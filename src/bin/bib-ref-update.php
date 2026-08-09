#!/usr/bin/env php
<?php

# bib-ref-update.php - wrapper. The work is done by class BibRefUpdate in
# bib-ref-update.inc, so that phpunit can test it without running this
# main section.

require_once __DIR__ . "/bib-ref-update.inc";

# ****************************************
# GetOps, Includes, Validate

try {
    $gpOpt = BibRefUpdate::fGetOps($argv, $argc);

    if ($gpOpt["help"])
        BibRefUpdate::fUsage($argv[0]);    # ---------->

    if ($gpOpt["test"] != "")
        exit(Util::uRunTest("BibRefUpdateTest", $gpOpt["test"]));    # ---------->

    $gApp = new BibRefUpdate(Util::uLoadConf(), $gpOpt);
    $gApp->fValidate();
} catch(Exception $e) {
    echo "Problem with setup: " . $e->getMessage() . "\n";
    exit(3);    # ---------->
}

# ========================================
# Write section

try {
    $gApp->fRun();
} catch(Exception $e) {
    echo "Problem creating table: " . $e->getMessage() . "\n";
    exit(4);    # ---------->
}

echo "Done. [bib-ref-update.php:" . __LINE__ . "]\n";
exit(0);    # ---------->

/* ...

=pod

=head1 NAME

bib-ref-update.php - update bib refs into Libreoffice odt document

=head1 SYNOPSIS

 ./bib-ref-update.php [-h]

=head1 DESCRIPTION

First run bib-ref-new.php.

The existing biblio references will be replace with new entries from
the cgDbTblBib table. There is no comparison to see if things are
different.  Everything is just replaced, even things are the same.

=head1 OPTIONS

See also ENVIRONMENT section.

=over 4

=item B<-h> - help

This help.

=item B<-T> "all" or a test name

Run this script's phpunit test, in test/BibRefUpdateTest.php. "all" runs the whole
test class; any other value is passed to phpunit as a --filter, so it
runs the one test method with that name.

=back

=for comment =head1 RETURN VALUE

=for comment =head1 ERRORS

=for comment =head1 EXAMPLES

=head1 ENVIRONMENT

Set these in conf.env

    cgDocFile              # Your doc file, to whole reason for this app
    cgDbTblBib                # Partially formatted cgDbTblLo table

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
