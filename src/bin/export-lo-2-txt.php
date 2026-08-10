#!/usr/bin/env php
<?php

# export-lo-2-txt.php - wrapper. The work is done by class ExportLo2Txt in
# export-lo-2-txt.inc, so that phpunit can test it without running this
# main section.

require_once __DIR__ . "/export-lo-2-txt.inc";

# ****************************************
# GetOps, Includes, Validate

try {
    $gpOpt = ExportLo2Txt::fGetOps($argv, $argc);

    if ($gpOpt["help"])
        ExportLo2Txt::fUsage($argv[0]);    # ---------->

    if ($gpOpt["test"] != "")
        exit(Util::uRunTest("ExportLo2TxtTest", $gpOpt["test"]));    # ---------->

    $gApp = new ExportLo2Txt(Util::uLoadConf(), $gpOpt);
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

export-lo-2-txt.php - export lo db to biblio.txt

=head1 SYNOPSIS

 ./export-lo-2-txt.php [-h]

=head1 DESCRIPTION

Table cgDbTblLo will be exported to cgDirTmp/cgLoFile. That is, the table
will be converted to a text file.

=head1 OPTIONS

=over 4

See also ENVIRONMENT section.

=item B<-h> - help

This help.

=item B<-T> "all" or a test name

Run this script's phpunit test, in test/ExportLo2TxtTest.php. "all" runs the whole
test class; any other value is passed to phpunit as a --filter, so it
runs the one test method with that name.

=back

=for comment =head1 RETURN VALUE

=for comment =head1 ERRORS

=for comment =head1 EXAMPLES

=head1 ENVIRONMENT

Set these in conf.env

    cgDbTblLo
    cgDirTmp
    cgLoFile

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
