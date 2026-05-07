#!/bin/bash

# Configuration souhaitée
SIZE="5G"
TIME="1800"

# Trouver le chemin du php.ini (détecte FPM pour Nginx)
PHP_INI=$(php -i | grep "Loaded Configuration File" | awk '{print $5}')

if [ -z "$PHP_INI" ]; then
    echo "❌ Impossible de trouver le fichier php.ini"
    exit 1
fi

echo "⚙️ Modification de : $PHP_INI"

# Appliquer les modifications avec sed
sudo sed -i "s/upload_max_filesize = .*/upload_max_filesize = $SIZE/" "$PHP_INI"
sudo sed -i "s/post_max_size = .*/post_max_size = $SIZE/" "$PHP_INI"
sudo sed -i "s/memory_limit = .*/memory_limit = 512M/" "$PHP_INI"
sudo sed -i "s/max_execution_time = .*/max_execution_time = $TIME/" "$PHP_INI"
sudo sed -i "s/max_input_time = .*/max_input_time = $TIME/" "$PHP_INI"

# Redémarrer le service (ajuster si tu n'es pas en php8.2-fpm)
echo "🔄 Redémarrage de PHP-FPM..."
sudo systemctl restart php*-fpm

echo "✅ Configuration PHP mise à jour avec succès !"