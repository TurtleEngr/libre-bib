#!/bin/bash
# Usage:
#    valid-conf.sh <conf.env
# Output:
#    /tmp/conf.env
# Return
#    0 - OK
#    1 - errors

tStr=''
tErr=0

# Remove comments, and filter out invalid characters
awk '
    BEGIN {
        tErr=0
    }
    NF == 0 { print ""; next }
    /#/ {
        sub(/\s*#.*/, "");
        if ($0 == "") {
            print ""; next;
        }
    }
    {
        if (split($0, tPart, /=/) != 2) {
            print "error: = missing at line", NR > "/dev/stderr";
            print ""
            tErr=1
        }
        gsub(/[^ A-Za-z0-9_]*/, "", tPart[1]);
        gsub(/[()\`]/, "", tPart[2]);
        print tPart[1] "=" tPart[2];
    }
    END {
        exit $tErr
    }
' >/tmp/conf.env 2>/tmp/conf.err
tErr=$?
if [ $tErr -ne 0 ]; then
    cat /tmp/conf.err
    exit $tErr
fi

chmod a+rx /tmp/conf.env

# Is this still a valid bash file?
tStr=$(cat /tmp/conf.env | bash 2>&1)
tErr=$?
if [ $tErr -ne 0 ]; then
    echo "$tStr"
fi
exit $tErr
