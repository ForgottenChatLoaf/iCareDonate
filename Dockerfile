# iCareDonate — Render Web Service Dockerfile
# PHP 8.2 + Apache, configured for Render's dynamic PORT assignment.

FROM php:8.2-apache

# ── System Dependencies ───────────────────────────────────────────────────────
RUN apt-get update && apt-get install -y --no-install-recommends \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        zip \
        unzip \
        curl \
    && docker-php-ext-install pdo pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

# ── Apache: enable mod_rewrite ────────────────────────────────────────────────
RUN a2enmod rewrite

# ── Apache: virtual host — serve from /var/www/html ──────────────────────────
# AllowOverride All enables .htaccess rewrites.
# The PORT env-var substitution is done in the entrypoint script below.
RUN { \
    echo '<VirtualHost *:${APACHE_PORT}>'; \
    echo '  ServerName iCareDonate'; \
    echo '  DocumentRoot /var/www/html'; \
    echo '  DirectoryIndex login.php index.php index.html'; \
    echo '  <Directory /var/www/html>'; \
    echo '    Options -Indexes +FollowSymLinks'; \
    echo '    AllowOverride All'; \
    echo '    Require all granted'; \
    echo '  </Directory>'; \
    echo '  # Block direct web access to sensitive directories'; \
    echo '  <DirectoryMatch "^/var/www/html/(includes|config|database|archived|\.git)">'; \
    echo '    Require all denied'; \
    echo '  </DirectoryMatch>'; \
    echo '  # Block setup scripts in production'; \
    echo '  <Files "setup_admin.php">'; \
    echo '    Require all denied'; \
    echo '  </Files>'; \
    echo '  ErrorLog ${APACHE_LOG_DIR}/error.log'; \
    echo '  CustomLog ${APACHE_LOG_DIR}/access.log combined'; \
    echo '</VirtualHost>'; \
} > /etc/apache2/sites-available/000-default.conf

# ── PHP config: turn off error display in production ─────────────────────────
RUN { \
    echo 'display_errors = Off'; \
    echo 'log_errors = On'; \
    echo 'error_log = /dev/stderr'; \
    echo 'session.cookie_httponly = 1'; \
    echo 'session.cookie_samesite = None'; \
    echo 'session.cookie_secure = 1'; \
    echo 'expose_php = Off'; \
} > /usr/local/etc/php/conf.d/icaredonate.ini

# ── Application files ─────────────────────────────────────────────────────────
WORKDIR /var/www/html
COPY . .

# Remove files that must never be web-accessible
RUN rm -f /var/www/html/Dockerfile \
          /var/www/html/.dockerignore

# Fix file permissions for Apache
RUN chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && find /var/www/html -type f -exec chmod 644 {} \;

# ── Entrypoint: Render passes PORT as an env variable (default 10000) ─────────
# Apache must listen on that port, not the default 80.
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 10000

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["apache2-foreground"]
