## Steps to setup docker container

Starts and builds the container (if necessary)
`
docker-compose up -d --build cwb
`

Enter the docker machine with an interactive bash session
`
docker exec -it cqpweb_cwb_1 bash
`

This will setup the database and user
`
cat /tmp/cwb/mysql_setup | mysql
`

This will reset the databse (This removes all data!)
`
cat /tmp/cwb/mysql_clear | mysql
`

Copy CQPweb to the apache server
`
cp -r /tmp/cwb/CQPWeb/ /var/www/
cp cqp-conf /var/www/CQPweb/lib/config.inc.php
`

Create a directory for uploaded files
`
mkdir /cqp/upload
`

This will setup the database
`
cd /var/www/CQPweb/bin
php autosetup.php
`

Install demo corpus
`
cp -r DemoCorpus/data/ /corpora/data/dickens
cp DemoCorpus/registry/dickens /usr/local/share/cwb/registry/dickens
`


