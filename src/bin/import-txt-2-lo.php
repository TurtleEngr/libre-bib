#!/usr/bin/env php
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

 ./import-txt-2lo.php [-h]

=head1 DESCRIPTION

Import file cgLoFile to table cgDbLo, in DB cgDbName.

All records blocks in cgLoFile must start with: "Id:"

=head1 OPTIONS

See also ENVIRONMENT section.

=over 4

=item B<-h> - help

This help.

=back

=for comment =head1 RETURN VALUE

=for comment =head1 ERRORS

=for comment =head1 EXAMPLES

=head1 ENVIRONMENT

Set these in conf.env

    cgLoFile
    cgDbLo

=for comment =head1 FILES

=for comment =head1 =head1 SEE ALSO

=for comment =head1 NOTES

=for comment =head1 CAVEATS

=for comment =head1 DIAGNOSTICS

=for comment =head1 BUGS

=for comment =head1 RESTRICTIONS

=for comment =head1 AUTHOR

=head1 HISTORY

$Revision: 1.4 $ $Date: 2023/05/28 01:09:05 $ GMT

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
    $tOpt = getopt("ch");
    $gpHelp = isset($tOpt['h']);
    if ($gpHelp or $argc < 2)
        fUsage();

    $tConf = $_ENV['cgDirApp'] . "/etc/conf.php";
    require_once "$tConf";
    require_once "$cgBin/util.php";
    fFixBool();

} # fGetOps

# -----------------------------
function fValidate() {
    global $cgBin;
    global $gFileH;
    global $cgLoFile;

    fValidateCommon();

    if ( ! file_exists("$cgLoFile"))
        throw new Exception("Error: Missing file: $cgLoFile. [" . __LINE__ . "]");
    if (($gFileH = fopen($cgLoFile, "r")) == FALSE)
        throw new Exception("Cannot open $cgLoFile. [" . __LINE__ . "]");
} # fValidate

# -----------------------------
function fCreateTable() {
    global $cgDbLo;
    global $gBackupName;

    $gBackupName = "";
    if (fTableExists($cgDbLo))
        $gBackupName = fRenameTable($cgDbLo);

    if (fTableExists($cgDbLo))
        fExecSql("drop table $cgDbLo");

    $cLoCol = fLoCol();
    $tSql = "CREATE TABLE $cgDbLo (";
    foreach (array_values($cLoCol) as $tCol)
        $tSql .= "`$tCol` VARCHAR(255),";
    $tSql = rtrim($tSql, ",") . ")";

    fExecSql("$tSql");
    fExecSql("alter table $cgDbLo add primary key (Identifier)");
} # fCreateTable

# -----------------------------
function fInsertRec($pRec) {
    global $cgDbLo;
    global $gNumLine;
    global $gNumRec;

    $tSql = "INSERT INTO $cgDbLo (`" .
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
    global $cgDebug;

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

    if ($cgDebug) print_r($tData);
    return $tData;    # ---------->
} # fParseLine($pLine)

# -----------------------------
function fAddRec($pRec) {
    global $gNumRec;;

    ++$gNumRec;
    if ($gNumRec % 50 == 0)
        echo "+";

    # Fixup Type/RepType mess (i.e. override Type setting)
    if ($pRec["RepType"] == "") {
        if (is_numeric($pRec["Type"])) {
            $pRec["RepType"] = fType2Txt($pRec["Type"]);
        } else {
            echo "Warning: Missing Media and Type in " .
                $pRec["Identifier"] . " [" . __LINE__ . "]\n";
            $pRec["RepType"] = "unknown";
        }
    }
    $pRec["Type"] = fRepType2Type($pRec["RepType"]);

    fInsertRec($pRec);
} # fAddRec

# -----------------------------
function fImportTxt() {
    global $gFileH;
    global $gNumLine;
    global $gNumRec;
    global $cgDebug;
    global $cgDbLo;
    global $cgVerbose;

    $tRec = fLoColValue();

    # Get lines from txt file
    $gNumLine = 0;
    $gNumRec = 0;
    while ($tLine = fgets($gFileH)) {
        if ($cgVerbose)
            if ($gNumLine % 500 == 0)
                echo ".";
            ++$gNumLine;

        if ($cgDebug) echo "Read: $tLine\n";

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
            fAddRec($tRec);
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

    # Don't forget the last record!
    fAddRec($tRec);

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
    echo "Concider restoring $cgDbLo from $gBackupName\n";
    exit(2);     # ---------->
}

exit(0);
?>
