#!/bin/bash
wget "http://geolite.maxmind.com/download/geoip/database/GeoLite2-Country.mmdb.gz"
gunzip GeoLite2-Country.mmdb.gz
mv GeoLite2-Country.mmdb ../plugins/system/akgeoip/db/
