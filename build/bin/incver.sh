#!/usr/bin/env bash

if [[ $# -ne 2 ]]; then
    cat <<EOF
Usage
    inver.sh [-M -m -p] FILE
EOF
    exit 1
fi

pLevel="$1"
pFile="$2"

if [[ ! -w $pFile ]]; then
    echo "Error: $pFile is missing or not writable."
    exit 1
fi

IFS='.'
read pMajor pMinor pPatch pExtra < <(cat "$pFile")
IFS=' '
##echo "M=$pMajor m=$pMinor p=$pPatch e=$pExtra"

case $pLevel in
    -M) ((++pMajor))
       pMinor=0
       pPatch=0
    ;;
    -m) ((++pMinor))
       pPatch=0
    ;;
    -p) ((++pPatch));;
    *) echo "Error: invalid option: $pLevel";;
esac

##echo "${pMajor}.${pMinor}.${pPatch}"
echo "${pMajor}.${pMinor}.${pPatch}" >$pFile

exit 0
