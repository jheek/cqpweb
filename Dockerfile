FROM debian:latest

ENV DEBIAN_FRONTEND noninteractive

RUN apt-get update

RUN apt-get install -y apache2 mysql-client \
    mysql-server php5 php5-mysql php5-dev php5-gd \
    php5-memcache php5-pspell php5-snmp snmp php5-xmlrpc \
    libapache2-mod-php5 php5-cli

RUN apt-get install -y r-base
RUN apt-get install -y nano sendmail

RUN mkdir -p /var/lock/apache2 /var/run/apache2

COPY ./src /tmp/cwb

WORKDIR /tmp/cwb

RUN mkdir -p /corpora/data/ && \
    mkdir -p /usr/local/share/cwb/registry/ && \
    mv DemoCorpus/data/ /corpora/data/dickens && \
    mv DemoCorpus/registry/dickens /usr/local/share/cwb/registry/dickens && \
    cp -r CQPweb/ /var/www/html/cqp
    ./install-cwb.sh

EXPOSE 80

php -r "mail('jheek@icloud.com','itworks...','Yes it does work.');"
echo "My test email being sent from sendmail" | /usr/sbin/sendmail jheek@icloud.com