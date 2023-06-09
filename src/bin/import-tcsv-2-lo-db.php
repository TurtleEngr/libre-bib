#!/usr/bin/env php
<?php

# -----------------------------
function fusage() {
    global $argc;
    global $argv;

    system("pod2text $argv[0]");
    exit(1);     # ---------->

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
} # fUsage

# -----------------------------
function fCleanUp() {
    echo "\n";
} # fCleanUp

# -----------------------------
function fGetOps() {
    global $argc;
    global $argv;
    global $cgDebug;
    global $gpHelp;

    $gpHelp = false;
    $gpSep = 'c';

    $tOpt = getopt("cs:h");

    if (isset($tOpt['s']))
        $gpSep = $tOpt['s'];

    $gpHelp = isset($tOpt['h']);

    if ($gpHelp or $argc < 2)
        fUsage();

    $tConf = $_ENV['cgDirApp'] . "/etc/conf.php";
    require_once "$tConf";
    require_once "$cgBin/util.php";
    uFixBool();

} # fGetOps

# -----------------------------
function fValidate() {
    global $gSep;
    global $gpSep;
    global $gFiileH;
    global $cgDebug;
    global $cgBackupFile;

    uValidateCommon();

    if ("$gpSep" == "")
        throw new Exception("Error: Missing -s option. [import-tcsv-2-lo-db.php:" . __LINE__ . "]");
    switch ($gpSep) {
    case "c":
        $gSep = ",";
        break;
    case "t":
        $gSep = "\t";
        break;
    default:
        throw new Exception("Error: Bad -s. Should be 'c' or 's'. [import-tcsv-2-lo-db.php:" . __LINE__ . "]");
    }

    if (($gFiileH = fopen($cgBackupFile, "r")) == FALSE)
        throw new Exception("Cannot open $cgBackupFile. [import-tcsv-2-lo-db.php:" . __LINE__ . "]");
} # fValidate

# -----------------------------
function fCreateTable() {
    global $cgDbLo;
    global $gBackupName;
    global $gFieldList;
    global $gFiileH;

    $gBackupName = "";
    if (uTableExists($cgDbLo))
        $gBackupName = uRenameTable($cgDbLo);

    #  Get the fields from the first row
    $tTmpList = fgetcsv($gFiileH, 15000, $gSep);

    $gFieldList = array();
    foreach ($tTmpList as $tField) {
        $tField = preg_replace('/\s+/', '_', $tField);
        array_push($gFieldList, $tField);
    }

    # Create the table from header record
    $tSql = "CREATE TABLE IF NOT EXISTS $cgDbLo (";
    foreach ($gFieldList as $tField)
        $tSql .= "`$tField` VARCHAR(255),";
    $tSql = rtrim($tSql, ",") . ")";
    uExecSql("$tSql");
    uExecSql("alter table $cgDbLo add primary key (Identifier)");
} # fCreateTable

# -----------------------------
function fInsertRec() {
    global $gDb;
    global $gSep;
    global $gFieldList;
    global $gFiileH;
    global $cgDbLo;
    global $cgDebug;

    # Use this to trim all fields in array to be < 254 char
    $fTrimLen = function($pElement) {
        return substr( $pElement, 0, 254 );     # ---------->
    };

    # Insert data into the table
    $tRec = 0;
    while (($tData = fgetcsv($gFiileH, 15000, $gSep)) !== FALSE) {
        ++$tRec;
        echo ".";
        $tData = array_map($fTrimLen, $tData);
        $tValueStr = implode('","', $tData);
        $tValueStr = utf8_encode($tValueStr);

        $tSql = "INSERT INTO $cgDbLo (`" . implode("`, `", $gFieldList) . "`) VALUES (\"$tValueStr\")";

        $tCount = $gDb->exec($tSql);
        if ($tCount != 1) {
            echo "\nRecord: $tRec \n";
            $tmp = $gDb->errorInfo();
            echo "$tmp[2]\n";
            if ($cgDebug) {
                echo "$tSql \n";
                # var_dump($tValueStr);
                throw new Exception("Insert error. [import-tcsv-2-lo-db.php:" . __LINE__ . "]");
            }
        }
    } # while
    echo "\nProcessed: $tRec \n";
} # fInsertRec

# ****************************************
# Includes, GetOps, Validate, ReadOnly

try {
    fGetOps();
    fValidate();
} catch(Exception $e) {
    echo "Problem with setup: " . $e->getMessage() . "\n";
    exit(2);     # ---------->
}

# Write section
try {
    fCreateTable();
    fInsertRec();
} catch(Exception $e) {
    echo "Problem creating table: " . $e->getMessage() . "\n";
    echo "Concider restoring $cgDbLo from $gBackupName\n";
    exit(3);     # ---------->
}

?>
