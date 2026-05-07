#!/bin/bash


mv ipxe_RNS/admin.php /var/www/html/
mv ipxe_RNS/menu.php /var/www/html/
mv ipxe_RNS/extract.sh /var/www/html/scripts/
rm /etc/nginx/sites-available/default
mv ipxe_RNS/default /etc/nginx/sites-available/
cp /usr/lib/syslinux/memdisk /var/www/html/tftp/ 

sudo chown -R www-data:www-data /var/www/html /var/cache/nginx
sudo chmod -R 775 /var/www/html /var/cache/nginx