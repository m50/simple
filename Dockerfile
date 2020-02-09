FROM php:7.4-cli-alpine

MAINTAINER Marisa Clardy <marisa@clardy.eu>

COPY ./simple.phar /simple

RUN chmod +x /simple

WORKDIR /app

CMD [ "php", "/simple" ]
