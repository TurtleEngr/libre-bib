#!/usr/bin/env php
<?php

# bib-ref-new.php - wrapper. The work is done by class BibRefNew in
# bib-ref-new.inc, so that phpunit can test it without running this
# main section.

require_once __DIR__ . "/bib-ref-new.inc";

# ****************************************
# GetOps, Includes, Validate

try {
    $gpOpt = BibRefNew::fGetOps($argv, $argc);

    if ($gpOpt["help"])
        BibRefNew::fUsage($argv[0]);    # ---------->

    if ($gpOpt["test"] != "")
        exit(Util::uRunTest("BibRefNewTest", $gpOpt["test"]));    # ---------->

    $gApp = new BibRefNew(Util::uLoadConf(), $gpOpt);
    $gApp->fValidate();
} catch(Exception $e) {
    echo "Problem with setup: " . $e->getMessage() . " [bib-ref-new.php:" . __LINE__ . "]\n";
    exit(3);    # ---------->
}

# ========================================
# Write section

try {
    $gApp->fRun();
} catch(Exception $e) {
    echo "Problem updating the document: " . $e->getMessage() . " [bib-ref-new.php:"
        . __LINE__ . "]\n";
    exit(4);    # ---------->
}

echo "Done. [bib-ref-new.php:" . __LINE__ . "]\n";
exit(0);    # ---------->

/* ...

=pod

=head1 NAME

bib-ref-new.php - insert new bib refs into Libreoffice odt document

=head1 SYNOPSIS

 ./bib-ref-new.php [-c] [-h]

=head1 DESCRIPTION

bib-ref-new.php will look for "bare" {REF} cites in cgDocFile, and
replace them with bibliography-mark tags. The cgDbTblBib Table will be
used to look up the values for the bibliography-marks.

A cite is the text between '{' and '}'. The text before an optional
':' is the Identifier; the rest, if any, is the location and is put
after the bibliography-mark. For example:

    {ARJ00}
    {GAS00:p22}

content.xml is parsed with the php DOM functions, so a cite is only
recognized when it is all within one XML text node. If the cite is all
of a text:span, the span is replaced too, which drops its character
style.

If a {REF} cite is not found in the DB, then it will be left in the
file and a warning will be output.

Cites that already have bibliography-mark tags are not touched. See
bib-ref-update.php to refresh those from the DB.

=head1 OPTIONS

See also ENVIRONMENT section.

=over 4

=item B<-c> - change

Update cgDocFile.

=item B<-h> - help

This help.

=item B<-T> "all" or a test name

Run this script's phpunit test, in test/BibRefNewTest.php. "all" runs
the whole test class; any other value is passed to phpunit as a
--filter, so it runs the one test method with that name.

=back

=for comment =head1 RETURN VALUE

=for comment =head1 ERRORS

=head1 ENVIRONMENT

cgDirApp is required

Set these in conf.env

    cgDocFile              # Your doc file, to whole reason for this app
    cgDbTblBib             # Partially formatted cgDbTblLo table

=head1 FILES

 etc/cite-new.xml - the XML template for a new cite
 bib-ref-new.inc  - the functions used by this script

=head1 SEE ALSO

bib-ref-update.php, util.php

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
