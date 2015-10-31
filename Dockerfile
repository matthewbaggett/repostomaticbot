FROM phusion/baseimage:latest
MAINTAINER Matthew Baggett <matthew@baggett.me>

CMD ["/sbin/my_init"]

# Install base packages
ENV DEBIAN_FRONTEND noninteractive
RUN apt-get update && \
    apt-get -yq install \
        nano \
        libapache2-mod-php5 \
        php5-mysql \
        php5-gd \
        php5-curl \
        php-pear \
        php5-dev \
        php-apc && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

RUN sed -i "s/variables_order.*/variables_order = \"EGPCS\"/g" /etc/php5/apache2/php.ini
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN mkdir -p /app
ADD . /app
ADD telegram.key /app/telegram.key
RUN cat /app/telegram.key

# Run Composer
RUN cd /app && composer install

WORKDIR /app

# Add startup scripts
RUN mkdir /etc/service/repost-o-matic
ADD docker/run.repost-o-matic.sh /etc/service/repost-o-matic/run
RUN chmod +x /etc/service/*/run
