<?php
declare(strict_types=1);
if ($argc < 5) { fwrite(STDERR, "usage: php build-brand-assets.php <logo> <hero> <about> <outdir>\n"); exit(2); }
[$script,$logo,$hero,$about,$out] = $argv;
if (!extension_loaded('gd')) { fwrite(STDERR, "GD is required\n"); exit(3); }
@mkdir($out, 0755, true);
function load_img(string $path) {
    $raw = @file_get_contents($path);
    if ($raw === false || strlen($raw) < 1000) throw new RuntimeException("bad source: $path");
    $im = @imagecreatefromstring($raw);
    if (!$im) throw new RuntimeException("unsupported image: $path");
    return $im;
}
function fit_webp(string $src, string $dest, int $maxW, int $quality, bool $alpha=false): void {
    $im = load_img($src); $w = imagesx($im); $h = imagesy($im);
    $nw = min($w, $maxW); $nh = (int)round($h * ($nw / $w));
    $dst = imagecreatetruecolor($nw, $nh);
    if ($alpha) { imagealphablending($dst,false); imagesavealpha($dst,true); $clear=imagecolorallocatealpha($dst,0,0,0,127); imagefill($dst,0,0,$clear); }
    imagecopyresampled($dst,$im,0,0,0,0,$nw,$nh,$w,$h);
    if (!imagewebp($dst,$dest,$quality)) throw new RuntimeException("webp failed: $dest");
    imagedestroy($im); imagedestroy($dst);
}
try {
    fit_webp($logo, $out.'/logo-brand.webp', 384, 90, true);
    fit_webp($hero, $out.'/hero-bg.webp', 1024, 72, false);
    fit_webp($hero, $out.'/hero-bg-mobile.webp', 640, 68, false);
    fit_webp($about, $out.'/about-image.webp', 960, 74, false);
    fit_webp($about, $out.'/about-image-mobile.webp', 640, 72, false);
    foreach (['logo-brand.webp','hero-bg.webp','hero-bg-mobile.webp','about-image.webp','about-image-mobile.webp'] as $f) {
        $p=$out.'/'.$f; if (!is_file($p) || filesize($p) < 1000) throw new RuntimeException("missing output: $f");
        echo $f.':'.filesize($p)."\n";
    }
} catch (Throwable $e) { fwrite(STDERR,$e->getMessage()."\n"); exit(4); }
