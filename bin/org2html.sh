#!/usr/bin/env bash
set -u

# ========================================
# Functions

# --------------------------------
fUsage() {
    cat <<EOF
$cName - comvert FILE.org to FILE.html
Usage
    $cName FILE.org FILE.html
Debug
    Before $cName is run, all files in $Tmp
    are removed, unless env. var. gpDebug is set and not 0.
Replacements
    '+ ' - will be changed to <li>
    '- ' - will be regular paragraphs
    '**** ' - will be replaced with <h4> (similarly for 5 and 6 *)
    '{.*}' - will be replaced with <cite>.*</cite>
    '[TBD.*] - will be replaced with <span class="tbd">[TBD.*]</span>
EOF
    exit 1
} # fUsage

# ========================================
# Main

export gpDebug=${gpDebug:-0}
cName=org2html.sh
cTidyHtml="tidy -q -i -w 78 -asxhtml --break-before-br yes --indent-attributes yes --indent-spaces 2 --tidy-mark no --vertical-space no"

# -------------------
# Setup Tmp files
export Tmp=${Tmp:-"/tmp/$USER/$cName"}
if [[ ! -d $Tmp ]]; then
    mkdir -p $Tmp
fi
if [[ $gpDebug -eq 0 ]]; then
    rm $Tmp/* 2>/dev/null
fi

export cPID=$$
export cTmpF=$Tmp/file-$cPID
cTmp1=${cTmpF}-part1.tmp
cTmp2=${cTmpF}-part2.tmp
cTmp3=${cTmpF}-part3.tmp
cTmp4=${cTmpF}-part4.tmp
cTmpErr=${cTmpF}-part3.err
cPreFix=${cTmpF}-prefix.sed
cPreFixPl=${cTmpF}-prefix.pl
cPostFix=${cTmpF}-postfix.sed

# -------------------
# Get Args Section
if [ $# -ne 2 ]; then
    fUsage
fi

pFileIn=$1
pFileOut=$2
if [[ ! -r $pFileIn ]]; then
    echo "Error: cannot read file: $pFileIn"
    exit 1
fi

# -------------------
# Helper files

cat <<\EOF >$cPreFix
s/^ *- /\n\n/g
s;^\*\*\*\* \(.*\);\n<h4>\1</h4>\n;
s;^\*\*\*\*\* \(.*\);\n<h5>\1</h5>\n;
s;\*\*\*\*\*\* \(.*\);\n<h6>\1</h6>\n;
EOF

cat <<\EOF >$cPreFixPl
    while (<>) {
        s/\[TBD([^]]*])/<span class="tbd">[TBD$1<\/span>/;
        print $_;
    }
EOF

cat <<\EOF >$cPostFix
s/\&lt;/\</g
s/\&gt;/>/g

s/<p><p/<p/g
s;</p></p>;</p>;g

s/<p>\(<blockquote[^>]*>\)/\1<p>/
s;</blockquote></p>;</p></blockquote>;

s/\&quot;/"/g
s;<p></p>;;g
s;<p><div ;<div ;g
s;</div></p>;</div>;g

s|<p>&lt;h4&gt;|<h4>|g
s|&lt;/h4&gt;</p>|</h4>|g

s|<p>&lt;h5&gt;|<h5>|g
s|&lt;/h5&gt;</p>|</h5>|g

s|<p>&lt;h6&gt;|<h6>|g
s|&lt;/h6&gt;</p>|</h6>|g

s;<p><h4>;<h4>;g
s;</h4></p>;</h4>;g

s;<p><h5>;<h5>;g
s;</h5></p>;</h5>;g

s;<p><h6>;<h6>;g
s;</h6></p>;</h6>;g

s;{\(.\);<cite>{\1;g
s;\(.\)};\1}</cite>;g

s;<cite><cite>;<cite>;g
s;</cite></cite>;</cite>;g

s;<h1;<hr><h1;g
s;<h2;<hr><h2;g
EOF

# --------------------
# Functional section

sed -f $cPreFix  <$pFileIn | perl  $cPreFixPl >$cTmp1
pandoc -f org -t html <$cTmp1 >$cTmp2

sed -f /$cPostFix <$cTmp2 >$cTmp3
$cTidyHtml <$cTmp3 >$pFileOut 2>$cTmpErr

sed -i 's|/\*<!\[CDATA\[\*/|| ; s|/\*]]>\*/||' $pFileOut

cat $cTmpErr
echo
echo "If lots of errors, see: $cTmp3 and $cTmpErr"
