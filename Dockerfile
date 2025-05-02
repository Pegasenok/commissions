FROM php:8.4-cli-alpine

# Install required packages for Xdebug
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS linux-headers \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apk del .build-deps

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
WORKDIR /app
COPY composer.json composer.lock ./
RUN --mount=type=cache,target=/root/.composer/cache composer install --no-scripts --no-autoloader --dev

COPY ./src ./src
COPY ./tests ./tests
COPY ./phpunit.xml ./phpunit.xml
COPY ./var ./var
COPY ./app.php ./app.php

# Generate optimized autoloader
RUN composer dump-autoload --optimize
ENV XDEBUG_MODE=coverage
# Command to run when container starts
CMD ["./vendor/bin/phpunit", "tests"]

