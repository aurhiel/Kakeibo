FROM php:8.2-fpm
WORKDIR "/application"

# 1. Force Timezone to "Europe/Paris"
ENV TZ=Europe/Paris
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# 1. Install system tools
RUN apt-get update && apt-get install -y \
        sudo \
        curl \
        git \
        gnupg \
        unzip \
        libicu-dev  \
        libzip-dev \
        apt-transport-https && \
    apt-get clean; rm -rf /var/lib/apt/lists/*

# 2. PHP Extensions
RUN docker-php-ext-install pdo pdo_mysql zip intl

# 3. Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer && \
    chmod +x /usr/local/bin/composer

# 4. Create docker sudo user
RUN useradd -ms /bin/bash docker && adduser docker sudo && \
    echo "docker ALL=(ALL) NOPASSWD:ALL" >> /etc/sudoers

USER docker
