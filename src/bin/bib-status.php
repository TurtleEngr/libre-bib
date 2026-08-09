#!/usr/bin/env php
<?php

# bib-status.php - wrapper. The work is done by class BibStatus in
# bib-status.inc, so that phpunit can test it without running this
# main section.

require_once __DIR__ . "/bib-status.inc";

# ****************************************
# GetOps, Includes, Validate

try {
    $gpOpt = BibStatus::fGetOps($argv, $argc);

    if ($gpOpt["help"])
        BibStatus::fUsage($argv[0]);    # ---------->

    if ($gpOpt["test"] != "")
        exit(Util::uRunTest("BibStatusTest", $gpOpt["test"]));    # ---------->

    $gApp = new BibStatus(Util::uLoadConf(), $gpOpt);
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
    echo "Problem with setup: " . $e->getMessage() . "\n";
    exit(3);    # ---------->
}

exit(0);    # ---------->

/* ...

=pod

=head1 NAME

bib-ref-new.php - insert new bib refs into Libreoffice odt document

=head1 SYNOPSIS

 ./bib-ref-new.php [-h]

=head1 DESCRIPTION

bib-ref-new.php will look for {REF} tags in cgDocFile, and replace
them with bibliography-mark tags. The cgDbib Table will be used to
look up the values for the bibliography-marks.

If a {REF} tag is not found in the DB, then it will be left in the
file and a warning will be output.

=head1 OPTIONS

See also ENVIRONMENT section.

=over 4

=item B<-h> - help

This help.

=item B<-T> "all" or a test name

Run this script's phpunit test, in test/BibStatusTest.php. "all" runs the whole
test class; any other value is passed to phpunit as a --filter, so it
runs the one test method with that name.

=back

=for comment =head1 RETURN VALUE

=head1 ERRORS

If you see "Error:" messages, the script failed. Fix the errors
and maybe restore from backed up files or DB, then try again.

    Does the conf file exist?
    Values in the conf file?
    Is the ssh tunnel setup?
    Does the DB exist?
    Does the user have grants needed to access DB and it's tables?
    Do expected files exist?

=for comment =head1 EXAMPLES

=head1 ENVIRONMENT

cgDirApp is required

Most of variables in the conf.env are used by this script.

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
