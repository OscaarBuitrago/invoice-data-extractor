FROM php:8.4-cli-bookworm

# System dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    nodejs \
    npm \
    git \
    zip \
    unzip \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libxml2-dev \
    libonig-dev \
    libcurl4-openssl-dev \
    libicu-dev \
    && rm -rf /var/lib/apt/lists/*

# PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        bcmath \
        gd \
        mbstring \
        pdo \
        pdo_mysql \
        xml \
        zip \
        intl \
        pcntl

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Composer deps (layer cache)
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts

# NPM deps (layer cache)
COPY package.json package-lock.json ./
RUN npm ci

# Application
COPY . .

# Build frontend
RUN npm run build

# Create required Laravel directories
RUN mkdir -p bootstrap/cache \
    storage/app/private \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    && chmod -R 775 storage bootstrap/cache

# Finalize composer
RUN composer dump-autoload --optimize

RUN chmod +x start.sh worker.sh

EXPOSE 8000

CMD ["bash", "start.sh"]
