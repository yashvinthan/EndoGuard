FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libcurl4-openssl-dev \
    libonig-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pgsql \
    mbstring \
    curl

# Enable Apache mod_rewrite
RUN a2enmod rewrite headers

# Set the document root to /var/www/html
ENV APACHE_DOCUMENT_ROOT /var/www/html
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Set working directory
WORKDIR /var/www/html

# Expose port 80
EXPOSE 80
