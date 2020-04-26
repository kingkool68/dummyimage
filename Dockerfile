FROM php:7.4-apache

# install and enable mod_rewrite
RUN a2enmod rewrite
RUN echo "LoadModule rewrite_module modules/mod_rewrite.so" >> /etc/apache2/apache2.conf

# install gd for image generation
RUN apt-get update -y && apt-get install -y \
  libpng-dev \
  libjpeg-dev \
  libfreetype6-dev

RUN docker-php-ext-configure gd \
  --with-freetype \
  --with-jpeg

RUN docker-php-ext-install gd

COPY . /var/www/html
