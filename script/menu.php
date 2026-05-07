<?php
if(!isset($is_building)){
    header('Content-Type: text/plain');
}

$server_ip = "192.168.1.106"; 
$dir = "ipxe_scripts";

// On récupère tous les fichiers .ipxe du dossier scripts
$files = array_diff(scandir($dir), array('.', '..'));

echo "#!ipxe\n\n";
echo ":start\n";
echo "menu Serveur PXE Modulaire\n";

foreach ($files as $file) {
    if (pathinfo($file, PATHINFO_EXTENSION) === 'ipxe') {
        $basename = pathinfo($file, PATHINFO_FILENAME);
        echo "item $basename Lancer $basename\n";
    }
}
echo "item shell Shell iPXE\n";
echo "choose target && goto \${target}\n\n";

foreach ($files as $file) {
    if (pathinfo($file, PATHINFO_EXTENSION) === 'ipxe') {
        $basename = pathinfo($file, PATHINFO_FILENAME);
        echo ":$basename\n";
        echo "chain http://$server_ip/$dir/$file\n";
    }
}

echo ":shell\nshell\n";
?>
