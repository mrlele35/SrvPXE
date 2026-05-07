#!/bin/bash

sudo mkdir -p /var/www/html/{ipxe_scripts,iso_locales,tftp,distrib,scripts}
sudo mkdir -p /var/cache/nginx/iso_cache
sudo chown -R www-data:www-data /var/www/html /var/cache/nginx
sudo chmod -R 775 /var/www/html /var/cache/nginx
sudo mkdir -p /var/lib/tftpboot

DESTFILE="/etc/dnsmasq.conf"

mv "$DESTFILE" /etc/dnsmasq.conf.backup

cat <<EOF >  "$DESTFILE"

port=0
enable-tftp
tftp-root=/var/lib/tftpboot

EOF