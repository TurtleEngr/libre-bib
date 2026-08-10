#!/usr/bin/env php
<?php

# import-tcsv-2-lo-db.php - wrapper. The work is done by class ImportTcsv2LoDb in
# import-tcsv-2-lo-db.inc, so that phpunit can test it without running this
# main section.

require_once __DIR__ . "/import-tcsv-2-lo-db.inc";

# ****************************************
# GetOps, Includes, Validate

try {
    $gpOpt = ImportTcsv2LoDb::fGetOps($argv, $argc);

    if ($gpOpt["help"])
        ImportTcsv2LoDb::fUsage($argv[0]);    # ---------->

    if ($gpOpt["test"] != "")
        exit(Util::uRunTest("ImportTcsv2LoDbTest", $gpOpt["test"]));    # ---------->

    $gApp = new ImportTcsv2LoDb(Util::uLoadConf(), $gpOpt);
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
    echo "Concider restoring " . $gApp->mDbLo . " from " . $gApp->mBackupName . "\n";
    exit(3);    # ---------->
}

exit(0);    # ---------->

/* ...

=pod

=head1 NAME

import-tcsv-2-lo-db.php - create lo DB from tsv file

=head1 SYNOPSIS

 ./import-tcsv-2-lo-db.php [-s Sep] [-h]

=head1 DESCRIPTION

Import the tab or comma separated file cgBackupFile to the cgDbLo
table.  The header will be used for field names.

=head1 OPTIONS

See also ENVIRONMENT section.

=over 4

=item B<-s Sep>

Separator. c - comma; t - tab. Default: c

=item B<-h> - help

This help.

=item B<-T> "all" or a test name

Run this script's phpunit test, in test/ImportTcsv2LoDbTest.php. "all" runs the whole
test class; any other value is passed to phpunit as a --filter, so it
runs the one test method with that name.

=back

=for comment =head1 RETURN VALUE

=head1 ERRORS

    Does the conf.env file exist?
    Values in the conf file?
    Do expected files exist?
    Is the ssh tunnel setup?
    Does the DB exist?
    Does the user have grants needed to access DB and it's tables?

=for comment =head1 EXAMPLES

=head1 ENVIRONMENT

Set these in conf.env
    cgBackupFile         # Required
    cgDbLo                 # Required

=for comment =head1 FILES

=for comment =head1 SEE ALSO

=head1 NOTES

 https://www.php.net/manual/en/book.pdo.php

 Convert: ../draft/MY-BIBLIO-final.txt to MY-BIBLIO-final.tsv
 Run: ./convert-txt2tsv.sh

 Parse tsv file and import to DB: biblio_bruce, table: my

 List header fields, converting spaces to '_':
    head -n 1 MY-BIBLIO-final.tsv | sed 's/ /_/g; s/\t/\n/g'

 show columns from lib;
 select * from lib;

 Interesting fields:
    Book_Id,Title,Primary_Author,Publication,Date,
    Media,Page_Count,Tags,ISBN,Subjects,Dewey_Wording

 If Media == 'Ebook', it's a Kindle book

 alter table lo add primary key (Identifier);

 select Media,Title,Primary_Author,Date,ISBN,Publication from lib where
 Title like '%: %';

=for comment =head1 CAVEATS

=for comment =head1 DIAGNOSTICS

=for comment =head1 BUGS

=for comment =head1 RESTRICTIONS

=for comment =head1 AUTHOR

=for comment =head1 HISTORY

=cut

... */
?>
