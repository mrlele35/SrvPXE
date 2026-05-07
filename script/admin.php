<?php
$scripts_dir = "ipxe_scripts/";
$locales_dir = "iso_locales/";
$cache_dir = "/var/cache/nginx/iso_cache/";
$json_file = "isos.json";
$static_menu_file = "menu.ipxe";
$locales_dir = "iso_locales/";

// Scan du dossier local
$local_files = [];
if (is_dir($locales_dir)) {
    $files = array_diff(scandir($locales_dir), array('.', '..'));
    foreach ($files as $f) {
        $local_files[] = [
            'name' => $f,
            'path' => $locales_dir . $f,
            'url'  => "http://" . $_SERVER['SERVER_ADDR'] . "/" . $locales_dir . $f,
            'size' => round(filesize($locales_dir . $f) / (1024 * 1024), 2) . " Mo"
        ];
    }
}

// --- FONCTION DE SCAN PHYSIQUE (Uniquement ce qui est sur le disque) ---
function syncPhysicalInventory($loc_dir, $cache_path, $json_p) {
    $inventory = [];
    if (is_dir($loc_dir)) {
        $files = array_diff(scandir($loc_dir), array('.', '..'));
        foreach ($files as $f) {
            $inventory[] = [
                "name" => "🏠 [LOCAL] " . $f,
                "url" => "http://" . $_SERVER['SERVER_ADDR'] . "/" . $loc_dir . $f,
                "size" => round(filesize($loc_dir . $f) / (1024 * 1024), 2) . " Mo",
                "type" => "Stockage Interne"
            ];
        }
    }
    if (is_dir($cache_path)) {
        $finfo = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($cache_path));
        $cache_count = 0;
        foreach ($finfo as $file) { if ($file->isFile()) $cache_count++; }
        if ($cache_count > 0) {
            $inventory[] = [
                "name" => "⚡ Cache Nginx (Fichiers fragmentés)",
                "url" => "Dynamique (Proxy)",
                "size" => $cache_count . " objets",
                "type" => "Proxy Cache"
            ];
        }
    }
    file_put_contents($json_p, json_encode($inventory, JSON_PRETTY_PRINT));
}
// --- ACTION : EXTRACTION ASYNCHRONE D'ISO ---
if (isset($_GET['extract_iso_async'])) {
    $iso_name = basename($_GET['extract_iso_async']);
    $iso_path = "/var/www/html/iso_locales/" . $iso_name;
    $folder_name = pathinfo($iso_name, PATHINFO_FILENAME);
    $dest_dir = "/var/www/html/distrib/" . $folder_name;

    if (file_exists($iso_path)) {
        // Le chemin vers ton script bash
        $script_path = "/var/www/html/scripts/extract.sh";
        
        // On lance le script en arrière-plan
        $cmd = "sudo $script_path " . escapeshellarg($iso_path) . " " . escapeshellarg($dest_dir) . " > /dev/null 2>&1 &";
        
        shell_exec($cmd);
        
        // On redirige tout de suite, sans attendre la fin de l'extraction
        header("Location: admin.php?msg=Extraction lancée en arrière-plan !");
        exit;
    }
}
// --- ACTIONS ---
if (isset($_GET['del_iso'])) {
    $file_to_delete = $locales_dir . basename($_GET['del_iso']);
    if (file_exists($file_to_delete)) {
        unlink($file_to_delete);
        // On force une synchro de l'inventaire après suppression
        header("Location: admin.php?msg=deleted");
        exit;
    }
}
// Build Menu + Sync Physique + Preview
if (isset($_POST['action']) && $_POST['action'] === 'build_menu') {
    $is_building = true; 
    syncPhysicalInventory($locales_dir, $cache_dir, $json_file);
    ob_start();
    include 'menu.php';
    $generated_content = ob_get_clean();
    file_put_contents($static_menu_file, $generated_content);
    $msg = "✅ Inventaire synchronisé et menu.ipxe généré !";
}

// Upload Script
if (isset($_FILES['ipxe_file'])) {
    move_uploaded_file($_FILES['ipxe_file']['tmp_name'], $scripts_dir . basename($_FILES['ipxe_file']['name']));
    header("Location: admin.php"); exit;
}

// Upload ISO Locale (Limite PHP à vérifier dans php.ini)
if (isset($_FILES['iso_file'])) {
    if (!is_dir($locales_dir)) mkdir($locales_dir, 0775, true);
    move_uploaded_file($_FILES['iso_file']['tmp_name'], $locales_dir . basename($_FILES['iso_file']['name']));
    header("Location: admin.php"); exit;
}

// Suppression Script
if (isset($_GET['del_s'])) {
    unlink($scripts_dir . basename($_GET['del_s']));
    header("Location: admin.php"); exit;
}

$scripts = array_diff(scandir($scripts_dir), array('.', '..'));
$isos = json_decode(file_get_contents($json_file), true) ?? [];
$total_cache = exec("du -sh $cache_dir | cut -f1") ?: "0";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin PXE - Modulaire</title>
    <style>
        body{font-family:sans-serif;background:#eee;padding:20px;}
        .card{background:#fff;padding:20px;margin-bottom:20px;border-radius:5px;box-shadow:0 2px 5px rgba(0,0,0,0.1);}
        .btn-build { background: #e67e22; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; }
        .preview { background: #282c34; color: #abb2bf; padding: 15px; border-radius: 5px; margin-top: 15px; text-align: left; font-family: monospace; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { text-align: left; padding: 8px; border-bottom: 1px solid #eee; }
        .tag-ready { color: #2ecc71; font-weight: bold; }
    </style>
</head>
<body>

    <div class="card" style="text-align: center;">
        <h1>🛠️ État du Serveur</h1>
        <?php if(isset($msg)) echo "<p style='color:green;'>$msg</p>"; ?>
        <form method="POST">
            <input type="hidden" name="action" value="build_menu">
            <button type="submit" class="btn-build">🔄 Synchroniser l'inventaire réel & Build</button>
        </form>

        <?php if(isset($generated_content)): ?>
            <div class="preview">
                <strong style="color: #61afef;">Aperçu du menu généré :</strong><br><br>
                <pre><?= htmlspecialchars($generated_content) ?></pre>
            </div>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>📦 Fichiers physiquement présents (Total Cache: <?= $total_cache ?>)</h2>
        <table>
            <tr><th>Type</th><th>Nom / Description</th><th>Poids / Objets</th><th>Statut</th></tr>
            <?php foreach($isos as $iso): ?>
            <tr>
                <td><small><?= $iso['type'] ?></small></td>
                <td><strong><?= htmlspecialchars($iso['name']) ?></strong></td>
                <td><?= $iso['size'] ?></td>
                <td class="tag-ready">✓ PRÊT</td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="card">
        <h2>📂 Gestion des Scripts (.ipxe)</h2>
        <form method="POST" enctype="multipart/form-data"><input type="file" name="ipxe_file"><button>Uploader</button></form>
        <ul>
            <?php foreach($scripts as $s): ?>
                <li><?= $s ?> <a href="?del_s=<?= $s ?>" style="color:red; text-decoration:none;">[X]</a></li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="card">
        <h2>📤 Pousser une ISO locale</h2>
        <p><small>Note: Assurez-vous que <code>upload_max_filesize</code> est suffisant dans votre php.ini</small></p>
        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="iso_file">
            <button type="submit">Uploader l'ISO</button>
        </form>
    </div>
    <div class="card">
    <h2>🏠 Bibliothèque d'ISO Locales</h2>
    <p><small>Voici les fichiers stockés physiquement sur votre serveur. Utilisez l'URL pour vos scripts iPXE.</small></p>
    
    <table>
        <thead>
            <tr>
                <th>Nom du fichier</th>
                <th>Taille</th>
                <th>URL iPXE (à copier)</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($local_files)): ?>
                <tr><td colspan="4" style="text-align:center;">Aucune ISO locale trouvée.</td></tr>
            <?php else: ?>
                <?php foreach ($local_files as $iso): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($iso['name']) ?></strong></td>
                    <td><span class="tag" style="background:#95a5a6;"><?= $iso['size'] ?></span></td>
                    <td>
                        <input type="text" value="<?= $iso['url'] ?>" readonly 
                               style="width:100%; font-family:monospace; font-size:0.8em; border:1px solid #ddd; padding:5px; background:#f9f9f9;">
                    </td>
                    <td>
                        <a href="?del_iso=<?= urlencode($iso['name']) ?>" 
                           onclick="return confirm('Supprimer cette ISO ?')" 
                           style="color:#e74c3c; text-decoration:none; font-weight:bold;">🗑️ Supprimer</a>
                    </td>
                    <td>
    <?php 
    $folder_name = pathinfo($iso['name'], PATHINFO_FILENAME);
    $dest_dir = "/var/www/html/distrib/" . $folder_name;
    $status_file = $dest_dir . "/.status";
    $status = "";

    // On lit le fichier de statut s'il existe
    if (file_exists($status_file)) {
        $status = trim(file_get_contents($status_file));
    }
    ?>

    <?php if ($status === "EN_COURS"): ?>
        <span style="color:#f39c12; font-weight:bold; font-size:0.9em;">⏳ Extraction en cours...</span>
        
        <a href="admin.php" 
           style="background:#f39c12; color:white; padding:3px 8px; border-radius:3px; text-decoration:none; font-size:0.8em; margin-left:5px;">
           🔄 Actualiser
        </a>

    <?php elseif ($status === "TERMINE"): ?>
        <span style="color:#27ae60; font-weight:bold; font-size:0.9em;">✅ Prêt (UEFI)</span>

    <?php elseif ($status === "ERREUR"): ?>
        <span style="color:#e74c3c; font-weight:bold; font-size:0.9em;">❌ Erreur d'extraction</span>
        <a href="?extract_iso_async=<?= urlencode($iso['name']) ?>" 
           style="margin-left:5px; font-size:0.8em; color:#3498db;">Réessayer</a>

    <?php else: ?>
        <a href="?extract_iso_async=<?= urlencode($iso['name']) ?>" 
           class="btn-extract" 
           style="background:#27ae60; color:white; padding:5px 10px; border-radius:3px; text-decoration:none; font-size:0.8em;">
           📦 Extraire (Arrière-plan)
        </a>
    <?php endif; ?>
</td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>
