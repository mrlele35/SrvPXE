#!/bin/bash

ISO_PATH=$1
DEST_DIR=$2

# On crée le dossier (on peut mettre sudo ici aussi si besoin)
sudo mkdir -p "$DEST_DIR"
echo "EN_COURS" > "$DEST_DIR/.status"

# Ici, le sudo est "good" si visudo est prêt
if sudo 7z x "$ISO_PATH" -o"$DEST_DIR/" -y; then
    echo "TERMINE" > "$DEST_DIR/.status"
else
    echo "ERREUR" > "$DEST_DIR/.status"
fi

# On réattribue les droits pour que Nginx puisse lire les fichiers extraits
sudo chown -R www-data:www-data "$DEST_DIR"
sudo chmod -R 755 "$DEST_DIR"
