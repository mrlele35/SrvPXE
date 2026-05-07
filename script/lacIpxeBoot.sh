#!/bin/bash

SERVER_IP=$(hostname -I | awk '{print $1}')
SOURCE_DIR="/tmp/ipxe_build"
TARGET_DIR="/var/lib/tftpboot"

git clone https://github.com/ipxe/ipxe.git "$SOURCE_DIR"

cd "$SOURCE_DIR/src"

cat <<EOF > embed.ipxe
#!ipxe
dhcp
chain http://$SERVER_IP/menu.ipxe
EOF

make bin/undionly.kpxe EMBED=embed.ipxe

make bin-x86_64-efi/ipxe.efi EMBED=embed.ipxe

sudo mkdir -p "$TARGET_DIR"
sudo cp bin/undionly.kpxe "$TARGET_DIR/"
sudo cp bin-x86_64-efi/ipxe.efi "$TARGET_DIR/"

sudo chmod 644 "$TARGET_DIR/undionly.kpxe" "$TARGET_DIR/ipxe.efi"

echo "✅ Compilation terminée avec succès !"
echo "📍 Fichiers disponibles dans $TARGET_DIR"
