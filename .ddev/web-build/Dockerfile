# Custom Dockerfile for nr_saml_auth DDEV environment

# Create directories for TYPO3 installations
RUN mkdir -p /var/www/html/v12 /var/www/html/v13 \
    && chown -R www-data:www-data /var/www/html

# Create symlink location for extension
RUN mkdir -p /var/www/nr_saml_auth \
    && chown -R www-data:www-data /var/www/nr_saml_auth
