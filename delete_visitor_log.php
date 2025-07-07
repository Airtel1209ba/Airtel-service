<?php
// Configuration pour désactiver l'affichage des erreurs en production
ini_set('display_errors', 0);
error_reporting(0);

// Identifiants d'administration pour ce tableau de bord (À NE PAS LAISSER HARDCODÉ EN PRODUCTION)
$visitor_admin_username = 'visiteurHACKER'; // Exemple: Un nom d'utilisateur spécifique pour le log des visiteurs
$visitor_admin_password = 'visiteur1209ba'; // Exemple: Un mot de passe spécifique (DOIT ÊTRE HASHÉ EN PRODUCTION)

// Constantes de connexion à la base de données (intégrées directement ici)
define('DB_HOST', 'localhost');
define('DB_NAME', 'ONLY.NBA');
define('DB_USER', 'nathanaelhacker6NBA');
define('DB_PASS', 'nathanael1209ba');

// Démarrage de la session si ce n'est pas déjà fait
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error_message = ''; // Message d'erreur pour le formulaire de connexion
$error_message_db = ''; // Message d'erreur pour la base de données (si authentifié)
$success_message_delete = ''; // Message de succès après suppression

// --- GESTION DE LA SUPPRESSION DE LOG (requête AJAX POST) ---
// Cette partie est exécutée si une requête POST est reçue avec l'action 'delete_log'
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_log' && isset($_POST['id'])) {
    
    // Vérifier l'authentification avant de traiter la suppression
    if (!isset($_SESSION['authenticated_visitor_log']) || $_SESSION['authenticated_visitor_log'] !== true) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Non autorisé. Veuillez vous reconnecter.']);
        exit();
    }

    $log_id = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT); // Nettoyer l'ID
    
    $response = ['status' => 'error', 'message' => 'Requête de suppression invalide ou ID manquant.'];

    try {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false); // Désactiver l'émulation pour une vraie protection contre les injections SQL

        // Préparer la requête de suppression
        $stmt = $pdo->prepare("DELETE FROM site_visitors WHERE id = :id");
        $stmt->bindValue(':id', $log_id, PDO::PARAM_INT); // Lier l'ID en tant qu'entier pour la sécurité

        if ($stmt->execute()) {
            if ($stmt->rowCount() > 0) {
                $response['status'] = 'success';
                $response['message'] = 'Entrée de log supprimée avec succès.';
            } else {
                $response['message'] = 'Aucune entrée de log trouvée avec cet ID.';
            }
        } else {
            $response['message'] = 'Échec de la suppression de l\'entrée de log.';
        }

    } catch (PDOException $e) {
        $response['message'] = "Erreur de base de données lors de la suppression.";
        error_log("[" . date('Y-m-d H:i:s') . "] DB Delete Visitor Log Error (inline): " . $e->getMessage() . " - ID: " . $log_id . " - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'N/A') . "\n", 3, __DIR__ . '/../logs/dashboard_db_error.log');
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit(); // IMPORTANT: Arrêter l'exécution après avoir envoyé la réponse JSON pour AJAX
}
// --- FIN GESTION DE LA SUPPRESSION DE LOG ---


// Gestion de la déconnexion
if (isset($_GET['logout']) && $_GET['logout'] === 'true') {
    // Détruire toutes les variables de session spécifiques à ce panneau
    unset($_SESSION['authenticated_visitor_log']);

    // Supprimer le cookie de session si présent et s'il est spécifique à cette session
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"] // Corrigé: httppnly -> httponly
        );
    }
    // Si vous voulez détruire la session complètement, utilisez session_destroy();
    // Mais soyez prudent si d'autres parties de votre site utilisent la même session.
    // session_destroy(); 

    header('Location: log_visit.php', true, 302); // Rediriger vers la page de connexion
    exit();
}

// Traitement du formulaire de connexion
if (isset($_POST['username']) && isset($_POST['password'])) {
    $input_username = $_POST['username'];
    $input_password = $_POST['password'];

    // Vérification simple (À REMPLACER PAR UNE VÉRIFICATION DE MOT DE PASSE HASHÉ EN PRODUCTION)
    if ($input_username === $visitor_admin_username && $input_password === $visitor_admin_password) {
        $_SESSION['authenticated_visitor_log'] = true;
        // Régénérer l'ID de session après une connexion réussie pour prévenir les attaques de fixation de session
        session_regenerate_id(true); 
        header('Location: log_visit.php', true, 302); // Rediriger pour nettoyer l'URL après connexion
        exit();
    } else {
        $error_message = "Nom d'utilisateur ou mot de passe incorrect.";
    }
}

// --- Début du Bloc d'Authentification ---
// Si l'utilisateur n'est PAS authentifié, afficher le formulaire de connexion
if (!isset($_SESSION['authenticated_visitor_log']) || $_SESSION['authenticated_visitor_log'] !== true) {
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accès Tableau de Bord Visiteurs</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .airtel-red-500 { background-color: #E4002B; }
        .airtel-red-600 { background-color: #B00021; }
        .airtel-text-red-500 { color: #E4002B; }
        .airtel-border-red-500 { border-color: #E4002B; }
        input:focus, button:focus, select:focus {
            outline: none !important;
            border-color: #E4002B !important;
            box-shadow: 0 0 0 3px rgba(228, 0, 43, 0.4) !important;
        }
        .btn-airtel {
            @apply rounded-full shadow-md transition duration-300 transform hover:scale-105;
        }
    </style>
    <meta http-equiv="Content-Security-Policy" content="
        default-src 'self';
        script-src 'self' https://cdn.tailwindcss.com;
        style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://fonts.googleapis.com;
        img-src 'self' https://placehold.co;
        font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com;
        connect-src 'self';
        form-action 'self';
        base-uri 'self';
        frame-ancestors 'none';
    ">
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen p-4">
    <div class="bg-white p-8 rounded-xl shadow-2xl w-full max-w-sm border-t-8 border-airtel-red-500 transform transition duration-500 hover:scale-[1.02]">
        <div class="flex flex-col items-center mb-6">
            <img src="https://placehold.co/80x80/E4002B/FFFFFF?text=Airtel" alt="Logo Airtel" class="h-20 w-20 rounded-full mb-3 border-4 border-gray-200 shadow-inner">
            <h2 class="text-4xl font-extrabold text-center text-airtel-text-red-500">Airtel Log Visiteurs</h2>
            <p class="text-gray-600 text-center mt-2">Accès Administrateur</p>
        </div>
        <?php if (!empty($error_message)): ?>
            <p class="bg-red-100 text-red-700 p-3 rounded-lg text-center mb-4 border border-red-200"><?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="mb-5">
                <label for="username" class="block text-gray-700 text-sm font-semibold mb-2">Nom d'utilisateur:</label>
                <div class="relative">
                    <input type="text" id="username" name="username" class="pl-10 shadow-sm appearance-none border rounded-lg w-full py-3 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-airtel-red-500" required placeholder="adminvisiteur">
                    <i class="fas fa-user absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                </div>
            </div>
            <div class="mb-6">
                <label for="password" class="block text-gray-700 text-sm font-semibold mb-2">Mot de passe:</label>
                <div class="relative">
                    <input type="password" id="password" name="password" class="pl-10 shadow-sm appearance-none border rounded-lg w-full py-3 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:ring-2 focus:ring-airtel-red-500" required placeholder="••••••••">
                    <i class="fas fa-lock absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                </div>
            </div>
            <button type="submit" class="airtel-red-500 hover:airtel-red-600 text-white font-bold py-3 px-4 w-full btn-airtel focus:outline-none focus:shadow-outline">
                <i class="fas fa-sign-in-alt mr-2"></i>Se connecter
            </button>
        </form>
        <p class="text-center text-gray-500 text-xs mt-6">
            &copy; <?php echo date("Y"); ?> Airtel. Tous droits réservés.
        </p>
    </div>
</body>
</html>
<?php
exit(); // Arrête l'exécution du script après l'affichage du formulaire de connexion
}
// --- Fin du Bloc d'Authentification ---


// --- Début du code pour le tableau de bord (exécuté si authentifié) ---
$site_visitors_logs = []; // Pour stocker les logs détaillés

try {
    // Connexion à la base de données via PDO (PHP Data Objects)
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Active le mode d'erreur pour les exceptions
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false); // Désactiver l'émulation des requêtes préparées pour une vraie protection contre les injections SQL

    // Récupérer le message de succès de la session après une suppression
    if (isset($_SESSION['success_message_delete'])) {
        $success_message_delete = $_SESSION['success_message_delete'];
        unset($_SESSION['success_message_delete']); // Effacer le message après l'avoir affiché
    }

    // --- 1. Récupérer le nombre total de visites ---
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM site_visitors");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_visits = $result['total'];

    // --- 2. Récupérer le nombre de visiteurs uniques (basé sur l'IP) ---
    $stmt = $pdo->query("SELECT COUNT(DISTINCT ip_address) AS unique_count FROM site_visitors");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $unique_ips = $result['unique_count'];

    // --- 3. Récupérer les 5 pages les plus visitées ---
    $stmt = $pdo->prepare("SELECT page_visited, COUNT(*) AS count FROM site_visitors GROUP BY page_visited ORDER BY count DESC LIMIT :limit");
    $stmt->bindValue(':limit', 5, PDO::PARAM_INT);
    $stmt->execute();
    $most_visited_pages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- 4. Récupérer les 5 agents utilisateurs les plus fréquents ---
    $stmt = $pdo->prepare("SELECT user_agent, COUNT(*) AS count FROM site_visitors GROUP BY user_agent ORDER BY count DESC LIMIT :limit");
    $stmt->bindValue(':limit', 5, PDO::PARAM_INT);
    $stmt->execute();
    $top_user_agents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- NOUVEAU: Récupérer tous les logs détaillés pour le tableau ---
    $stmt = $pdo->query("SELECT id, ip_address, user_agent, page_visited, timestamp FROM site_visitors ORDER BY timestamp DESC");
    $site_visitors_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_message_db = "Une erreur est survenue lors de la récupération des données. Veuillez réessayer plus tard.";
    // Remarque: Le chemin du fichier de log doit exister et être accessible en écriture
    error_log("[" . date('Y-m-d H:i:s') . "] DB Dashboard Error: " . $e->getMessage() . " - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'N/A') . "\n", 3, __DIR__ . '/../logs/dashboard_db_error.log');
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord Visiteurs - Airtel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --airtel-red: #E4002B;
            --airtel-red-dark: #B00021;
            --light-bg: #F8F9FA;
            --card-bg: #FFFFFF;
            --text-dark: #212529;
            --text-muted: #6C757D;
            --border-light: #E9ECEF;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--light-bg);
            color: var(--text-dark);
            transition: background-color 0.3s ease;
        }

        input:focus, button:focus, select:focus, a:focus {
            outline: none !important;
            border-color: var(--airtel-red) !important;
            box-shadow: 0 0 0 3px rgba(228, 0, 43, 0.2) !important;
        }

        .dashboard-header {
            background: linear-gradient(90deg, var(--airtel-red), var(--airtel-red-dark));
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            border-bottom: 5px solid rgba(255, 255, 255, 0.15);
        }
        .header-logo {
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.1));
        }

        .dashboard-card {
            background-color: var(--card-bg);
            border-radius: 1rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            justify-content: center;
            text-align: left;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border-left: 8px solid var(--airtel-red);
            position: relative;
            overflow: hidden;
        }
        .dashboard-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 50px;
            height: 50px;
            background: var(--airtel-red);
            border-bottom-left-radius: 1rem;
            opacity: 0.05;
            transform: rotate(45deg) translate(20px, -20px);
            z-index: 0;
        }
        .dashboard-card:hover {
            transform: translateY(-5px) scale(1.005);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        }
        .dashboard-card-icon {
            color: var(--airtel-red);
            font-size: 2.5rem;
            margin-bottom: 1rem;
            transition: transform 0.3s ease;
        }
        .dashboard-card:hover .dashboard-card-icon {
            transform: scale(1.1);
        }
        .dashboard-card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
            transition: color 0.3s ease;
        }
        .dashboard-card:hover .dashboard-card-title {
            color: var(--text-dark);
        }
        .dashboard-card-value {
            font-size: 3.5rem;
            font-weight: 800;
            color: var(--airtel-red);
            line-height: 1;
        }
        .dashboard-card-label {
            font-size: 1rem;
            color: var(--text-muted);
            margin-top: 0.25rem;
        }

        .list-section-card {
            background-color: var(--card-bg);
            border-radius: 1rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            padding: 1.5rem;
            border-left: 8px solid var(--airtel-red);
            position: relative;
            overflow: hidden;
        }
        .list-section-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 50px;
            height: 50px;
            background: var(--airtel-red);
            border-bottom-left-radius: 1rem;
            opacity: 0.05;
            transform: rotate(45deg) translate(20px, -20px);
            z-index: 0;
        }
        .list-section-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 1.5rem;
            text-align: left;
        }
        .list-item {
            display: flex;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px dashed var(--border-light);
            transition: background-color 0.2s ease;
        }
        .list-item:last-child {
            border-bottom: none;
        }
        .list-item:hover {
            background-color: rgba(228, 0, 43, 0.05);
            border-radius: 0.5rem;
        }
        .list-item-icon {
            color: var(--airtel-red);
            margin-right: 1rem;
            font-size: 1.2rem;
        }
        .list-item-text {
            color: var(--text-dark);
            font-size: 0.95rem;
            flex-grow: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .list-item-count {
            color: var(--airtel-red);
            font-weight: 700;
            margin-left: 1rem;
            flex-shrink: 0;
        }

        .agent-table-container, .logs-table-container {
            background-color: var(--card-bg);
            border-radius: 1rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            padding: 1.5rem;
            border-left: 8px solid var(--airtel-red);
            position: relative;
            overflow: hidden;
        }
        .agent-table-container::before, .logs-table-container::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 50px;
            height: 50px;
            background: var(--airtel-red);
            border-bottom-left-radius: 1rem;
            opacity: 0.05;
            transform: rotate(45deg) translate(20px, -20px);
            z-index: 0;
        }

        .agent-table, .logs-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        .agent-table th, .agent-table td, .logs-table th, .logs-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border-light);
        }
        .agent-table thead th, .logs-table thead th {
            background-color: #F0F2F5;
            color: var(--text-dark);
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.9rem;
            letter-spacing: 0.05em;
            padding-top: 0.75rem;
            padding-bottom: 0.75rem;
        }
        .agent-table tbody tr, .logs-table tbody tr {
            transition: background-color 0.2s ease;
            cursor: pointer;
        }
        .agent-table tbody tr:hover, .logs-table tbody tr:hover {
            background-color: rgba(228, 0, 43, 0.08);
        }
        .agent-table tbody tr:nth-child(odd), .logs-table tbody tr:nth-child(odd) {
            background-color: var(--card-bg);
        }
        .agent-table tbody tr:nth-child(even), .logs-table tbody tr:nth-child(even) {
            background-color: #FDFDFD;
        }
        .agent-table td:first-child, .logs-table td:first-child { border-top-left-radius: 0.5rem; border-bottom-left-radius: 0.5rem; }
        .agent-table td:last-child, .logs-table td:last-child { border-top-right-radius: 0.5rem; border-bottom-right-radius: 0.5rem; }

        .primary-button {
            background: linear-gradient(90deg, var(--airtel-red), var(--airtel-red-dark));
            color: white;
            font-weight: 700;
            padding: 0.75rem 2rem;
            border-radius: 9999px;
            transition: transform 0.3s ease, box-shadow 0.3s ease, background 0.3s ease;
            box-shadow: 0 4px 15px rgba(228, 0, 43, 0.25);
            position: relative;
            overflow: hidden;
            border: none;
        }
        .primary-button:hover {
            transform: translateY(-2px) scale(1.02);
            box-shadow: 0 6px 20px rgba(228, 0, 43, 0.4);
            background: linear-gradient(90deg, var(--airtel-red-dark), var(--airtel-red));
        }
        .primary-button::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -60%;
            width: 30%;
            height: 200%;
            background: rgba(255, 255, 255, 0.25);
            transform: rotate(30deg);
            transition: all 0.7s cubic-bezier(0.165, 0.84, 0.44, 1);
            opacity: 0;
        }
        .primary-button:hover::after {
            left: 100%;
            opacity: 1;
        }

        .dashboard-footer {
            color: var(--text-muted);
            padding-top: 2rem;
            border-top: 1px dashed var(--border-light);
            margin-top: 3rem;
        }

        /* Styles pour la modale de confirmation */
        .modal {
            display: none; /* Masqué par défaut */
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.6); /* Fond sombre semi-transparent */
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: #fff;
            margin: auto;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            width: 90%;
            max-width: 450px;
            text-align: center;
            animation: fadeInScale 0.3s ease-out;
            position: relative; /* Pour positionner le bouton de fermeture */
        }

        .modal-close-button {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 2rem;
            color: #ccc;
            cursor: pointer;
            transition: color 0.2s ease;
        }

        .modal-close-button:hover {
            color: #888;
        }

        .modal-icon {
            font-size: 3.5rem;
            color: var(--airtel-red);
            margin-bottom: 20px;
            animation: bounceIn 0.6s ease-out;
        }

        .modal-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 15px;
        }

        .modal-message {
            font-size: 1rem;
            color: var(--text-muted);
            margin-bottom: 30px;
            line-height: 1.5;
        }

        .modal-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
        }

        .modal-button {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .modal-button.confirm {
            background-color: var(--airtel-red);
            color: white;
            box-shadow: 0 4px 10px rgba(228, 0, 43, 0.3);
        }

        .modal-button.confirm:hover {
            background-color: var(--airtel-red-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(228, 0, 43, 0.4);
        }

        .modal-button.cancel {
            background-color: #E0E0E0;
            color: #4A4A4A;
            border: 1px solid #D5D5D5;
        }

        .modal-button.cancel:hover {
            background-color: #D5D5D5;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        /* Message Area */
        #dashboard-message-area.success {
            background-color: #d4edda; /* Light green */
            border-left: 4px solid #28a745; /* Darker green */
            color: #155724; /* Even darker green text */
        }

        #dashboard-message-area.error {
            background-color: #f8d7da; /* Light red */
            border-left: 4px solid #dc3545; /* Darker red */
            color: #721c24; /* Even darker red text */
        }

        /* Animations */
        @keyframes fadeInScale {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }

        @keyframes bounceIn {
            0% { transform: scale(0.3); opacity: 0; }
            50% { transform: scale(1.05); opacity: 1; }
            70% { transform: scale(0.9); }
            100% { transform: scale(1); }
        }
    </style>
    <!-- Content Security Policy (CSP) pour mitiger les attaques XSS et l'injection de code -->
    <meta http-equiv="Content-Security-Policy" content="
        default-src 'self';
        script-src 'self' https://cdn.tailwindcss.com;
        style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://fonts.googleapis.com;
        img-src 'self' https://placehold.co;
        font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com;
        connect-src 'self';
        form-action 'self';
        base-uri 'self';
        frame-ancestors 'none'; /* Empêche l'inclusion dans un iframe */
    ">
</head>
<body>
    <div class="container mx-auto p-6 sm:p-10">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-10 dashboard-header p-5 rounded-xl shadow-lg text-white">
            <div class="flex items-center mb-4 sm:mb-0">
                <img src="https://placehold.co/80x80/E4002B/FFFFFF?text=Airtel" alt="Logo Airtel Dashboard" class="h-20 w-20 rounded-full mr-4 border-4 border-white header-logo">
                <h1 class="text-3xl sm:text-5xl font-extrabold tracking-tight">Tableau de Bord <span class="block sm:inline">Visiteurs <span class="font-light">Airtel</span></span></h1>
            </div>
            <a href="?logout=true" class="primary-button text-base mt-4 sm:mt-0">
                <i class="fas fa-sign-out-alt mr-2"></i> Déconnexion
            </a>
        </div>

        <!-- Zone d'affichage des messages (succès/erreur) -->
        <div id="dashboard-message-area" class="hidden p-4 rounded-lg shadow-md mb-8" role="alert">
            <p id="dashboard-message-text" class="font-bold"></p>
        </div>
        
        <?php if (!empty($error_message_db)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-8 rounded-lg shadow-md" role="alert">
                <p class="font-bold text-lg">Erreur de connexion à la base de données :</p>
                <p class="mt-2 text-sm"><?php echo htmlspecialchars($error_message_db, ENT_QUOTES, 'UTF-8'); ?></p>
                <p class="mt-2 text-xs opacity-75">Veuillez vérifier vos configurations de base de données.</p>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 xl:grid-cols-4 gap-8 mb-10">
            <div class="dashboard-card">
                <i class="fas fa-globe-americas dashboard-card-icon"></i>
                <h3 class="dashboard-card-title">Total des Visites</h3>
                <p class="dashboard-card-value"><?php echo number_format($total_visits); ?></p>
                <span class="dashboard-card-label">Nombre total de consultations de votre site</span>
            </div>
             
            <div class="dashboard-card">
                <i class="fas fa-user-friends dashboard-card-icon"></i>
                <h3 class="dashboard-card-title">Visiteurs Uniques</h3>
                <p class="dashboard-card-value"><?php echo number_format($unique_ips); ?></p>
                <span class="dashboard-card-label">Nombre d'adresses IP distinctes</span>
            </div>

            <div class="list-section-card col-span-1 md:col-span-2">
                <i class="fas fa-pager dashboard-card-icon"></i> <h3 class="list-section-title">Top 5 Pages les Plus Visitées</h3>
                <ul class="w-full mt-4 space-y-2">
                    <?php if (!empty($most_visited_pages)): ?>
                        <?php foreach ($most_visited_pages as $page): ?>
                            <li class="list-item">
                                <i class="fas fa-link list-item-icon"></i>
                                <span class="list-item-text" title="<?php echo htmlspecialchars($page['page_visited'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars($page['page_visited'], ENT_QUOTES, 'UTF-8'); ?>
                                </span> 
                                <span class="list-item-count"><?php echo number_format($page['count']); ?></span>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="text-text-muted text-center py-4 text-sm">Aucune donnée disponible sur les pages visitées.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <div class="agent-table-container col-span-full mb-10">
            <i class="fas fa-desktop dashboard-card-icon"></i> <h3 class="list-section-title">Top 5 Agents Utilisateurs Détaillés</h3>
            <div class="overflow-x-auto">
                <table class="agent-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Agent Utilisateur</th>
                            <th class="text-center">Visites</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($top_user_agents)): ?>
                            <?php $i = 1; foreach ($top_user_agents as $ua): ?>
                                <tr class="cursor-pointer" data-modal-target="agentModal" data-user-agent="<?php echo htmlspecialchars($ua['user_agent'], ENT_QUOTES, 'UTF-8'); ?>" data-count="<?php echo htmlspecialchars($ua['count'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <td><?php echo $i++; ?></td>
                                    <td>
                                        <div class="flex items-center">
                                            <i class="fas fa-info-circle text-blue-400 mr-2" title="Cliquer pour plus de détails"></i>
                                            <span class="truncate max-w-xs block" title="<?php echo htmlspecialchars($ua['user_agent'], ENT_QUOTES, 'UTF-8'); ?>">
                                                <?php echo htmlspecialchars($ua['user_agent'], ENT_QUOTES, 'UTF-8'); ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="text-center font-bold text-lg text-airtel-red"><?php echo number_format($ua['count']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-text-muted text-center py-6">Aucune donnée disponible sur les agents utilisateurs.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- NOUVEAU : Logs Détaillés des Visiteurs avec bouton de suppression -->
        <div class="logs-table-container col-span-full mb-10">
            <h3 class="list-section-title flex items-center"><i class="fas fa-list-alt mr-3 dashboard-card-icon"></i> Logs Détaillés des Visiteurs</h3>
            <?php if (empty($site_visitors_logs)): ?>
                <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6 rounded-lg shadow-md" role="alert">
                    <p class="font-bold">Aucun log de visiteur disponible pour l'instant.</p>
                    <p>Le système n'a pas encore enregistré de visites.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="logs-table">
                        <thead>
                            <tr>
                                <th class="py-4 px-6 text-left">ID</th>
                                <th class="py-4 px-6 text-left">Adresse IP</th>
                                <th class="py-4 px-6 text-left">Agent Utilisateur</th>
                                <th class="py-4 px-6 text-left">Page Visitée</th>
                                <th class="py-4 px-6 text-left">Date & Heure</th>
                                <th class="py-4 px-6 text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($site_visitors_logs as $log): ?>
                                <tr id="log-row-<?php echo htmlspecialchars($log['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <td class="py-3 px-6 text-left font-medium"><?php echo htmlspecialchars($log['id'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="py-3 px-6 text-left"><?php echo htmlspecialchars($log['ip_address'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="py-3 px-6 text-left truncate max-w-xs" title="<?php echo htmlspecialchars($log['user_agent'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($log['user_agent'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="py-3 px-6 text-left truncate max-w-xs" title="<?php echo htmlspecialchars($log['page_visited'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($log['page_visited'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="py-3 px-6 text-left whitespace-nowrap text-gray-500 text-xs"><?php echo htmlspecialchars($log['timestamp'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="py-3 px-6 text-left">
                                        <button onclick="showDeleteLogModal(<?php echo htmlspecialchars($log['id'], ENT_QUOTES, 'UTF-8'); ?>)"
                                                class="bg-red-500 text-white py-1 px-3 rounded-md text-sm font-semibold hover:bg-red-600 transition duration-150 ease-in-out flex items-center justify-center">
                                            <i class="fas fa-trash-alt mr-1"></i> Supprimer
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>


        <footer class="text-center dashboard-footer text-xs">
            <p>&copy; <?php echo date("Y"); ?> Airtel. Tous droits réservés.</p>
            <p class="mt-1">Propulsé par une analyse de données sécurisée.</p>
        </footer>
    </div>

    <!-- Modale de Détails de l'Agent Utilisateur -->
    <div id="agentModal" class="fixed inset-0 bg-gray-900 bg-opacity-75 flex items-center justify-center z-50 hidden">
        <div class="bg-white p-8 rounded-lg shadow-xl max-w-lg w-full relative">
            <button class="modal-close-button" onclick="document.getElementById('agentModal').classList.add('hidden');">&times;</button>
            <h3 class="text-2xl font-bold mb-4 text-airtel-red">Détails de l'Agent Utilisateur</h3>
            <div id="modalContent">
                <p class="mb-2"><strong class="text-gray-700">Agent :</strong> <span id="modalUserAgent" class="font-mono text-sm text-gray-800 break-all"></span></p>
                <p><strong class="text-gray-700">Nombre de visites :</strong> <span id="modalCount" class="font-bold text-airtel-red"></span></p>
            </div>
            <div class="mt-6 text-right">
                <button class="modal-button cancel" onclick="document.getElementById('agentModal').classList.add('hidden');">Fermer</button>
            </div>
        </div>
    </div>

    <!-- Modale de Confirmation de Suppression de Log -->
    <div id="deleteLogConfirmationModal" class="modal">
        <div class="modal-content">
            <button class="modal-close-button" onclick="hideDeleteLogModal()">&times;</button>
            <i class="fas fa-exclamation-triangle modal-icon"></i>
            <h3 class="modal-title">Confirmer la Suppression du Log</h3>
            <p class="modal-message" id="deleteLogModalMessage">Êtes-vous sûr de vouloir supprimer cette entrée de log ? Cette action est irréversible.</p>
            <div class="modal-buttons">
                <button id="confirmDeleteLogBtn" class="modal-button confirm">Oui, Supprimer</button>
                <button id="cancelDeleteLogBtn" class="modal-button cancel">Annuler</button>
            </div>
        </div>
    </div>


    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Pour la modale des agents utilisateurs (existante)
            const agentTableRows = document.querySelectorAll('.agent-table tbody tr[data-modal-target]');
            const agentModal = document.getElementById('agentModal');
            const modalUserAgent = document.getElementById('modalUserAgent');
            const modalCount = document.getElementById('modalCount');

            agentTableRows.forEach(row => {
                row.addEventListener('click', () => {
                    const userAgent = row.dataset.userAgent;
                    const count = row.dataset.count;

                    modalUserAgent.textContent = userAgent;
                    modalCount.textContent = count;
                     
                    agentModal.classList.remove('hidden');
                });
            });

            agentModal.addEventListener('click', (e) => {
                if (e.target === agentModal) {
                    agentModal.classList.add('hidden');
                }
            });

            // --- NOUVEAU : Fonctions pour les messages du tableau de bord ---
            function showDashboardMessage(message, type) {
                const messageArea = document.getElementById('dashboard-message-area');
                const messageText = document.getElementById('dashboard-message-text');

                messageArea.classList.remove('hidden', 'success', 'error');
                messageArea.classList.add(type);
                messageText.textContent = message;

                // Cacher automatiquement après 5 secondes
                setTimeout(() => {
                    hideDashboardMessage();
                }, 5000);
            }

            function hideDashboardMessage() {
                const messageArea = document.getElementById('dashboard-message-area');
                messageArea.classList.add('hidden');
                messageArea.classList.remove('success', 'error');
                document.getElementById('dashboard-message-text').textContent = '';
            }


            // --- NOUVEAU : Pour la modale de suppression de log ---
            const deleteLogConfirmationModal = document.getElementById('deleteLogConfirmationModal');
            const deleteLogModalMessage = document.getElementById('deleteLogModalMessage');
            const confirmDeleteLogBtn = document.getElementById('confirmDeleteLogBtn');
            const cancelDeleteLogBtn = document.getElementById('cancelDeleteLogBtn');

            let currentLogIdToDelete = null; // Variable pour stocker l'ID du log à supprimer

            // Fonction pour afficher la modale de confirmation de suppression
            window.showDeleteLogModal = function(logId) {
                currentLogIdToDelete = logId;
                deleteLogModalMessage.textContent = `Êtes-vous sûr de vouloir supprimer l'entrée de log (ID: ${logId}) ? Cette action est irréversible.`;
                deleteLogConfirmationModal.style.display = 'flex'; // Utiliser flex pour centrer
            };

            // Fonction pour masquer la modale de confirmation
            function hideDeleteLogModal() {
                deleteLogConfirmationModal.style.display = 'none';
                currentLogIdToDelete = null;
            }

            // Gestionnaire de clic sur le bouton Confirmer de la modale de suppression
            confirmDeleteLogBtn.onclick = function() {
                if (currentLogIdToDelete !== null) {
                    performDeleteLog(currentLogIdToDelete);
                }
                hideDeleteLogModal();
            };

            // Gestionnaire de clic sur le bouton Annuler de la modale de suppression
            cancelDeleteLogBtn.onclick = function() {
                hideDeleteLogModal();
            };

            // Fermer la modale si l'utilisateur clique en dehors du contenu
            window.onclick = function(event) {
                if (event.target == deleteLogConfirmationModal) {
                    hideDeleteLogModal();
                }
                // Si vous avez d'autres modales, ajoutez-les ici
            };

            // Fonction réelle pour effectuer la suppression de log après confirmation
            function performDeleteLog(logId) {
                console.log(`Tentative de suppression du log avec l'ID : ${logId}`);
                fetch('log_visit.php', { 
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=delete_log&id=' + logId // Envoyer l'action et l'ID
                })
                .then(response => {
                    console.log('Réponse brute reçue :', response);
                    if (!response.ok) {
                        // Log des détails de l'erreur HTTP
                        console.error(`Erreur HTTP ! statut : ${response.status}`);
                        return response.text().then(text => {
                            console.error('Texte de la réponse :', text);
                            throw new Error(`Le serveur a répondu avec un statut non-OK : ${response.status}`);
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Données JSON parsées :', data);
                    if (data.status === 'success') {
                        // Supprimer la ligne du tableau après succès
                        const row = document.getElementById('log-row-' + logId);
                        if (row) {
                            row.remove();
                        }
                        showDashboardMessage(data.message, 'success');
                        // La page ne se rechargera pas automatiquement pour une meilleure fluidité,
                        // mais vous pouvez décommenter la ligne ci-dessous si les statistiques en haut
                        // doivent être mises à jour immédiatement après chaque suppression.
                        // window.location.reload(); 
                    } else {
                        showDashboardMessage('Erreur : ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Erreur lors de la requête de suppression de log :', error);
                    showDashboardMessage('Une erreur s\'est produite lors de la suppression du log. Vérifiez la console pour plus de détails.', 'error');
                });
            }
        });
    </script>
</body>
</html>
