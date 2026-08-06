#!/bin/bash

# --------------------
if [[ "$1" != "-c" ]]; then
    cat <<EOF
Usage:
    db-create.sh -c
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
create user 'admin'@'127.0.0.1' identified by '$cAdminPass';
create user 'admin'@'${cgDbHost}' identified by '$cAdminPass';
grant all privileges on *.* to 'admin'@'localhost';
grant all privileges on *.* to 'admin'@127.0.0.1;
grant all privileges on *.* to 'admin'@'${cgDbHost}';

create user 'root'@'localhost' identified by '$cRootPass';
create user 'root'@'127.0.0.1' identified by '$cRootPass';
create user 'root'@'${cgDbHost}' identified by '$cRootPass';
grant all privileges on *.* to 'root'@'localhost';
grant all privileges on *.* to 'root'@'127.0.0.1';
grant all privileges on *.* to 'root'@'${cgDbHost}';

create user '$cgDbUser'@'localhost' identified by '$cUserPass';
create user '$cgDbUser'@'127.0.0.1' identified by '$cUserPass';
create user '$cgDbUser'@'${cgDbHost}' identified by '$cUserPass';
grant all privileges on $cgDbName.* to '$cgDbUser'@'$localhost';
grant all privileges on $cgDbName.* to '$cgDbUser'@'127.0.0.1';
grant all privileges on $cgDbName.* to '$cgDbUser'@'${cgDbHost}';

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
