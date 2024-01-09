#!/usr/bin/env bash

# Usage:
#    valid-conf.sh <conf.env
# Output:
#    /tmp/conf.env
# Return
#    0 - OK
#    1 - errors

tStr=''
tErr=0

# Remove comments, and look for invalid characters
awk '
    BEGIN {
        tErr=0
    }
    NF == 0 {
        print ""
        next
    }
    /#/ {
        gsub(/\s*#.*/, "")
        gsub(/\s*$/, "")
        if ($0 == "") {
            print ""
            next
        }
    }
    /[()\`\[\]]/ {
        print "Error: Syntax error at line:", NR > "/dev/stderr";
        print $0
        tErr=1
        next
    }
    /=/ {
        split($0, tPart, /=/)
        gsub(/^ */, "", tPart[1])
        gsub(/ *export */, "", tPart[1])
        tSub = gsub(/[^A-Za-z0-9_]+/, "", tPart[1])
        if (tSub) {
            print "Error: Invalid var name at line:", NR > "/dev/stderr";
            print $0
            tErr=1
            next
        }
        print tPart[1] "=" tPart[2]
        next
    }
    {
            print "Error: = missing at line:", NR > "/dev/stderr"
            print $0
            tErr=1
    }
    END {
        exit tErr
    }
' >/tmp/conf.env 2>/tmp/conf.err
tErr=$?
if [ $tErr -ne 0 ]; then
    cat /tmp/conf.err
    echo '----------'
    cat -n /tmp/conf.env
    exit $tErr
fi

chmod a+rx /tmp/conf.env

# Is this still a valid bash file?
tStr=$(cat /tmp/conf.env | bash 2>&1)
tErr=$?
if [ $tErr -ne 0 ]; then
    echo "$tStr" >>/tmp/conf.err
    cat /tmp/conf.err
    echo '----------'
    cat -n /tmp/conf.env
fi
exit $tErr
