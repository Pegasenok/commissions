FROM php:8.4-cli-alpine

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
WORKDIR /app
COPY composer.json composer.lock ./
RUN --mount=type=cache,target=/root/.composer/cache composer install --no-scripts --no-autoloader

COPY ./src ./src
COPY ./tests ./tests
COPY ./phpunit.xml ./phpunit.xml
COPY ./var ./var
COPY ./app.php ./app.php

RUN composer dump-autoload --optimize
CMD ["sh"]

