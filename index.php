<?php
declare(strict_types=1);
require_once __DIR__ . '/app/config.php';
require_once __DIR__ . '/app/leads.php';
require_once __DIR__ . '/app/view.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if ($path === '/healthz') {
    header('Content-Type: application/json; charset=utf-8'); header('Cache-Control: no-store');
    echo json_encode(['ok'=>true,'service'=>'etizan-law','release'=>etizan_release(),'router_configured'=>etizan_secret()!==''], JSON_UNESCAPED_SLASHES); exit;
}
if ($path === '/submit') {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') etizan_json(405,['ok'=>false,'error'=>'method_not_allowed']);
    etizan_handle_submit();
}
if ($path === '/llms.txt') {
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: public, max-age=86400');
    echo "# شركة إتزان للمحاماة والاستشارات القانونية\n\n";
    echo "> شركة سعودية تقدم خدمات قانونية للشركات والمستثمرين والأفراد عبر صفحات متخصصة لفرعي الرياض وجدة.\n\n";
    echo "## الفروع\n";
    echo "- [مكتب الرياض](".ETIZAN_BASE."/riyadh/)\n";
    echo "- [مكتب جدة](".ETIZAN_BASE."/jeddah/)\n\n";
    echo "## صفحات الخدمات\n";
    foreach (['riyadh'=>'الرياض','jeddah'=>'جدة'] as $cityEn=>$cityAr) {
        foreach (etizan_pages() as $key=>$page) {
            $url=ETIZAN_BASE.'/'.$cityEn.'/'.($key==='general'?'':$key.'/');
            $label=str_replace('%CITY%',$cityAr,(string)$page['h1']);
            echo '- ['.$label.']('.$url.")\n";
        }
    }
    echo "\n## التواصل\n- [سياسة الخصوصية](".ETIZAN_PRIVACY.")\n";
    exit;
}
if ($path === '/robots.txt') { header('Content-Type: text/plain; charset=utf-8'); echo "User-agent: *\nAllow: /\nSitemap: https://etizan.hositee.com/sitemap.xml\n"; exit; }
if ($path === '/sitemap.xml') {
    $urls=[]; foreach(['riyadh','jeddah'] as $city) foreach(array_keys(etizan_pages()) as $key) $urls[]=ETIZAN_BASE.'/'.$city.'/'.($key==='general'?'':$key.'/');
    header('Content-Type: application/xml; charset=utf-8'); echo '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'; foreach($urls as $url) echo '<url><loc>'.htmlspecialchars($url,ENT_XML1).'</loc></url>'; echo '</urlset>'; exit;
}
if ($path === '/') { header('Location: /riyadh/', true, 302); exit; }
if (preg_match('#^/(riyadh|jeddah)/ip-insurance/?$#', $path, $m)) { header('Location: /'.$m[1].'/ip-franchise/', true, 301); exit; }
if (preg_match('#^/(riyadh|jeddah)(?:/([^/]+))?/?$#', $path, $m)) {
    $key = $m[2] ?? 'general'; if ($key !== 'general' && !isset(etizan_pages()[$key])) { http_response_code(404); echo 'Not Found'; exit; }
    ob_start(static fn(string $html): string => str_replace('/assets/site.js?v=10','/assets/site.js?v=12',$html));
    etizan_render($path);
}
http_response_code(404); header('Content-Type: text/plain; charset=utf-8'); echo 'Not Found';
