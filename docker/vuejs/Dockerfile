FROM node:24.12.0-alpine

WORKDIR /app

COPY docker/vuejs/*.sh /usr/local/bin/

COPY vuejs-app /app

RUN chmod +x /usr/local/bin/*.sh

EXPOSE 5173

