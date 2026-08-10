#!/usr/bin/env php
<?php

# export-lo-2-tcsv.php - wrapper. The work is done by class ExportLo2Tcsv in
# export-lo-2-tcsv.inc, so that phpunit can test it without running this
# main section.

require_once __DIR__ . "/export-lo-2-tcsv.inc";

# ****************************************
# GetOps, Includes, Validate

try {
    $gpOpt = ExportLo2Tcsv::fGetOps($argv, $argc);

    if ($gpOpt["help"])
        ExportLo2Tcsv::fUsage($argv[0]);    # ---------->

    if ($gpOpt["test"] != "")
        exit(Util::uRunTest("ExportLo2TcsvTest", $gpOpt["test"]));    # ---------->

    $gApp = new ExportLo2Tcsv(Util::uLoadConf(), $gpOpt);
    $gApp->fValidate();
} catch(Exception $e) {
    echo "Problem with setup: " . $e->getMessage() . "\n";
    exit(2);    # ---------->
}

# ========================================
# Write section

try {
    $gApp->fRun();
} catch(Exception $e) {
    echo "Problem creating table: " . $e->getMessage() . "\n";
    exit(3);    # ---------->
}

exit(0);    # ---------->

/* ...

=pod

=head1 NAME

export-lo-2-tcvs.php - export lo db to csv or tsv file

=head1 SYNOPSIS

 ./export-lo-2-tcvs.php [-s Sep] [-h]

=head1 DESCRIPTION

Export the cgDbTblLo table to file cgBackupFile. Which is usally put in
cgDirBackup. Copy cgBackupFile before running this, if you want to
keep it.

=head1 OPTIONS

See also ENVIRONMENT section.

=over 4

=item B<-s Sep>

Separator. c - comma; t - tab. Default: c

=item B<-h> - help

This help.

=item B<-T> "all" or a test name

Run this script's phpunit test, in test/ExportLo2TcsvTest.php. "all" runs the whole
test class; any other value is passed to phpunit as a --filter, so it
runs the one test method with that name.

=back

=for comment =head1 RETURN VALUE

=for comment =head1 ERRORS

=for comment =head1 EXAMPLES

=head1 ENVIRONMENT

    cgDbTblLo
    cgBackupFile

=for comment =head1 FILES

=for comment =head1 SEE ALSO

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
