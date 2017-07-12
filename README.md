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

Create a directory for uploaded files
`
mkdir /cqp/upload
`

This will setup the database
`
cd /var/www/CQPweb/bin
php autosetup.php
`


Mappings to the host file system are given in `docker-compose.yml`.

A database dump can be created for backup and migration purposes:
`
mysqldump cqpweb > /cqp/dbdump.sql
`
This command should be executed within the container. The dump is stored in the shared folderr `cqp`.