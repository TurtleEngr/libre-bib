#!/bin/bash

# -------------------
fUsage()
{
    return
    cat <<\EOF >/dev/null
=pod

=head1 NAME

unit-test-app.sh - test libre-bib app

=head1 SYNOPSIS

        unit-test-app.sh [testName,testName,...]

=head1 DESCRIPTION

shunit2.1 is used to run the unit tests. If no test function names are
listed, then all of the test functions will be run.

=head1 RETURN VALUE

0 - if all passed
!0 - if there are any fails

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

    return 0
} # oneTimeSetUp

# -------------------
setUp()
{
    # Restore settings, before each test

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
    assertTrue "$LINENO" "[ -x $cgBin/valid-conf.sh ]"
#    assertNotNull "$LINENO init" "$cgBin"
#    tResult=$(fLog $tLevel "$tMsg" $tLine $tErr 2>&1)
#    assertContains "$LINENO $tResult" "$tResult" "$cName"
    return 0
} # testInit

# -------------------
testPass() {
    local tResult

    return 0
} # testValidConfPass

# --------------------------------------
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
export cgBuildBin=$cgCurDir/bin
export cgRefDir=$cgCurDir/test/ref
export cgTestDir=$cgCurDir/tmp-test

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
