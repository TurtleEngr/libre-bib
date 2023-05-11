#!/usr/bin/php
<?php

# -----------------------------
function fusage() {
    global $argc;
    global $argv;
    
    system("pod2text $argv[0]");
    exit(1);

/* ...

=pod

=head1 NAME

import-txt-2lo.php - import biblio.txt to lo db

=head1 SYNOPSIS

 ./import-txt-2lo.php [-c Conf] [-t Table] [-b] [-i File.txt]
                      [-n] [-v] [-d] [-h]

=head1 DESCRIPTION

[Describe the script's purpose]

=head1 OPTIONS

=over 4

=item B<-c Conf>

Default: conf.php

Define these vars:

 $gDBName = "biblio_db";
 $gHost = "127.0.0.1";
 $gPassHint = "b4n";
 $gPassCache = ".pass.tmp";
 $gPortLocal = "3306";
 $gPortRemote = "3308";
 $gUserName = "bruce";
 $gDsn = "mysql:dbname=biblio_db;host=127.0.0.1;port=3308;charset=UTF8";

=item B<-t Table>

Table to be created. Default: lo

=item B<-b>

Backup the table before importing.

=item B<-i File.txt>

Output file name. Default: biblio.txt

All records must start with: "Id:"

=item B<-n> - noexecute

If defined, the script will run everything it can, but not execute any
write operations.

=item B<-v> - verbose

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

.pass.tmp, conf.php, /usr/local/bin/mkconf.sh, bin/util.php

=head1 SEE ALSO

Makefile, mkver.pl

=head1 NOTES

 https://www.php.net/manual/en/book.pdo.php

 alter table bib add primary key (Identifier);

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
    global $gpBackup;
    global $gpConf;
    global $gpDebug;
    global $gpHelp;
    global $gpNoExec;
    global $gpTable;
    global $gpFile;
    global $gpVerbose;

    $gpBackup = false;
    $gpConf = "conf.php";
    $gpDebug = false;
    $gpHelp = false;
    $gpNoExec = false;
    $gpTable = "lo";
    $gpFile = 'biblio.txt';
    $gpVerbose = false;

    $tOpt = getopt("bc:i:t:ndvh");
    
    $gpBackup = isset($tOpt['b']);
    
    if (isset($tOpt['c']))
        $gpConf = $tOpt['c'];

    if (isset($tOpt['i']))
        $gpFile = $tOpt['i'];

    if (isset($tOpt['t']))
        $gpTable = $tOpt['t'];

    $gpNoExec = isset($tOpt['n']);
    $gpDebug = isset($tOpt['d']);
    $gpVerbose = isset($tOpt['v']);
    $gpHelp = isset($tOpt['h']);

    if ($gpHelp or $argc < 2)
        fUsage();
    
    if ($gpDebug)
        echo "Debug is on. [" . __LINE__ . "]\n";
} # fGetOps

# -----------------------------
function fValidate() {
    global $gDb;
    global $gDsn;
    global $gPassCache;
    global $gPassword;
    global $gUserName;
    global $gFileH;
    global $gpBackup;
    global $gpConf;
    global $gpDebug;
    global $gpTable;
    global $gpFile;

    if ("$gpConf" == "")
        throw new Exception("Error: Missing -c option. [" . __LINE__ . "]");

    if (! file_exists("$gpConf"))
        throw new Exception("Error: Bad -c option. [" . __LINE__ . "]");
    require_once($gpConf);

    if (! file_exists("bin/util.php"))
        throw new Exception("Error: Missing: bin/util.php [" . __LINE__ . "]");
    require_once("bin/util.php");

    if ("$gpTable" == "")
        throw new Exception("Error: Missing -t option. [" . __LINE__ . "]");

    if ("$gpFile" == "")
        throw new Exception("Error: Missing -f option. [" . __LINE__ . "]");

    if (! file_exists("$gpFile"))
        throw new Exception("Error: Missing file: $gpFile. [" . __LINE__ . "]");

    if (($gFileH = fopen($gpFile, "r")) == FALSE)
        throw new Exception("Cannot open $gpFile. [" . __LINE__ . "]");
        
    if ("$gDsn" == "")
        throw new Exception("Error: Missing gDsn. [" . __LINE__ . "]");

    if ("$gPassCache" == "")
        throw new Exception("Error: Missing gPassCache. Run make connect [" . __LINE__ . "]");

    if ("$gUserName" == "")
        throw new Exception("Error: Missing gUserName. [" . __LINE__ . "]");

    if (! file_exists("$gPassCache"))
        throw new Exception("Missing: gPassCache file: $gPassCache. Run make connect [" . __LINE__ . "]");

    $gPassword = rtrim(shell_exec("/bin/bash -c 'cat $gPassCache'"));
    if ("$gPassword" == "")
        throw new Exception("Error: password is not in $gPassCache. [" . __LINE__ . "]");
    
    if ($gpBackup)
        echo "Backup is on. [". __LINE__ . "]\n";
    else
        echo "Backup is off. [". __LINE__ . "]\n";
        
    # Create database connection
    if ($gpDebug) { echo "$gDsn, $gUserName \n"; }
    $gDb = new PDO($gDsn, $gUserName, $gPassword);

} # fValidate

# -----------------------------
function fCreateTable() {
    global $gBackupName;
    global $gpBackup;
    global $gpTable;
    global $gBackupName;

    $gBackupName = "";
    if ($gpBackup and fTableExists($gpTable))
        $gBackupName = fRenameTable($gpTable);

    if (fTableExists($gpTable))
        fExecSql("drop table $gpTable");

    $cLoCol = fLoCol();
    $tSql = "CREATE TABLE $gpTable (";
    foreach (array_values($cLoCol) as $tCol)
        $tSql .= "`$tCol` VARCHAR(255),";
    $tSql = rtrim($tSql, ",") . ")";
    
    fExecSql("$tSql");
    fExecSql("alter table $gpTable add primary key (Identifier)");
} # fCreateTable

# -----------------------------
function fInsertRec($pRec) {
    global $gpTable;
    global $gNumLine;
    global $gNumRec;

    $tSql = "INSERT INTO $gpTable (`" .
        implode("`, `", array_keys($pRec)) .
        "`) VALUES (\"" .
        implode('","', array_values($pRec)) .
        '")';
    if (fExecSql("$tSql") == false)
        throw new Exception("Error: FileLine: $gNumLine, Rec: $gNumRec Failed: $tSql [" . __LINE__ . "]");
} # fInsertRec

# -----------------------------
function fParseLine($pLine) {
    global $gNumLine;
    global $gNumRec;
    global $gpDebug;

    $tData = array("key"=>"", "val"=>"");

    $tKey = preg_replace("/: .*/", "", $pLine);
    if ($tKey == $pLine)
        return $tData;    # ---------->

    $tData["key"] = $tKey;

    $tVal = preg_replace("/^[^:]*: /", "", $pLine, 1);
    if ($tVal == $pLine)
        return $tData;    # ---------->

    $tVal = substr($tVal, 0, 254);
    $tVal = trim($tVal);
    $tData["val"] = $tVal;

    if ($gpDebug) print_r($tData);
    return $tData;    # ---------->
} # fParseLine($pLine)

# -----------------------------
function fImportTxt() {
    global $gFileH;
    global $gNumLine;
    global $gNumRec;
    global $gpDebug;
    global $gpTable;
    global $gpVerbose;
    
    $tRec = fLoColValue();

    # Get lines from txt file
    $gNumLine = 0;
    $gNumRec = 0;
    while ($tLine = fgets($gFileH)) {
        if ($gpVerbose)
            if ($gNumLine % 500 == 0)
                echo ".";
        ++$gNumLine;

        if ($gpDebug) echo "Read: $tLine\n";

        $tLine = trim($tLine);

        # Skip blank lines
        if ($tLine == "")
            continue;

        # Skip comment lines
        if (preg_match("/^#/", $tLine))
            continue;

        $tData = fParseLine($tLine);

        if ($tData["key"] == "") {
            echo "Warning: no key found at: FileLine: $gNumLine, Rec: $gNumRec [" . __LINE__ . "]\n";
            continue;
        }

        # If Id found, insert any previous record's values to DB
        if ($tData["key"] == "Id" and $tRec["Identifier"] != "") {
            ++$gNumRec;
            if ($gNumRec % 50 == 0)
                echo "+";

            # Fixup Type/RepType mess (i.e. override Type setting)
            if ($tRec["RepType"] == "") {
                if (is_numeric($tRec["Type"])) {
                    $tRec["RepType"] = fType2Txt($tRec["Type"]);
                } else {
                    echo "Warning: Missing Media and Type in " .
                        $tRec["Identifier"] . " [" . __LINE__ . "]\n";
                    $tRec["RepType"] = "unknown";
                }
            }
            $tRec["Type"] = fRepType2Type($tRec["RepType"]);

            fInsertRec($tRec);
            foreach (array_keys($tRec) as $tCol)
                $tRec["$tCol"] = "";
            if ($tData["val"] == "")
                throw new Exception("Error: No name after Id: at FileLine: $gNumLine, Rec: $gNumRec [" . __LINE__ . "]");
        }

        # Do nothing, already empty
        if ($tData["val"] == "")
            continue;

        $tKey = $tData["key"];
        $tLoCol = fTxt2LoMap($tKey);
        if ($tLoCol == "") {
            echo "Warning: $tKey not found in KeyMap at: FileLine: $gNumLine, Rec: $gNumRec [" . __LINE__ . "]\n";
            continue;
        }

        if ($tLoCol == "Type")
            $tData["val"] = fRepType2Type($tData["val"]);

        $tRec[$tLoCol] = $tData["val"];
    } # while
    echo "\nProcessed $gNumLine lines. [" . __LINE__ . "]\n";
    echo "Inserted $gNumRec records. [" . __LINE__ . "]\n";
    fclose($gFileH);
} # fImportTxt

# ****************************************
# Includes, GetOps, Validate, ReadOnly

try {
    fGetOps();
    fValidate();
} catch(Exception $e) {
    echo "Problem with setup: " . $e->getMessage() . "\n";
    exit(1);
}

# Write section
try {
    fCreateTable();
    fImportTxt();
} catch(Exception $e) {
    echo "Problem creating table: " . $e->getMessage() . "\n";
    if ($gpBackup)
        echo "Concider restoring $gpTable from $gBackupName\n";
    exit(2);
}

exit(0);
?>
