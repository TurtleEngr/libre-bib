#!/bin/bash

# --------------------
if [[ "$1" != "-c" ]]; then
    cat <<EOF
Usage:
    db-create.sh -c

This script works best with a "new" mysql. It might work if there are
different values defined in conf.env (because errors are ignored).

To reset mysql to the base install state, do this:"

    sudo systemctl stop mysql
    sudo rm -rf  /var/lib/mysql/*
    sudo -u mysql mysql_install_db
    sudo systemctl start mysql

Change a user's DB password:
    alter user 'USERNAME'@'localhost' IDENTIFIED BY 'NEW-PASS';
    flush privileges;
EOF
    exit 1
fi

# --------------------
# Validate

for i in \
        ${cgDbPassCache} \
        ${cgDirCache}/.root.pass \
        ${cgDirCache}/.admin.pass \
    ; do
    if [[ ! -e $i ]]; then
        echo "Error: could not find $i [$LINENO]"
    fi
done

# --------------------
# Get passwords

cUserPass=$(cat ${cgDbPassCache} | rot13)
cRootPass=$(cat ${cgDirCache}/.root.pass | rot13)
cAdminPass=$(cat ${cgDirCache}/.admin.pass | rot13)

# --------------------
# Script to create DB and users

cat <<EOF >$cgDirCache/db-create.cmd
create database $cgDbName;

create user 'admin'@'localhost' identified by '$cAdminPass';
grant all privileges on *.* to 'admin'@'localhost';

create user '$cgDbUser'@'localhost' identified by '$cUserPass';
grant all privileges on $cgDbName.* to '$cgDbUser'@'localhost';

flush privileges;

show databases;
select host,user from mysql.user;
show grants for 'root'@${cgDbHost};
show grants for 'admin'@${cgDbHost};
show grants for '$cgDbUser'@${cgDbHost};
EOF

# --------------------
# Setup and secure the DB

echo "root pass: $cRootPass"
sudo mysql_secure_installation

# --------------------
# Create DB and users

sudo mysql -f -P ${cgDbPortLocal} -u root --password=$cRootPass \
     -h ${cgDbHost} <$cgDirCache/db-create.cmd
tErr=$?
if [[ "$cgDebug" = "false" ]]; then
    rm $cgDirCache/db-create.cmd
fi
exit $tErr
