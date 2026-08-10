#!/usr/bin/env php
<?php

# bib-style-save.php - wrapper. The work is done by class BibStyleSave in
# bib-style-save.inc, so that phpunit can test it without running this
# main section.

require_once __DIR__ . "/bib-style-save.inc";

# ****************************************
# GetOps, Includes, Validate

try {
    $gpOpt = BibStyleSave::fGetOps($argv, $argc);

    if ($gpOpt["help"])
        BibStyleSave::fUsage($argv[0]);    # ---------->

    if ($gpOpt["test"] != "")
        exit(Util::uRunTest("BibStyleSaveTest", $gpOpt["test"]));    # ---------->

    $gApp = new BibStyleSave(Util::uLoadConf(), $gpOpt);
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
    echo "Problem: " . $e->getMessage() . "\n";
    exit(4);    # ---------->
}

echo "Done. [bib-style-save.php:" . __LINE__ . "]\n";
exit(0);    # ---------->

/* ...

=pod

=head1 NAME

bib-style-save.php - extract the biliography style settings

=head1 SYNOPSIS

 ./bib-style-save.php -c [-h]

=head1 DESCRIPTION


=head1 OPTIONS

See also ENVIRONMENT section.

=over 4

=item B<-h> - help

This help.

=item B<-T> "all" or a test name

Run this script's phpunit test, in test/BibStyleSaveTest.php. "all" runs the whole
test class; any other value is passed to phpunit as a --filter, so it
runs the one test method with that name.

=back

=for comment =head1 RETURN VALUE

=for comment =head1 ERRORS

=head1 ENVIRONMENT

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
