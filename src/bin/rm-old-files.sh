#!/usr/bin/env bash

# Expected pBase values:
# all - do all of these:
# $cgDirBackup/backup-${cgDbLo}.csv.sav
# $cgDirBackup/${cgDocFile}
# $cgDirBackup/cite-new.xml
# $cgDirBackup/cite-update.xml
# $cgDirBackup/bib-style.xml
# $cgDirBackup/bib-template.xml

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
        $cgDirBackup/backup-${cgDbLo}.csv.sav \
        $cgDirBackup/${cgDocFile} \
        $cgDirBackup/cite-new.xml \
        $cgDirBackup/cite-update.xml \
        $cgDirBackup/bib-style.xml \
        $cgDirBackup/bib-template.xml \
    "
fi

for tBase in $tBaseList; do
    # Get the names of the files to be removed (excluding the pNum newest ones)
    tFileList=$(ls -t ${tBase}* 2>/dev/null | tail -n +$pNum)

    if [[ -n "$tFileList" ]]; then
        rm $tFileList
        if [[ $pNum -eq 3 ]]; then
            mv $tBase.~* $tBase.~1~
        fi
    fi
done
exit 0
