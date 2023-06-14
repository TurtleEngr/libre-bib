#!/usr/bin/env bash

pBase=${1:-lo}
pNum=${2:-2}

. /opt/libre-bib/etc/conf.env
. ./conf.env

if [[ "$cgUseRemote" = "true" ]]; then
    tPort=$cgDbPortRemote
else
    tPort=$cgDbPortLocal
fi

echo 'select TABLE_NAME from information_schema.tables where TABLE_NAME like' "\"${pBase}_%\";" >tmp/get.cmd
if [[ "$cgDebug" = "true" ]]; then
    cat tmp/get.cmd
fi

cat tmp/get.cmd | \
    mysql -N --batch --raw -P $tPort -u $cgDbUser --password=$(cat $cgDbPassCache) -h $cgDbHost $cgDbName >tmp/get.out 2>&1
if [[ $? -ne 0 ]]; then
    echo "Error: DB problem:"
    cat tmp/get.out
    exit 1
fi
if [[ ! -s tmp/get.out ]]; then
    if [[ "$cgDebug" = "true" ]]; then
        echo "No tables found"
    fi
    exit 0
fi
if [[ "$cgDebug" = "true" ]]; then
    cat tmp/get.out
fi

(( ++pNum ))
for i in $(sort -r tmp/get.out | tail -n +$pNum); do
    echo "drop table \`$i\`;"
done >tmp/drop.cmd
if [[ "$cgDebug" = "true" ]]; then
    cat tmp/drop.cmd
fi

# Drop the oldest pNum tables
if [[ -s tmp/drop.cmd ]]; then
    cat tmp/drop.cmd | \
    mysql -N --batch --raw -P $tPort -u $cgDbUser --password=$(cat $cgDbPassCache) -h $cgDbHost $cgDbName >tmp/drop.out 2>&1
    cat tmp/drop.out
fi

exit 0
