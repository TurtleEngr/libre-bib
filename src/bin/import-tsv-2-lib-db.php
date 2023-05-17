#!/usr/bin/env php
<?php
# TBD more work needed. e.g. remove more options

# -----------------------------
function fusage() {
    global $argc;
    global $argv;
    global $cgDebug;

    system("pod2text $argv[0]");
    exit(1);

    /* ...

=pod

=head1 NAME

import-tsv-2-db.php - DESCRIPTION

=head1 SYNOPSIS

 ./import-tsv-2-db.php [-h]

=head1 DESCRIPTION

Import the cgLibFile (librarything.tsv) to DB table cgDbLib (lib).

=head1 OPTIONS

See the ENVIRONMENT section.

=over 4

=item B<-h> - help

This help.

=back

=for comment =head1 RETURN VALUE

=for comment =head1 ERRORS

=for comment =head1 EXAMPLES

=head1 ENVIRONMENT

    cgLibFile
    cgDbLib

=for comment =head1 FILES

=for comment =head1 SEE ALSO

=head1 NOTES

 https://www.php.net/manual/en/book.pdo.php

=head2 my

 Parse tsv file and import to DB: biblio_db, table: my

 Interesting fields:
    Book_Id,Title,Primary_Author,Publication,Date,
    Media,Page_Count,Tags,ISBN,Subjects,Dewey_Wording

 If Media == 'Ebook', it's a Kindle book

 select Media,Title,Primary_Author,Date,ISBN,Publication from lib where
    Title like '%: %';

=head2 lib

Write a php script that will import a csv file into a mysql db.
The first record of the csv file will had the names of table fields.
If the table does not exits, then create the table with csv file's
fields.

  Run:
  create tunnel in another terminal: ssh moria
  php -f import-lib2db.php

  https://www.php.net/manual/en/book.pdo.php

  Export librarything books:
  https://www.librarything.com/export.php?export_type=tsv

  Parse tsv file.
  Exported librarything: librarything_BruceRaf.tsv

  List header fields, converting spaces to '_':
    head -n 1 librarything_BruceRaf.tsv | sed 's/ /_/g; s/\t/\n/g'

 Find max field length:
    cat librarything_BruceRaf.tsv | while read; do
        echo $REPLY | wc -c;
    done | sort -nu

  show columns from lib;
  select * from lib;

  Interesting fields:
    Book_Id,Title,Primary_Author,Publication,Date,
    Media,Page_Count,Tags,ISBN,Subjects,Dewey_Wording

  If Media == 'Ebook', it's a Kindle book

  alter table lib add primary key(Book_Id);

  select Media,Title,Primary_Author,Date,ISBN,Publication from lib where
  Title like '%: %';

=for comment =head1 CAVEATS

=for comment =head1 DIAGNOSTICS

=for comment =head1 BUGS

=for comment =head1 RESTRICTIONS

=for comment =head1 AUTHOR

=head1 HISTORY

$Revision: 1.1 $ $Date: 2023/05/17 01:13:24 $ GMT

=cut

... */
} # fUsage

# -----------------------------
function fCleanUp() {
    global $cgDebug;
    echo "\n";
} # fCleanUp

# -----------------------------
function fGetOps() {
    global $argc;
    global $argv;
    global $cgDebug;
    global $gpHelp;

    $gpHelp = false;
    $tOpt = getopt("c:i:t:h");
    $gpHelp = isset($tOpt['h']);
    if ($gpHelp or $argc < 2)
        fUsage();

    $tConf = $_ENV['cgDirApp'] . "/etc/conf.php";
    require_once "$tConf";
    require_once "$cgBin/util.php";
    fFixBool();
}

# -----------------------------
function fValidate() {
    global $gHandle;
    global $cgLibFile;

    fValidateCommon();

    if (($gHandle = fopen($cgLibFile, "r")) == FALSE)
        throw new Exception("Cannot open $cgLibFile. [" . __LINE__ . "]");
}

# -----------------------------
function fCreateTable() {
    global $gDb;
    global $gFieldList;
    global $gHandle;
    global $cgDebug;
    global $cgDbLib;
    global $gBackupName;
    global $cgBackup;

    #  Get the fields from the first row
    $tTmpList = fgetcsv($gHandle, 15000, "\t");

    $gFieldList = array();
    foreach ($tTmpList as $tField) {
        $tField = preg_replace('/\s+/', '_', $tField);
        array_push($gFieldList, $tField);
    }

    $gBackupName = "";
    if ($cgBackup and fTableExists($cgDbLib))
        $gBackupName = fRenameTable($cgDbLib);

    if (fTableExists($cgDbLib))
        fExecSql("drop table $cgDbLib");

    # Create the table from header record
    $tSql = "CREATE TABLE $cgDbLib (";
    foreach ($gFieldList as $tField) {
        $tSql .= "`$tField` VARCHAR(255),";
    }
    $tSql = rtrim($tSql, ",") . ")";

    fExecSql("$tSql");
    fExecSql("alter table $cgDbLib add primary key (Book_Id)");
} # fCreateTable

# -----------------------------
function fInsertRec() {
    global $gDb;
    global $gFieldList;
    global $gHandle;
    global $cgDbLib;
    global $cgDebug;

    # Use this to trim all fields in array to be < 254 char
    $fTrimLen = function($pElement) {
        return substr( $pElement, 0, 254 );
    };

    # Insert data into the table
    $tRec = 0;
    while (($tData = fgetcsv($gHandle, 15000, "\t")) !== FALSE) {
        ++$tRec;
        echo ".";
        $tData = array_map($fTrimLen, $tData);
        $tValueStr = implode('","', $tData);
        $tValueStr = utf8_encode($tValueStr);

        $tSql = "INSERT INTO $cgDbLib (`" . implode("`, `", $gFieldList) . "`) VALUES (\"$tValueStr\")";

        $tCount = $gDb->exec($tSql);
        if ($tCount != 1) {
            echo "\nRecord: $tRec \n";
            $tmp = $gDb->errorInfo();
            echo "$tmp[2]\n";
            if ($cgDebug) {
                echo "$tSql \n";
                # var_dump($tValueStr);
                throw new Exception("Insert error. [" . __LINE__ . "]");
            }
        }
    } # while
    echo "\nProcessed: $tRec \n";
}


# ****************************************
# Includes, GetOps, Validate, ReadOnly

try {
    fGetOps();
    fValidate();
} catch(Exception $e) {
    echo "Problem with setup: " . $e->getMessage() . "\n";
    exit;
}

# Write section
try {
    fCreateTable();
    fInsertRec();
} catch(Exception $e) {
    echo "Problem creating table: " . $e->getMessage() . "\n";
}
?>
