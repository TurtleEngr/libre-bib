#!/usr/bin/php
<?php

echo "Add -s and -b options"
exit(1);

# -----------------------------
function fusage() {
    global $argc;
    global $argv;
    
    system("pod2text $argv[0]");
    exit(1);

/* ...

=pod

=head1 NAME

import-tcsv-2-lo-db.php - create lo DB from tsv file

=head1 SYNOPSIS

 ./import-tcsv-2-lo-db.php -c Conf.php -i File.tsv -t lo
                           [-n] [-v] [-d] [-h]

=head1 DESCRIPTION

[Describe the script's purpose]

=head1 OPTIONS

=over 4

=item B<-c Conf.php>

Define these vars:

 $gDBName = "biblio_db";
 $gHost = "127.0.0.1";
 $gPassHint = "b4n";
 $gPassCache = ".pass.tmp";
 $gPortLocal = "3306";
 $gPortRemote = "3308";
 $gUserName = "bruce";
 $gDsn = "mysql:dbname=biblio_db;host=127.0.0.1;port=3308;charset=UTF8";

=item B<-i File.tsv>

Input file. Header will be used for field names.

=item B<-t Table>

Table to be created.

=item B<-n> - noexecute

Not imp.

If defined, the script will run everything it can, but not execute any
write operations.

=item B<-v> - verbose

Not imp.

Verbose output.

=item B<-d> - debug

Turn debug code on.

=item B<-h> - help

This help.

=back

=for comment =head1 RETURN VALUE

=head1 ERRORS

Does the conf file exist?

Values i the conf file?

Do expected files exist?

Is the ssh tunnel setup?

Does the DB exist?

Does the user have grants needed to access DB and it's tables?

=for comment =head1 EXAMPLES

=head1 ENVIRONMENT

=head1 FILES

.pass.tmp, conf.php, mkconf.sh, util.php

=head1 SEE ALSO

Makefile, mkver.pl

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

=head1 HISTORY

$Revision: 1.1 $ $Date: 2023/05/11 20:16:16 $ GMT 

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
    global $gpConf;
    global $gpDebug;
    global $gpHelp;
    global $gpNoExec;
    global $gpTable;
    global $gpTsvFile;
    global $gpVerbose;

    $gpConf = "conf.php";
    $gpDebug = false;
    $gpHelp = false;
    $gpNoExec = false;
    $gpTable = "lo";
    $gpTsvFile = '';
    $gpVerbose = false;

    $tOpt = getopt("c:i:t:ndvh");
    
    if (isset($tOpt['c']))
        $gpConf = $tOpt['c'];

    if (isset($tOpt['i']))
        $gpTsvFile = $tOpt['i'];

    if (isset($tOpt['t']))
        $gpTable = $tOpt['t'];

    $gpNoExec = isset($tOpt['n']);
    $gpDebug = isset($tOpt['d']);
    $gpVerbose = isset($tOpt['v']);
    $gpHelp = isset($tOpt['h']);

    if ($gpHelp or $argc < 2)
        fUsage();
    
    if ($gpDebug)
        echo "Debug is on. " . __FILE__ . "[" . __LINE__ . "]\n";
}

# -----------------------------
function fValidate() {
    global $gDb;
    global $gDsn;
    global $gFiileH;
    global $gPassCache;
    global $gPassword;
    global $gUserName;
    global $gpConf;
    global $gpDebug;
    global $gpTsvFile;

    if ("$gpConf" == "")
        throw new Exception("Error: Missing -c option. [" . __LINE__ . "]");

    if ($gpTsvFile == '')
        throw new Exception("Missing -i option. [" . __LINE__ . "]");

    if ("$gpTsvFile" == "")
        throw new Exception("Error: Missing -t option. [" . __LINE__ . "]");
    
    if (! file_exists("$gpConf"))
        throw new Exception("Error: Bad -c option. [" . __LINE__ . "]");
    require_once($gpConf);

    if (! file_exists("bin/util.php"))
        throw new Exception("Error: Missing bin/util.php [" . __LINE__ . "]");
    require_once("bin/util.php");

    if ("$gDsn" == "")
        throw new Exception("Error: Missing gDsn. [" . __LINE__ . "]");
        
    if ("$gPassCache" == "")
        throw new Exception("Error: Missing gPassCache. [" . __LINE__ . "]");

    if ("$gUserName" == "")
        throw new Exception("Error: Missing gUserName. [" . __LINE__ . "]");

    if (! file_exists("$gPassCache"))
        throw new Exception("Missing: gPassCache file: $gPassCache. Run make connect [" . __LINE__ . "]");
    $gPassword = rtrim(shell_exec("/bin/bash -c 'cat $gPassCache'"));
    
    if ("$gPassword" == "")
        throw new Exception("Error: password is not in $gPassCache. [" . __LINE__ . "]");
    
    if (($gFiileH = fopen($gpTsvFile, "r")) == FALSE)
        throw new Exception("Cannot open $gpTsvFile. [" . __LINE__ . "]");

    # Create database connection
    if ($gpDebug) echo "$gDsn, $gUserName \n";
    $gDb = new PDO($gDsn, $gUserName, $gPassword);
}

# -----------------------------
function fCreateTable() {
    global $gFieldList;
    global $gFiileH;
    global $gpTable;

    #  Get the fields from the first row
    $tTmpList = fgetcsv($gFiileH,15000,"\t");

    $gFieldList = array();
    foreach ($tTmpList as $tField) {
        $tField = preg_replace('/\s+/', '_', $tField);
        array_push($gFieldList, $tField);
    }

    # Create the table from header record
    $tSql = "CREATE TABLE IF NOT EXISTS $gpTable (";
    foreach ($gFieldList as $tField)
        $tSql .= "`$tField` VARCHAR(255),";
    $tSql = rtrim($tSql, ",") . ")";
    fExecSql("$tSql");
    fExecSql("alter table $gpTable add primary key (Identifier)");
}

# -----------------------------
function fInsertRec() {
    global $gDb;
    global $gFieldList;
    global $gFiileH;
    global $gpTable;
    global $gpDebug;

    # Use this to trim all fields in array to be < 254 char
    $fTrimLen = function($pElement) {
        return substr( $pElement, 0, 254 );
    };
    
    # Insert data into the table
    $tRec = 0;
    while (($tData = fgetcsv($gFiileH,15000,"\t")) !== FALSE) {
        ++$tRec;
        echo ".";
        $tData = array_map($fTrimLen, $tData);
        $tValueStr = implode('","', $tData);
        $tValueStr = utf8_encode($tValueStr);

        $tSql = "INSERT INTO $gpTable (`" . implode("`, `", $gFieldList) . "`) VALUES (\"$tValueStr\")";

        $tCount = $gDb->exec($tSql);
        if ($tCount != 1) {
            echo "\nRecord: $tRec \n";
            $tmp = $gDb->errorInfo();
            echo "$tmp[2]\n";
            if ($gpDebug) {
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
