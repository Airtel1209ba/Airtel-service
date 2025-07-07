# Étape 1: Utiliser une image PHP officielle avec Apache comme base
FROM php:8.2-apache

# Étape 2: Définir le répertoire de travail
WORKDIR /var/www/html/

# Étape 3: Copier tous les fichiers de l'application dans le répertoire web d'Apache
COPY . /var/www/html/

# Étape 4: Installer le client PostgreSQL (psql) ET les librairies de développement PostgreSQL
# C'est la ligne clé pour résoudre l'erreur : ajout de 'libpq-dev'
RUN apt-get update && \
    apt-get install -y postgresql-client libpq-dev && \  # <-- AJOUT DE 'libpq-dev' ICI
    docker-php-ext-install pdo pdo_pgsql && \
    rm -rf /var/lib/apt/lists/*

# Pour MySQL, ce serait :
# RUN apt-get update && \
#     apt-get install -y default-mysql-client libmysqlclient-dev && \ # <-- AJOUT DE 'libmysqlclient-dev'
#     docker-php-ext-install pdo pdo_mysql && \
#     rm -rf /var/lib/apt/lists/*

# Étape 5: Configurer les permissions pour Apache
RUN chown -R www-data:www-data /var/www/html \
    && a2enmod rewrite

# Étape 6: Exposer le port par défaut d'Apache
EXPOSE 80

# Étape 7: Commande de démarrage principale du conteneur
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
