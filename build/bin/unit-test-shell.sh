#!/bin/bash

# -------------------
fUsage()
{
    return
    cat <<\EOF >/dev/null
=pod

=head1 NAME

unit-test-shell.sh - test shell scripts

=head1 SYNOPSIS

        unit-test-shell.sh [testName,testName,...]

=head1 DESCRIPTION

shunit2.1 is used to run the unit tests. If no test function names are
listed, then all of the test functions will be run.

=head1 RETURN VALUE

0 - if OK

=head1 ERRORS

Look for the assert errors.

=for comment =head1 EXAMPLES

=for comment =head1 ENVIRONMENT

=for comment =head1 FILES

=head1 SEE ALSO

shunit2.1

=for comment =head1 NOTES

=for comment =head1 CAVEATS

=for comment =head1 DIAGNOSTICS

=for comment =head1 BUGS

=for comment =head1 RESTRICTIONS

=for comment =head1 AUTHOR

=for comment =head1 HISTORY

=cut
EOF
}

# -------------------
oneTimeSetUp()
{
    # --------
    echo "# ignore this line" >$cgTestDir/vc1-pass.tmp
    echo "     # ignore this line too #end spaces   " >>$cgTestDir/vc1-pass.tmp
    cat <<EOF >>$cgTestDir/vc1-pass.tmp
var1=foo
   var2=bar    # comment
export var3="foo3 bar3"
var4="${var1}x $var2"
EOF

    return 0
} # oneTimeSetUp

# -------------------
setUp()
{
    # Restore settings, before each test
    rm /tmp/conf.env &>/dev/null
    rm /tmp/conf.err &>/dev/null

    return 0
} # setUp

# -------------------
oneTimeTearDown() {
    # Cleanup
    return 0
} # oneTimeTearDown

# -------------------
testInit()
{
    local tProg
    local tResult

    assertEquals "$LINENO" "$PWD" "$cgCurDir"
    assertTrue "$LINENO" "[ -d $cgCurDir ]"
    assertTrue "$LINENO" "[ -d $cgBin ]"
    assertTrue "$LINENO" "[ -x $cgBin/valid-conf.sh ]"
#    assertNotNull "$LINENO init" "$cgBin"
#    tResult=$(fLog $tLevel "$tMsg" $tLine $tErr 2>&1)
#    assertContains "$LINENO $tResult" "$tResult" "$cName"
    return 0
} # testInit

# -------------------
testValidConfPass() {
    local tResult
    local tN1
    local tN2

    tResult=$($cgBin/valid-conf.sh <$cgTestDir/vc1-pass.tmp)
    assertTrue "$LINENO" "[ $? -eq 0 ]"
    assertTrue "$LINENO" "[ -s /tmp/conf.env ]"
    assertTrue "$LINENO" "[ -x /tmp/conf.env ]"
    assertFalse "$LINENO" "[ -s /tmp/conf.err ]"
    assertTrue "$LINENO" "bash -n /tmp/conf.env ]"
    assertTrue "$LINENO" "[ $? -eq 0 ]"

    tN1=$(wc -l $cgTestDir/vc1-pass.tmp)
    tN1=${tN1% *}
    tN2=$(wc -l /tmp/conf.env)
    tN2=${tN2% *}
    assertTrue "$LINENO $tN1 $tN2" "[ $tN1 -eq $tN2 ]"

    return 0
} # testValidConfPass

# -------------------
testValidConfError() {
    local tResult
    local tN1
    local tN2
    local tFile=$cgTestDir/vc1-err.tmp

    echo 'var0="#s/b ok"' >$tFile
    tResult=$($cgBin/valid-conf.sh <$tFile 2>&1)
    assertTrue "$LINENO" "[ $? -ne 0 ]"
    assertContains "$LINENO $tResult" "$tResult" "unexpected EOF"
    assertTrue "$LINENO" "[ -s /tmp/conf.err ]"

    echo 'var-1=foo' >$tFile
    tResult=$($cgBin/valid-conf.sh <$tFile 2>&1)
    assertTrue "$LINENO" "[ $? -ne 0 ]"
    assertContains "$LINENO $tResult" "$tResult" "Invalid var name"

    echo 'expor var3="foo3 bar3"' >$tFile
    tResult=$($cgBin/valid-conf.sh <$tFile 2>&1)
    assertTrue "$LINENO" "[ $? -ne 0 ]"
    assertContains "$LINENO $tResult" "$tResult" "Invalid var name"
    tN1=$(wc -l $tFile)
    tN1=${tN1% *}
    tN2=$(wc -l /tmp/conf.env)
    tN2=${tN2% *}
    assertTrue "$LINENO $tN1 $tN2" "[ $tN1 -eq $tN2 ]"

    echo 'var4 = "${var1}x $var2"' >$tFile
    tResult=$($cgBin/valid-conf.sh <$tFile 2>&1)
    assertTrue "$LINENO" "[ $? -ne 0 ]"
    assertContains "$LINENO $tResult" "$tResult" "Invalid var name"

    cat <<\EOF >$tFile
var4="foo"
if [ "$var3" = "foo" ]; then
    var3="bar"
fi
EOF
    tResult=$($cgBin/valid-conf.sh <$tFile 2>&1)
    assertTrue "$LINENO" "[ $? -ne 0 ]"
    assertContains "$LINENO $tResult" "$tResult" "Syntax error"
    assertContains "$LINENO $tResult" "$tResult" "= missing"

    return 0
} # testValidConfError

# -------------------
testSanityCheck() {
    #src/bin/sanity-check.sh
    return 0
} # testSanityCheck

# -------------------
testGenConfPhp() {
    #src/bin/gen-conf-php.sh
    return 0
} # testGenConfPhp

# -------------------
testSortPara() {
    #src/bin/sort-para.sh
    return 0
} # testSortPara

# -------------------
testPass() {
    #src/bin/bib
    return 0
} # testPass

# -------------------
# This should be the last defined function
fRunTests()
{
    if [ "${gpTest:-all}" = "all" ]; then
        # shellcheck disable=SC1091
        . $cgBuildBin/shunit2.1
        exit $?
    fi
    # shellcheck disable=SC1091
    . $cgBuildBin/shunit2.1 -- $gpTest
    exit $?
} # fComRunTests

# ========================================
export PWD
if [ -z "$PWD" ]; then
    PWD=$(pwd)
fi
export cgCurDir=$PWD

export cgBin=$cgCurDir/src/bin
export cgBuildBin=$cgCurDir/build/bin
export cgRefDir=$cgCurDir/test-ref
export cgTestDir=$cgCurDir/test-dir

for i in $cgBin $cgBuildBin $cgRefDir; do
    if [[ ! -d $i ]]; then
        echo "Error: You are not in the top directory. Missing $i"
        exit 1
    fi
done
mkdir $cgTestDir &>/dev/null

# Test globals
export SHUNIT_COLOR

# -----
# Optional input: a comma separated list of test function names
export gpTest="$*"
fRunTests $gpTest
