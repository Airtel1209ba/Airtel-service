FROM php:8.2-apache
WORKDIR /var/www/html/
COPY . /var/www/html/
RUN apt-get update && apt-get install -y postgresql-client libpq-dev && docker-php-ext-install pdo pdo_pgsql && rm -rf /var/lib/apt/lists/*
RUN chown -R www-data:www-data /var/www/html && a2enmod rewrite
EXPOSE 80
CMD bash -c "while ! psql \"$DATABASE_URL\" -c '\l' > /dev/null 2>&1; do sleep 5; done; psql \"$DATABASE_URL\" -f setup_db.sql; apache2-foreground"
