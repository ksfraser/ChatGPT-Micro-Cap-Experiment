# Dockerfile for ChatGPT-Micro-Cap-Experiment
# - Installs PHP, Composer, Python, and required extensions
# - Allows connection to external MySQL and MQ servers
# - Optionally clones the GitHub repo during build

FROM php:7.3-cli

# Install system dependencies
RUN apt-get update && \
    apt-get install -y git unzip libzip-dev libpng-dev libonig-dev libxml2-dev \
    python3 python3-pip python3-venv default-mysql-client && \
    docker-php-ext-install pdo_mysql

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Optionally clone the repo (uncomment if you want to build from GitHub)
ARG REPO_URL=https://github.com/ksfraser/ChatGPT-Micro-Cap-Experiment.git
RUN git clone $REPO_URL .

# Copy local code (if not cloning)
#COPY . .

# Install PHP dependencies
RUN composer install --no-interaction --no-scripts --prefer-dist

# Install Python dependencies (if any)
RUN pip3 install -r requirements.txt

# Expose ports if needed (not required for CLI)
EXPOSE 8001

# Entrypoint (override as needed)
CMD ["php", "-v"]
