<?php
declare(strict_types=1);
require __DIR__ . '/../app/config.php';
$required = ['general','corporate','contracts','litigation','criminal','administrative','real-estate','labor','family-inheritance','enforcement-arbitration','insurance','financial-regulatory','bankruptcy','ip-franchise','cybercrime','aviation-transport'];
$pages = etizan_pages();
$missing = array_values(array_diff($required, array_keys($pages)));
if ($missing) { fwrite(STDERR, 'Missing routes: '.implode(',', $missing).PHP_EOL); exit(2); }
$services = etizan_services();
foreach ($pages as $key => $page) {
    foreach (['service','title','h1','lead','keywords','cards'] as $field) if (!array_key_exists($field, $page)) { fwrite(STDERR, "$key missing $field\n"); exit(3); }
    if (!in_array($page['service'], $services, true)) { fwrite(STDERR, "$key has unmapped service\n"); exit(4); }
    if (count($page['cards']) !== 3 || count($page['keywords']) < 4) { fwrite(STDERR, "$key content incomplete\n"); exit(5); }
}
$urls=[];
foreach (['riyadh','jeddah'] as $city) foreach (array_keys($pages) as $key) $urls[] = ETIZAN_BASE.'/'.$city.'/'.($key === 'general' ? '' : $key.'/');
if (count($urls) !== 32 || count(array_unique($urls)) !== 32) { fwrite(STDERR, "URL matrix invalid\n"); exit(6); }
echo "ETIZAN_ROUTE_QA_OK routes=".count($pages)." urls=".count($urls).PHP_EOL;
