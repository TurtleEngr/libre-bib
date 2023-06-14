#!/usr/bin/env bash

# Expected pBase values:
# all - do all of these:
# $cgDbLo
# $cgDbBib
# $cgDbLib

if [[ -z "$cgDirBackup" ]]; then
    . /opt/libre-bib/etc/conf.env
    if [[ -x $cgDirConf/conf.env ]]; then
        . $cgDirConf/conf.env
    fi
    . ./conf.env
fi

pBase=$1
if [[ -z "$pBase" ]]; then
    echo "Error: missing pBase"
    exit 1
fi

pNum=${2:-$cgBackupNum}
if [[ $pNum -lt 2 ]]; then
    pNum=2
fi
((++pNum))

tBaseList=$pBase
if [[ "$pBase" = "all" ]]; then
    tBaseList="\
        $cgDbLo \
        $cgDbBib \
        $cgDbLib \
    "
fi

if [[ "$cgUseRemote" = "true" ]]; then
    tPort=$cgDbPortRemote
else
    tPort=$cgDbPortLocal
fi

for tBase in $tBaseList; do
    echo 'select TABLE_NAME from information_schema.tables where TABLE_NAME like' "\"${tBase}_%\";" >tmp/get.cmd
    if [[ "$cgDebug" = "true" ]]; then
        cat tmp/get.cmd
    fi

    cat tmp/get.cmd |
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
        continue
    fi
    if [[ "$cgDebug" = "true" ]]; then
        cat tmp/get.out
    fi

    for i in $(sort -r tmp/get.out | tail -n +$pNum); do
        echo "drop table \`$i\`;"
    done >tmp/drop.cmd
    if [[ "$cgDebug" = "true" ]]; then
        cat tmp/drop.cmd
    fi

    # Drop the oldest pNum tables
    if [[ -s tmp/drop.cmd ]]; then
        cat tmp/drop.cmd |
            mysql -N --batch --raw -P $tPort -u $cgDbUser --password=$(cat $cgDbPassCache) -h $cgDbHost $cgDbName >tmp/drop.out 2>&1
        cat tmp/drop.out
    fi
done
exit 0
