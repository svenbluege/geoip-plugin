#!/bin/bash
tmp_dir=$(mktemp -d -t ci-XXXXXXXXXX)
echo $tmp_dir
 

cp -r ../akgeoip/** $tmp_dir

pushd ./
cd $tmp_dir
rm lib/composer.*
rm -r lib/vendor/composer/ca-bundle/*
rm lib/vendor/composer/installed.json
rm -r lib/vendor/geoip2/geoip2/maxmind-db/*
rm -r lib/vendor/geoip2/geoip2/bin/*
rm lib/vendor/geoip2/geoip2/.gitmodules
rm lib/vendor/geoip2/geoip2/CHANGELOG.md
rm lib/vendor/geoip2/geoip2/composer.json
rm lib/vendor/maxmind/web-service-common/CHANGELOG.md
rm lib/vendor/maxmind/web-service-common/composer.json
rm -r lib/vendor/maxmind-db/reader/ext/*
rm -r lib/vendor/maxmind-db/reader/tests/*
rm lib/vendor/maxmind-db/reader/CHANGELOG.md
rm lib/vendor/maxmind-db/reader/composer.json

zip -r plg_system_akgeoip.zip .
popd

cp $tmp_dir/*.zip ../

rm -rf $tmp_dir