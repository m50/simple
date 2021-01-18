FROM php:8.0-cli-alpine

MAINTAINER Marisa Clardy <marisa@clardy.eu>

COPY ./simple.phar /simple

RUN chmod +x /simple && cp /simple /bin/simple

WORKDIR /app

CMD [ "php", "/simple" ]
