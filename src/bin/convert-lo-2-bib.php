#!/usr/bin/env php
<?php

# convert-lo-2-bib.php - wrapper. The work is done by class ConvertLo2Bib in
# convert-lo-2-bib.inc, so that phpunit can test it without running this
# main section.

require_once __DIR__ . "/convert-lo-2-bib.inc";

# ****************************************
# GetOps, Includes, Validate

try {
    $gpOpt = ConvertLo2Bib::fGetOps($argv, $argc);

    if ($gpOpt["help"])
        ConvertLo2Bib::fUsage($argv[0]);    # ---------->

    if ($gpOpt["test"] != "")
        exit(Util::uRunTest("ConvertLo2BibTest", $gpOpt["test"]));    # ---------->

    $gApp = new ConvertLo2Bib(Util::uLoadConf(), $gpOpt);
    $gApp->fValidate();
} catch(Exception $e) {
    echo "Problem with setup: " . $e->getMessage() . "\n";
    exit(1);    # ---------->
}

# ========================================
# Write section

try {
    $gApp->fRun();
} catch(Exception $e) {
    echo "Problem creating table: " . $e->getMessage() . "\n";
    exit(2);    # ---------->
}

exit(0);    # ---------->

/* ...

=pod

=head1 NAME

convert-lo-2-bib.php - copy lo table to create partially formatted bib fields.

=head1 SYNOPSIS

 ./convert-lo-2-bib.php [-h]

=head1 DESCRIPTION

Generate the cgDbTblBib table from the $cgDbTblLo table. Make a backup of
the cgDbTblBib table. This preprocessing makes it easier to use these fields
in the LibreOffice bibliography, because it only includes prefix and suffix
punctuation if a field is not empty.

=head1 OPTIONS

See also ENVIRONMENT section.

=over 4

=item B<-h> - help

This help.

=item B<-T> "all" or a test name

Run this script's phpunit test, in test/ConvertLo2BibTest.php. "all" runs the whole
test class; any other value is passed to phpunit as a --filter, so it
runs the one test method with that name.

=back

=for comment =head1 RETURN VALUE

=for comment =head1 ERRORS

=for comment =head1 EXAMPLES

=head1 ENVIRONMENT

Set these in conf.env

    cgDbTblBib
    cgDbTblLo

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
