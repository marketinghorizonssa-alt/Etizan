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
function etizan_post_json(string $url, array $payload): array {
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$json,CURLOPT_HTTPHEADER=>['Content-Type: application/json'],CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_CONNECTTIMEOUT=>6,CURLOPT_TIMEOUT=>12]);
    $body = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    if ($body === false || $error !== '') return ['ok'=>false,'http'=>$status,'error'=>$error];
    $decoded = json_decode((string)$body, true);
    return ['ok'=>$status >= 200 && $status < 300 && is_array($decoded) && !empty($decoded['ok']),'http'=>$status,'data'=>is_array($decoded)?$decoded:[]];
}
function etizan_handle_submit(): never {
    $token = etizan_secret();
    if ($token === '') etizan_json(503, ['ok'=>false,'error'=>'router_not_configured']);
    $raw = file_get_contents('php://input') ?: '';
    $data = json_decode($raw, true);
    if (!is_array($data)) $data = $_POST;
    $name = trim((string)($data['name'] ?? ''));
    $phone = trim((string)($data['phone'] ?? ''));
    $digits = preg_replace('/\D+/', '', $phone) ?? '';
    if (mb_strlen($name) < 2 || strlen($digits) < 9) etizan_json(422, ['ok'=>false,'error'=>'validation']);
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
    $result = etizan_post_json(ETIZAN_ROUTER . '?token=' . rawurlencode($token), $payload);
    if (empty($result['ok'])) etizan_json(502, ['ok'=>false,'error'=>'upstream']);
    etizan_json(200, ['ok'=>true,'lead_id'=>$result['data']['lead_id'] ?? null]);
}
