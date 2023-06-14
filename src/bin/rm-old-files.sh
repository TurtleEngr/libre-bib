#!/usr/bin/env bash

pBase=$1
pNum=$2

(( ++pNum ))

# Get the names of the files to be removed (excluding the pNum newest ones)
tFileList=$(ls -t ${pBase}* | tail -n +$pNum)

if [[ -n "$tFileList" ]]; then
    rm $tFileList
    if [[ $pNum -eq 3 ]]; then
        mv $pBase.~* $pBase.~1~
    fi
fi
