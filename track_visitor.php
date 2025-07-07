<?php
// track_visitor.php

// 1. Inclure vos informations de connexion à la base de données
// Assurez-vous que ces constantes sont bien définies.
// Si elles sont déjà définies ailleurs (par exemple dans un fichier de configuration),
// vous pouvez inclure ce fichier ici au lieu de redéfinir les constantes.
define('DB_HOST', 'localhost');
define('DB_NAME', 'ONLY.NBA'); // Vérifiez que c'est le bon nom de base de données
define('DB_USER', 'nathanaelhacker6NBA'); // Vérifiez que c'est le bon utilisateur
define('DB_PASS', 'nathanael1209ba'); // Vérifiez que c'est le bon mot de passe

try {
    // 2. Connexion à la base de données
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Active les erreurs PDO pour un meilleur débogage

    // 3. Récupérer les informations du visiteur
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'N/A'; // Adresse IP du visiteur
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'N/A'; // Navigateur et OS
    $referer = $_SERVER['HTTP_REFERER'] ?? null; // Page précédente (peut être vide si accès direct)
    $page_visited = $_SERVER['REQUEST_URI'] ?? 'N/A'; // La page actuelle visitée

    // 4. Préparer et exécuter la requête d'insertion
    $stmt = $pdo->prepare("INSERT INTO site_visitors (ip_address, user_agent, referer, page_visited) VALUES (?, ?, ?, ?)");
    $stmt->execute([$ip_address, $user_agent, $referer, $page_visited]);

} catch (PDOException $e) {
    // 5. Gérer les erreurs (très important pour le débogage !)
    // N'affichez PAS ces erreurs aux utilisateurs finaux.
    // Enregistrez-les dans un fichier de log sur le serveur.
    error_log("[" . date('Y-m-d H:i:s') . "] Visitor Tracking Error: " . $e->getMessage() . " IP: " . ($ip_address ?? 'N/A') . " Page: " . ($page_visited ?? 'N/A') . "\n", 3, 'visitor_tracking_error.log');
    // Pendant le développement, vous pouvez décommenter la ligne ci-dessous pour voir l'erreur directement :
    // echo "Erreur lors de l'enregistrement de la visite: " . $e->getMessage();
}
// Ce script ne doit rien afficher à l'écran, juste enregistrer la visite.
?>
