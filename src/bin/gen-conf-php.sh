#!/usr/bin/env bash
# Convert bash env. var. to be php global vars
# Usage:
# $cgBin/gen-conf-php.sh <$cgDirApp/etc/conf.env >$cgDirApp/etc/conf.php

echo "<?php"
echo "# Generated with gen-conf-php.sh"

cat <<\EOF

global $cgBin;
$cgBin=$_ENV["cgBin"];

global $cgDirApp;
$cgDirApp=$_ENV["cgDirApp"];
EOF

while read -r tLine; do
    tRegEx="^export[[:space:]]+([[:alnum:]_]+)"
    if [[ "$tLine" =~ $tRegEx ]]; then
        tVar="${BASH_REMATCH[1]}"
        echo -e "\nglobal \$$tVar;"
        echo -e "\$$tVar=\$_ENV[\"$tVar\"];"
    fi
done
