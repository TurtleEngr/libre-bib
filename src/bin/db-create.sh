#!/bin/bash

# --------------------
# Validate

for i in \
        ${cgDbPassCache} \
        ${cgDirCache}/.root.pass \
        ${cgDirCache)}.admin.pass \
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
show databases;

create user 'admin'@'localhost' identified by '$cAdminPass';
grant all privileges on *.* to 'admin'@localhost;

create user '$cgDbUser'@'localhost' identified by '$cUserPass';
grant all privileges on $cgDbName.* to '$cgDbUser'@localhost;

flush privileges;

select user from mysql.user;
show grants for 'root'@localhost;
show grants for 'admin'@localhost;
show grants for '$cgDbUser'@localhost;

quit;
EOF

exit

# --------------------
# Setup and secure the DB

echo "root pass: $cRootPass"
mysql_secure_installation

# --------------------
# Create DB and users

sudo mysql -P ${cgDbPortLocal} -u root --password=$cRootPass \
    -h $(cgDbHost) <$cgDirCache/db-create.cmd

exit
rm $cgDirCache/db-create.cmd
