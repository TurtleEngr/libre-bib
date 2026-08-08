#!/bin/bash
# Setup the sample directory with an intial state.

# Assumption, in: src/test/sample/

if [[ ! -f sample-doc.odt ]]; then
    echo "Error: not in src/test/sample dir"
    exit 1
fi

# Set location of app and bin dirs
. ./bootstrap.sh -s

bib setup-bib
sed -i '
  s/ *#cgDebug=false/cgDebug=true/
  s/ *#export cgDirLibreofficeConf/export cgDirLibreofficeConf/
  s/ *#cgDbPassHint="b4n"/cgDbPassHint="demo"/
  s/ *#cgDocFile="example.odt"/cgDocFile="test-doc.odt"/
' conf.env

bib setup-dir

. $cgDirApp/etc/conf.env
. ./conf.env
# Manually initialize passwords, so there are no 'read' prompts
echo demo | caesar 13 >$cgDbPassCache
echo demo | caesar 13 >$cgDirCache/.root.pass
echo demo | caesar 13 >$cgDirCache/.admin.pass
# This assumes first time install of mariadb. Only on a test server,
# reset with:
#    sudo systemctl stop mysql
#    sudo rm -rf  /var/lib/mysql/*
#    sudo -u mysql mysql_install_db
#    sudo systemctl start mysql
bib setup-db
