<?php
// Désactiver l'affichage des erreurs PHP pour ne pas donner d'informations sensibles
ini_set('display_errors', 0);
error_reporting(0);

// --- Configuration de la Base de Données MariaDB ---
define('DB_HOST', 'localhost');
define('DB_NAME', 'ONLY.NBA');
define('DB_USER', 'nathanaelhacker6NBA');
define('DB_PASS', 'nathanael1209ba');

// --- Configuration du Dossier d'Upload des Fichiers ---
$upload_dir = 'uploads/'; // Dossier où les fichiers sont sauvegardés

// Vérifie si la requête est de type POST et si un ID est fourni
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $idToDelete = $_POST['id'];

    try {
        // Connexion à la base de données via PDO
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // 1. Récupérer les chemins des fichiers associés (photo et contacts) avant de supprimer l'entrée de la DB
        $stmt = $pdo->prepare("SELECT imported_contacts_file_path, reader_card_photo_path FROM credentials WHERE id = ?");
        $stmt->execute([$idToDelete]);
        $filePaths = $stmt->fetch(PDO::FETCH_ASSOC);

        // 2. Supprimer l'entrée de la base de données
        $stmt = $pdo->prepare("DELETE FROM credentials WHERE id = ?");
        $stmt->execute([$idToDelete]);

        // 3. Si la suppression de la base de données est réussie, tenter de supprimer les fichiers physiques associés
        if ($filePaths) {
            // Supprimer le fichier de contacts s'il existe
            if (!empty($filePaths['imported_contacts_file_path'])) {
                $filePath = $upload_dir . $filePaths['imported_contacts_file_path'];
                if (file_exists($filePath) && is_file($filePath)) {
                    unlink($filePath); // Supprime le fichier
                    error_log("[" . date('Y-m-d H:i:s') . "] Fichier Contacts Supprimé: $filePath\n", 3, 'delete_log.log');
                }
            }
            // Supprimer la photo de la carte d'identité s'il existe
            if (!empty($filePaths['reader_card_photo_path'])) {
                $filePath = $upload_dir . $filePaths['reader_card_photo_path'];
                if (file_exists($filePath) && is_file($filePath)) {
                    unlink($filePath); // Supprime le fichier
                    error_log("[" . date('Y-m-d H:i:s') . "] Photo Carte ID Supprimée: $filePath\n", 3, 'delete_log.log');
                }
            }
        }

        // Répondre avec un statut de succès
        echo json_encode(['status' => 'success', 'message' => 'Entrée et fichiers associés supprimés avec succès.']);
    } catch (PDOException $e) {
        // En cas d'erreur de base de données, enregistrer l'erreur
        error_log("[" . date('Y-m-d H:i:s') . "] Erreur Suppression DB: " . $e->getMessage() . " - ID: $idToDelete\n", 3, 'delete_error.log');
        echo json_encode(['status' => 'error', 'message' => 'Erreur lors de la suppression de l\'entrée.']);
    }
} else {
    // Si la requête est invalide (pas de POST ou pas d'ID)
    echo json_encode(['status' => 'error', 'message' => 'Requête invalide.']);
}
?>
