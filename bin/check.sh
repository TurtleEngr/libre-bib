#!/usr/bin/env bash

export i

# --------------------
export cgDirApp=$PWD/src
export cgBin=$cgDirApp/bin

# --------------------
export tErr=0
$cgBin/sanity-check.sh
((tErr += $?))
if [[ $tErr -gt 0 ]]; then
    echo -e "sanity-check.sh errors\n"
fi

# --------------------
for i in $(shfmt -l -i 4 -ci $cgBin); do
    ((++tErr))
    if ! bash -n $i; then
        ((++tErr))
        continue
    fi
    echo -e "Run 'shfmt' -i 4 -ci -w $i\n"
done

# --------------------
for i in $cgBin/*.php; do
    tFound=$($cgBin/phptidy.php diff -q $i &>/dev/null)
    if [[ -n "$tFound" ]]; then
        ((++tErr))
        echo "$tFound"
        echo -e "Run: phptidy.php replace $i\n"
    fi
done

# --------------------
if [[ $tErr -gt 0 ]]; then
    cat <<EOF

Correct the above syntax and formatting errors before commiting your changes.
Also look at .git/hooks/pre-commit requirements:
    File names can only use letters, numbers, hypen, dash, and periods.
    File names cannot begin with hyphens or end with periods.
    File names cannot be all periods.
    No trailing spaces in file.
    No TABs in most files.
    "Large" binary files are not allowed.
EOF
fi
exit $tErr
