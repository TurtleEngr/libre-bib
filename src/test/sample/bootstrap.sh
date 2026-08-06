#!/bin/bash
# Set PATH, cgDirApp, and cgBin for test/sample/ area

if [[ $# -eq 0 ]]; then
    cat <<EOF
Usage:
   Set:
       cd test/sample
       source ./bootstrap.sh -s

   Restore:
       cd test/sample
       source ./bootstrap.sh -u
EOF
    return
fi

if [[ "$1" = '-s' ]]; then
   cd ../.. >/dev/null 2>&1
   export cgDirApp=$PWD
   export cgBin=$cgDirApp/bin
   cd - >/dev/null 2>&1

   echo $PATH | grep -q $cgBin
   if [[ $? -ne 0 ]]; then
       export PrevPath=$PATH
       PATH=$cgBin:$PATH
   fi
   return
fi

if [[ "$1" = '-u' ]]; then
    if [[ -n "$PrevPath'" ]]; then
        PATH=$PrevPath
    fi
    unset cgDirApp cgBin
    return
fi
