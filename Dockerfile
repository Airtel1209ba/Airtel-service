# Étape 1: Utiliser une image PHP officielle avec Apache comme base
# Cette image inclut Apache et PHP-FPM pré-configurés.
FROM php:8.2-apache

# Étape 2: Définir le répertoire de travail
# Cela rend les chemins relatifs plus faciles dans les commandes suivantes.
WORKDIR /var/www/html/

# Étape 3: Copier tous les fichiers de l'application dans le répertoire web d'Apache
# Le '.' représente le répertoire courant sur votre machine locale (là où se trouve le Dockerfile),
# et /var/www/html/ est le répertoire racine du serveur web à l'intérieur du conteneur.
COPY . /var/www/html/

# Étape 4: Installer le client PostgreSQL (psql) et l'extension PHP pour PostgreSQL
# Ceci est nécessaire pour que le conteneur puisse interagir avec une base de données PostgreSQL.
# Si vous utilisiez MySQL, vous installeriez 'mysql-client' et 'pdo_mysql'.
RUN apt-get update && \
    apt-get install -y postgresql-client && \
    docker-php-ext-install pdo pdo_pgsql && \
    rm -rf /var/lib/apt/lists/*

# Pour MySQL, ce serait :
# RUN apt-get update && \
#     apt-get install -y default-mysql-client && \
#     docker-php-ext-install pdo pdo_mysql && \
#     rm -rf /var/lib/apt/lists/*


# Étape 5: Configurer les permissions pour Apache
# S'assure que le serveur web (exécutant sous l'utilisateur 'www-data' par défaut)
# a les droits de lecture/écriture sur les fichiers de l'application.
# Active également le module mod_rewrite, souvent nécessaire pour les applications web.
RUN chown -R www-data:www-data /var/www/html \
    && a2enmod rewrite

# Étape 6: Exposer le port par défaut d'Apache
# Indique que le conteneur écoute sur le port 80 pour le trafic HTTP.
EXPOSE 80

# Étape 7: Commande de démarrage principale du conteneur
# Cette commande sera exécutée au lancement du conteneur.
# Elle combine plusieurs actions :
# 1. Attendre que la base de données soit accessible. Ceci est crucial pour la robustesse.
#    Nous utilisons un simple 'while' loop pour pinger la DB.
# 2. Exécuter le script SQL 'setup_db.sql' pour créer les tables.
#    'psql -d "$DATABASE_URL" -f setup_db.sql' se connecte et exécute le fichier SQL.
#    La variable $DATABASE_URL est fournie par Render.
#    '2>/dev/null || true' : redirige les erreurs vers /dev/null et s'assure que la commande réussit
#    même si elle échoue (afin de ne pas arrêter le conteneur si la table existe déjà).
# 3. Lancer le processus Apache en arrière-plan, en le gardant au premier plan du conteneur.
#    C'est la commande standard pour démarrer Apache dans cette image.
CMD bash -c " \
    echo 'Waiting for database to be ready...'; \
    while ! psql \"$DATABASE_URL\" -c '\l' > /dev/null 2>&1; do \
        echo 'Database is unavailable - sleeping'; \
        sleep 5; \
    done; \
    echo 'Database is ready. Running migrations...'; \
    psql \"$DATABASE_URL\" -f setup_db.sql; \
    echo 'Migrations finished. Starting Apache...'; \
    apache2-foreground \
"

# Pour MySQL, la CMD serait un peu différente pour la connexion :
# CMD bash -c " \
#     echo 'Waiting for database to be ready...'; \
#     while ! mysql -h \"$DB_HOST\" -P \"$DB_PORT\" -u \"$DB_USER\" -p\"$DB_PASSWORD\" -e 'SELECT 1' > /dev/null 2>&1; do \
#         echo 'Database is unavailable - sleeping'; \
#         sleep 5; \
#     done; \
#     echo 'Database is ready. Running migrations...'; \
#     mysql -h \"$DB_HOST\" -P \"$DB_PORT\" -u \"$DB_USER\" -p\"$DB_PASSWORD\" \"$DB_NAME\" < setup_db.sql; \
#     echo 'Migrations finished. Starting Apache...'; \
#     apache2-foreground \
# "
