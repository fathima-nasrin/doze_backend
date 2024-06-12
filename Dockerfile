FROM laravelsail/php81-composer:latest

RUN apt-get update -y && \
    apt-get install -y libpng-dev zlib1g-dev libicu-dev g++ && \
    rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure intl && \
    docker-php-ext-install exif gd intl zip

# Copy the Laravel application code to the container
COPY . /var/www/html

# Set the working directory
WORKDIR /var/www/html

# Install Laravel dependencies
RUN composer install

# Set permissions for Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Expose port 8000 and start the Laravel server
EXPOSE 8000
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]

