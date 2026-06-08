FROM php:8.2-cli

# Install MySQL PDO extensions so it can connect to Aiven
RUN docker-php-ext-install pdo pdo_mysql

# Copy your project files into the container
WORKDIR /app
COPY . /app

# Run the PHP server on the port Render requires
CMD php -S 0.0.0.0:$PORT
