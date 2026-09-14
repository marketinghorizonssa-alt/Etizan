<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

function etizan_json(int $status, array $body): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function etizan_ascii_digits(string $value): string {
    return strtr($value, [
        '٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9',
        '۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9'
    ]);
}

function etizan_normalize_phone(string $value): string {
    $raw = trim(etizan_ascii_digits($value));
    $digits = preg_replace('/\D+/u', '', $raw) ?? '';
    if (str_starts_with($digits, '00')) $digits = substr($digits, 2);

    // Saudi common formats: 05xxxxxxxx, 5xxxxxxxx, 9665xxxxxxxx,
    // +9665xxxxxxxx, 009665xxxxxxxx, and the occasionally entered 96605xxxxxxxx.
    if (strlen($digits) === 10 && str_starts_with($digits, '05')) return '966' . substr($digits, 1);
    if (strlen($digits) === 9 && str_starts_with($digits, '5')) return '966' . $digits;
    if (strlen($digits) === 13 && str_starts_with($digits, '96605')) return '966' . substr($digits, 4);
    return $digits;
}

function etizan_post_json(string $url, array $payload, int $timeoutMs = 3500): array {
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST=>true,
        CURLOPT_POSTFIELDS=>$json,
        CURLOPT_HTTPHEADER=>['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER=>true,
        CURLOPT_FOLLOWLOCATION=>true,
        CURLOPT_CONNECTTIMEOUT_MS=>1200,
        CURLOPT_TIMEOUT_MS=>$timeoutMs,
    ]);
    $body = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    if ($body === false || $error !== '') return ['ok'=>false,'http'=>$status,'error'=>$error];
    $decoded = json_decode((string)$body, true);
    return [
        'ok'=>$status >= 200 && $status < 300 && is_array($decoded) && !empty($decoded['ok']),
        'http'=>$status,
        'data'=>is_array($decoded)?$decoded:[],
        'error'=>'',
    ];
}

function etizan_outbox_db(): PDO {
    static $db = null;
    if ($db instanceof PDO) return $db;
    $home = rtrim((string)(getenv('HOME') ?: '/home/u878466595'), '/');
    $path = $home . '/.etizan-leads.sqlite';
    $db = new PDO('sqlite:' . $path, null, null, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    $db->exec('PRAGMA journal_mode=WAL');
    $db->exec('PRAGMA busy_timeout=3000');
    $db->exec('CREATE TABLE IF NOT EXISTS lead_outbox (
        submission_id TEXT PRIMARY KEY,
        payload TEXT NOT NULL,
        attempts INTEGER NOT NULL DEFAULT 0,
        last_error TEXT NOT NULL DEFAULT "",
        created_at INTEGER NOT NULL,
        updated_at INTEGER NOT NULL
    )');
    @chmod($path, 0600);
    return $db;
}

function etizan_queue_payload(string $submissionId, array $payload): void {
    $now = time();
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    $q = etizan_outbox_db()->prepare('INSERT INTO lead_outbox (submission_id,payload,attempts,last_error,created_at,updated_at)
        VALUES (:id,:payload,0,"",:created,:updated)
        ON CONFLICT(submission_id) DO UPDATE SET payload=excluded.payload, updated_at=excluded.updated_at');
    $q->execute([':id'=>$submissionId,':payload'=>$json,':created'=>$now,':updated'=>$now]);
}

function etizan_remove_queued(string $submissionId): void {
    $q = etizan_outbox_db()->prepare('DELETE FROM lead_outbox WHERE submission_id=:id');
    $q->execute([':id'=>$submissionId]);
}

function etizan_mark_queue_failure(string $submissionId, string $error): void {
    $q = etizan_outbox_db()->prepare('UPDATE lead_outbox SET attempts=attempts+1,last_error=:error,updated_at=:updated WHERE submission_id=:id');
    $q->execute([':error'=>mb_substr($error,0,500),':updated'=>time(),':id'=>$submissionId]);
}

function etizan_flush_outbox(int $limit = 20): array {
    $token = etizan_secret();
    if ($token === '') return ['ok'=>false,'error'=>'router_not_configured','sent'=>0,'failed'=>0];
    $db = etizan_outbox_db();
    $rows = $db->query('SELECT submission_id,payload FROM lead_outbox ORDER BY created_at ASC LIMIT ' . max(1,min(100,$limit)))->fetchAll(PDO::FETCH_ASSOC);
    $sent = 0; $failed = 0;
    foreach ($rows as $row) {
        $payload = json_decode((string)$row['payload'], true);
        if (!is_array($payload)) { etizan_remove_queued((string)$row['submission_id']); continue; }
        $result = etizan_post_json(ETIZAN_ROUTER . '?token=' . rawurlencode($token), $payload, 30000);
        if (!empty($result['ok'])) {
            etizan_remove_queued((string)$row['submission_id']);
            $sent++;
        } else {
            etizan_mark_queue_failure((string)$row['submission_id'], (string)($result['error'] ?? ('HTTP ' . ($result['http'] ?? 0))));
            $failed++;
        }
    }
    return ['ok'=>true,'sent'=>$sent,'failed'=>$failed,'remaining'=>(int)$db->query('SELECT COUNT(*) FROM lead_outbox')->fetchColumn()];
}

function etizan_handle_submit(): never {
    $token = etizan_secret();
    if ($token === '') etizan_json(503, ['ok'=>false,'error'=>'router_not_configured']);
    $raw = file_get_contents('php://input') ?: '';
    $data = json_decode($raw, true);
    if (!is_array($data)) $data = $_POST;

    $name = trim((string)($data['name'] ?? ''));
    $phone = etizan_normalize_phone((string)($data['phone'] ?? ''));
    if (mb_strlen($name) < 2 || strlen($phone) < 8 || strlen($phone) > 15) {
        etizan_json(422, ['ok'=>false,'error'=>'validation']);
    }

    $city = in_array(($data['city'] ?? ''), ['الرياض','جدة'], true) ? (string)$data['city'] : 'الرياض';
    $service = in_array(($data['service'] ?? ''), etizan_services(), true) ? (string)$data['service'] : 'خدمات قانونية أخرى';
    $submissionId = trim((string)($data['website_submission_id'] ?? ''));
    if ($submissionId === '') $submissionId = 'ETZ-WEB-' . round(microtime(true) * 1000) . '-' . bin2hex(random_bytes(3));

    $payload = [
        'name'=>$name,'phone'=>$phone,'city'=>$city,'service'=>$service,
        'message'=>mb_substr((string)($data['message'] ?? ''),0,1600),
        'preferred_contact'=>'غير محدد','consent'=>'نعم','consent_version'=>'v1',
        'consent_at'=>gmdate('c'),'submitted_at'=>gmdate('c'),'website_submission_id'=>$submissionId,
        'page_url'=>mb_substr((string)($data['page_url'] ?? ''),0,1000),'referrer'=>mb_substr((string)($data['referrer'] ?? ''),0,1000)
    ];
    foreach (['gclid','gbraid','wbraid','utm_source','utm_medium','utm_campaign','utm_term','utm_content','campaign_id','adgroup_id','creative_id'] as $key) {
        $payload[$key] = mb_substr((string)($data[$key] ?? ''),0,500);
    }

    $queued = false;
    try {
        etizan_queue_payload($submissionId, $payload);
        $queued = true;
    } catch (Throwable $e) {
        $queued = false;
    }

    // Try immediate Sheet delivery, but never make the visitor wait on a slow Apps Script response.
    $result = etizan_post_json(ETIZAN_ROUTER . '?token=' . rawurlencode($token), $payload, $queued ? 3500 : 12000);
    if (!empty($result['ok'])) {
        if ($queued) etizan_remove_queued($submissionId);
        etizan_json(200, ['ok'=>true,'lead_id'=>$result['data']['lead_id'] ?? $submissionId,'synced'=>true]);
    }

    // The request is already durably stored locally. A CLI cron retries with the same submission ID,
    // so Apps Script dedupe prevents duplicate customer rows if Google wrote before its response timed out.
    if ($queued) {
        etizan_mark_queue_failure($submissionId, (string)($result['error'] ?? ('HTTP ' . ($result['http'] ?? 0))));
        etizan_json(200, ['ok'=>true,'lead_id'=>$submissionId,'synced'=>false,'queued'=>true]);
    }

    etizan_json(502, ['ok'=>false,'error'=>'upstream']);
}
