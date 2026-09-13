#!/bin/sh
set -eu
SHA="${1:?commit sha required}"
T="/tmp/etizan-selftest-${SHA}"
Z="$T.zip"
rm -rf "$T" "$Z"; mkdir -p "$T"
curl -fsSL "https://codeload.github.com/marketinghorizonssa-alt/Etizan/zip/${SHA}" -o "$Z"
unzip -q "$Z" -d "$T"
S=$(find "$T" -mindepth 1 -maxdepth 1 -type d | head -n 1)
php -l "$S/index.php" >/dev/null
php -l "$S/app/config.php" >/dev/null
php -l "$S/app/leads.php" >/dev/null
php -l "$S/app/view.php" >/dev/null
php -l "$S/scripts/build-brand-assets.php" >/dev/null
curl -fsSL https://www.etizan-law.com/logo.png -o "$T/logo.png"
curl -fsSL https://www.etizan-law.com/hero-bg-wide-gavel.png -o "$T/hero.png"
curl -fsSL https://www.etizan-law.com/about-image.png -o "$T/about.png"
php "$S/scripts/build-brand-assets.php" "$T/logo.png" "$T/hero.png" "$T/about.png" "$T/out"
php -r '$c=file_get_contents($argv[1]);if(strpos($c,"#1b4f7a")===false)exit(2);' "$S/public/assets/site.css"
php -r '$c=file_get_contents($argv[1]);foreach(["logo-brand.webp","hero-bg.webp","about-image.webp","ETIZAN_GTM"] as $x)if(strpos($c,$x)===false)exit(2);' "$S/app/view.php"
php -r '$c=file_get_contents($argv[1]);if(strpos($c,"GTM-5ZKR4X9C")===false)exit(2);' "$S/app/config.php"
printf 'ETIZAN_SELFTEST_OK:%s\n' "$SHA"
rm -rf "$T" "$Z"
