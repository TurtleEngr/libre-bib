#!/usr/bin/env php
<?php

# import-txt-2-lo.php - wrapper. The work is done by class ImportTxt2Lo in
# import-txt-2-lo.inc, so that phpunit can test it without running this
# main section.

require_once __DIR__ . "/import-txt-2-lo.inc";

# ****************************************
# GetOps, Includes, Validate

try {
    $gpOpt = ImportTxt2Lo::fGetOps($argv, $argc);

    if ($gpOpt["help"])
        ImportTxt2Lo::fUsage($argv[0]);    # ---------->

    if ($gpOpt["test"] != "")
        exit(Util::uRunTest("ImportTxt2LoTest", $gpOpt["test"]));    # ---------->

    $gApp = new ImportTxt2Lo(Util::uLoadConf(), $gpOpt);
    $gApp->fValidate();
} catch(Exception $e) {
    echo "\nProblem with setup: " . $e->getMessage() . "\n";
    exit(1);    # ---------->
}

# ========================================
# Write section

try {
    $gApp->fRun();
} catch(Exception $e) {
    echo "\nProblem creating table: " . $e->getMessage() . "\n";
    echo "Concider restoring " . $gApp->mDbTblLo . " from " . $gApp->mBackupName . "\n";
    exit(2);    # ---------->
}

exit(0);    # ---------->

/* ...

=pod

=head1 NAME

import-txt-2lo.php - import biblio.txt to lo db

=head1 SYNOPSIS

 ./import-txt-2lo.php [-h]

=head1 DESCRIPTION

Import file cgLoFile to table cgDbTblLo, in DB cgDbName.

All records blocks in cgLoFile must start with: "Id:"

=head1 OPTIONS

See also ENVIRONMENT section.

=over 4

=item B<-h> - help

This help.

=item B<-T> "all" or a test name

Run this script's phpunit test, in test/ImportTxt2LoTest.php. "all" runs the whole
test class; any other value is passed to phpunit as a --filter, so it
runs the one test method with that name.

=back

=for comment =head1 RETURN VALUE

=for comment =head1 ERRORS

=for comment =head1 EXAMPLES

=head1 ENVIRONMENT

Set these in conf.env

    cgLoFile
    cgDbTblLo

=for comment =head1 FILES

=for comment =head1 =head1 SEE ALSO

=for comment =head1 NOTES

=for comment =head1 CAVEATS

=for comment =head1 DIAGNOSTICS

=for comment =head1 BUGS

=for comment =head1 RESTRICTIONS

=for comment =head1 AUTHOR

=for comment =head1 HISTORY

=cut

... */
?>
