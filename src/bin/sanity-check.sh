#!/usr/bin/env bash

# External globals
export cgDirApp=${cgDirApp:-/opt/libre-bib}
export cgBin=$cgDirApp/bin

# Internal globals
export gErr=0
export gVar=""
export gValue=""

# ------------------------------
fVar() {
    # Input: gVar
    # Output: gVal
    eval gVal=\$$gVar
    if [[ -z "$gVal" ]]; then
        echo "Error: $gVar is not defined"
        ((++gErr))
        return 1 # ---------->
    fi
    return 0
}

# ------------------------------
fDirExist() {
    # Input: gVar (optional)
    # Input: gVal
    if [[ ! -d $gVal ]]; then
        echo "Error: $gVar $gVal dir does not exist."
        ((++gErr))
        return 1 # ---------->
    fi
    if [[ ! -r $gVal ]]; then
        echo "Error: $gVar $gVal dir is not readable."
        ((++gErr))
        return 1 # ---------->
    fi
    return 0
}

# ------------------------------
fFileExist() {
    # Input: gVar (optional)
    # Input: gVal
    if [[ ! -r $gVal ]]; then
        echo "Error: $gVar $gVal file is not readable."
        ((++gErr))
        return 1 # ---------->
    fi
    return 0
}

# ------------------------------
fFileExec() {
    # Input: gVar (optional)
    # Input: gVal
    if [[ ! -x $gVal ]]; then
        echo "Error: $gVar $gVal file is not executable."
        ((++gErr))
        return 1 # ---------->
    fi
    return 0
}

# ------------------------------
fIsBool() {
    # Input: gVar
    # Output: gVal
    if ! fVar; then
        return 1 # ---------->
    fi

    declare -l tTest=$gVal
    case "$tTest" in
        0) return 0 ;;
        n) return 0 ;;
        no) return 0 ;;
        f) return 0 ;;
        false) return 0 ;;
        1) return 0 ;;
        y) return 0 ;;
        yes) return 0 ;;
        t) return 0 ;;
        true) return 0 ;;
        *)
            echo "Error: $gVar $gVal is not a valid boolean value"
            ((++gErr))
            ;;
    esac
    return 1
}

# ------------------------------
fIsNum() {
    # Input: gVar
    # Output: gVal
    if ! fVar; then
        return 1 # ---------->
    fi
    tRegEx='^[[:digit:]]+$'
    if [[ ! $gVal =~ $tRegEx ]]; then
        echo "Error: $gVar $gVal is not a number"
        ((++gErr))
        return 1 # ---------->
    fi
    return 0
}

# ------------------------------
fIsRead() {
    # Input: gVar
    # Output: gVal
    if ! fVar; then
        return 1 # ---------->
    fi
    if [[ ! -r $gVal ]]; then
        echo "Error: $gVar $gVal is not readable"
        ((++gErr))
        return 1 # ---------->
    fi
    return 0
}

# ------------------------------
fIsWrite() {
    # Input: gVar
    # Output: gVal
    if ! fVar; then
        return 1 # ---------->
    fi
    if [[ ! -w $gVal ]]; then
        echo "Error: $gVar $gVal is not writable"
        ((++gErr))
        return 1 # ---------->
    fi
    return 0
}

# ------------------------------
fCheckSh() {
    for gVal in $(grep -rl 'env bash' $cgBin/* | grep -Ev 'Makefile'); do
        if ! fFileExec; then
            continue
        fi
        if ! bash -n $gVal &>/dev/null; then
            echo "Syntax error found in $gVal"
            ((++gErr))
        fi
    done
    return 0
}

# ------------------------------
fCheckPhp() {
    for gVal in \
        $cgBin/util.php \
        $cgDirApp/etc/conf.php \
        $(grep -rl 'env php' $cgBin/* | grep -Ev 'Makefile'); do
        if ! fFileExec; then
            continue
        fi
        if ! php -l $gVal &>/dev/null; then
            echo "Syntax error found in $gVal"
            ((++gErr))
        fi
    done
    return 0
}

# ------------------------------
fNotOk() {
    if [[ $gErr != 0 ]]; then
        echo "$gErr errors found so far."
        echo "Have you run: bib setup-bib"
        exit $gErr # ---------->
    fi
}

# ------------------------------
fCheckDep() {
    for tCmd in \
        libreoffice \
        mysql \
        php \
        pod2html \
        pod2man \
        pod2text \
        pod2usage \
        bash \
        sed \
        tidy \
        make; do
        if ! which $tCmd &>/dev/null; then
            echo "Error: Could not find command: $tCmd"
            ((++gErr))
        fi
    done
    return 0
} # fCheckDep

# ------------------------------
fCheckApp() {
    for gVar in cgDirApp cgBin; do
        if fVar; then
            fDirExist
        fi
    done
    fNotOk # ?---------->

    # ----------
    if [[ "$cgDirApp" != "${cgBin%/bin}" ]]; then
        echo "Error: cgDirApp or cgBin are not set properly"
        ((++gErr))
    fi
    fNotOk # ?---------->

    find $cgDirApp -name '*~' -exec rm {} \;

    # ----------
    for tDir in \
        bin \
        doc \
        doc/manual \
        doc/example \
        etc; do
        gVal=$cgDirApp/$tDir
        fDirExist
    done

    # ----------
    gVar=""
    for tFile in \
        VERSION \
        bin/Makefile \
        bin/bib \
        bin/bib-ref-new.php \
        bin/bib-ref-update.php \
        bin/convert-lo-2-bib.php \
        bin/export-lo-2-tcsv.php \
        bin/export-lo-2-txt.php \
        bin/fixup.sed \
        bin/gen-conf-php.sh \
        bin/import-tcsv-2-lo-db.php \
        bin/import-tsv-2-lib-db.php \
        bin/import-txt-2-lo.php \
        bin/sanity-check.sh \
        bin/update-lib-2-lo.php \
        bin/util.php \
        doc/example/biblio-note.txt \
        doc/example/biblio.txt \
        doc/example/example.odt \
        doc/example/key.txt \
        doc/example/librarything.tsv \
        doc/manual/libre-bib.md \
        doc/ref/biblio.csv \
        doc/ref/biblio.dbf \
        doc/ref/biblio.dbt \
        doc/ref/librarything.tsv \
        etc/cite-new.xml \
        etc/cite-update.xml \
        etc/conf.env \
        etc/conf.php \
        etc/libre-bib.ssh \
        etc/lib-schema.tsv \
        etc/lo-schema.csv; do
        gVal=$cgDirApp/$tFile
        fFileExist
    done

    # ----------
    fCheckSh
    fCheckPhp

    # ----------
    if ! make -s -n -f $cgBin/Makefile check &>/dev/null; then
        echo "Error in Makefile [1]"
        ((++gErr))
    fi
    tResult=$(make -s -f $cgBin/Makefile check 2>&1)
    if [[ $? -ne 0 ]]; then
        echo "Error in Makefile [2]"
        ((++gErr))
    fi
    if [[ "$tResult" != "OK" ]]; then
        echo "Error in Makefile [3], $tResult"
        ((++gErr))
    fi
    fNotOk # ?---------->
}          # fCheckApp

# ------------------------------
fCheckUser() {
    gVal=conf.env
    fFileExec
    fNotOk # ?---------->

    # ----------
    for gVar in \
        cgBackup \
        cgDebug \
        cgNoExec \
        cgNoExecCmd \
        cgUseLib \
        cgUseRemote \
        cgVerbose; do
        fIsBool
    done

    # ----------
    for gVar in \
        cgDirBackup \
        cgDirConf \
        cgDirStatus \
        cgDirTmp; do
        if ! fVar; then
            continue
        fi
        if ! fDirExist; then
            continue
        fi
        fIsWrite
    done

    # ----------
    for gVar in \
        cgBackupNum \
        cgDbPortLocal \
        cgDbPortRemote; do
        if ! fVar; then
            continue
        fi
        fIsNum
    done

    # ----------
    for gVar in \
        cgDbBib \
        cgDbHost \
        cgDbHostRemote \
        cgDbLib \
        cgDbLo \
        cgDbName \
        cgDbPassCache \
        cgDbPassHint \
        cgDbUser \
        cgBackupFile; do
        fVar
    done

    # ----------
    for gVar in \
        cgLoFile \
        cgDocFile; do
        fIsRead
    done

    # ----------
    if [[ $cgBackupNum -lt 1 || $cgBackupNum -gt 100 ]]; then
        echo "Error: cgBackupNum $cgBackupNum should be 1 to 100"
        ((++gErr))
    fi

    # ----------
    gVar=cgDirLibreofficeConf
    if fVar; then
        fDirExist
        if [[ $? -ne 0 ]]; then
            echo "To set this, run: libreoffice $cgDocFile"
        fi
    fi

    if [[ "$cgUseRemote" == "true" ]]; then
        gVar=cgSshUser
        fVar
        gVar=cgSshKey
        fIsRead
    fi

    if [[ "$cgUseLib" == "true" ]]; then
        gVar=cgLibFile
        fIsRead
    fi

    return $gErr
} # fCheckUser

# ========================================

if [[ ! -x $cgDirApp/etc/conf.env ]]; then
    echo "Error: $cgDirApp/etc/conf.env is not executable"
    exit 1
fi
if ! bash -n $cgDirApp/etc/conf.env &>/dev/null; then
    echo "Error: $cgDirApp/etc/conf.env has syntax errors"
    exit 1
fi

if [[ -z "$cgDirConf" ]]; then
    . $cgDirApp/etc/conf.env
fi

fCheckDep
fCheckApp

if [[ ! -f conf.env ]]; then
    # setup-bib has not been called yet
    exit $gErr # ---------->
fi

if [[ ! -x ./conf.env ]]; then
    echo "Error: ./conf.env is not executable"
    exit 1
fi
if ! bash -n ./conf.env &>/dev/null; then
    echo "Error: ./conf.env has syntax errors"
    exit 1
fi

# This overrides the app's defaults, so not a good check for the
# defaults.

. ./conf.env

fCheckUser
fNotOk # ?---------->

exit 0
