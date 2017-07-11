FROM debian:7

ENV DEBIAN_FRONTEND noninteractive

RUN apt-get update

RUN apt-get install -y apache2 mysql-client \
    mysql-server php5 php5-mysql php5-dev php5-gd \
    php5-memcache php5-pspell php5-snmp snmp php5-xmlrpc \
    libapache2-mod-php5 php5-cli r-base nano sendmail

RUN mkdir -p /var/lock/apache2 /var/run/apache2

COPY ./src /tmp/cwb

WORKDIR /tmp/cwb

VOLUME /var/lib/mysql /corpora /usr/local/share/cwb/registry /cqp

RUN mkdir -p /tmp/cqp && chmod 777 /tmp/cqp && \
    ./install-cwb.sh && \
    cd /tmp/cwb/CWB-perl && \
    perl Makefile.PL && \
    make && make install && \
    cp /tmp/cwb/php.ini /etc/php5/apache2/php.ini

EXPOSE 80
