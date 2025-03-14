FROM php

WORKDIR /server

RUN apt update
RUN apt install -y libbrotli-dev
RUN pecl install swoole

RUN touch /usr/local/etc/php/php.ini
RUN echo "extension=swoole.so" > /usr/local/etc/php/php.ini

COPY config config
COPY web web
COPY . .

CMD ["php", "server.php"]
