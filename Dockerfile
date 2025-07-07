# Utilise une image PHP officielle comme base
FROM php:8.2-apache

# Copie le code source de l'application dans le répertoire web par défaut d'Apache
COPY . /var/www/html/

# Configure les permissions (important pour PHP)
RUN chown -R www-data:www-data /var/www/html \
    && a2enmod rewrite

# Expose le port 80 pour le serveur web Apache
EXPOSE 80

# Commande de démarrage (Apache est déjà démarré par l'image de base)
CMD ["apache2-foreground"]
