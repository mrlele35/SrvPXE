#!/bin/bash

cat <<EOF >> /etc/sudoers.tmp

www-data ALL=(ALL) NOPASSWD: /var/www/html/scripts/extract.sh

EOF

visudo -c