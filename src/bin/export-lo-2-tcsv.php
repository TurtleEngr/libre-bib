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

export-lo-2-tcvs.php - export lo db to csv or tsv file

=head1 SYNOPSIS

 ./export-lo-2-tcvs.php [-s Sep] [-h]

=head1 DESCRIPTION

Export the cgDbLo table to file cgBackupFile. Which is usally put in
cgDirBackup. Copy cgBackupFile before running this, if you want to
keep it.

=head1 OPTIONS

See also ENVIRONMENT section.

=over 4

=item B<-s Sep>

Separator. c - comma; t - tab. Default: c

=item B<-h> - help

This help.

=back

=for comment =head1 RETURN VALUE

=for comment =head1 ERRORS

=for comment =head1 EXAMPLES

=head1 ENVIRONMENT

    cgDbLo
    cgBackupFile

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
} # fUsage

# -----------------------------
function fCleanUp() {
    echo "\n";
} # fCleanUp

# -----------------------------
function fGetOps() {
    global $argc;
    global $argv;
    global $gSep;
    global $cgDebug;
    global $cgBackupFile;
    global $gpHelp;
    global $cgNoExec;
    global $gpSep;
    global $cgDbLo;
    global $cgVerbose;

    $gpHelp = false;
    $gpSep = 'c';
    $gSep = ",";
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
    global $cgDbLo;

    uValidateCommon();

    if ("$gpSep" == "")
        throw new Exception("\nError: Missing -s option. [export-lo-2-tcsv.php:" . __LINE__ . "]");
    switch ($gpSep) {
    case "c":
        $gSep = ",";
        break;
    case "t":
        $gSep = "\t";
        break;
    default:
        throw new Exception("\nError: Bad -s. Should be 'c' or 's'. [export-lo-2-tcsv.php:" . __LINE__ . "]");
    }

    if ( ! uTableExists($cgDbLo))
        throw new Exception("\nError: -t Table $cgDbLo does not exist. [export-lo-2-tcsv.php:" . __LINE__ . "]");
} # fValidate

# -----------------------------
function fExportTable() {
    global $gDb;
    global $cgDebug;
    global $cgBackupFile;
    global $gSep;
    global $cgDbLo;
    global $cgDirApp;

    # Get header "official" header from src/biblio.dbf
    shell_exec("/bin/bash -c 'head -n 1 $cgDirApp/etc/lo-schema.csv >$cgBackupFile'");

    if (($tFileH = fopen($cgBackupFile, "a")) == FALSE)
        throw new Exception("Cannot write to $cgBackupFile. [export-lo-2-tcsv.php:" . __LINE__ . "]");

    # Get all columns
    $tSql = "select * from bib";
    $tRecH = $gDb->prepare($tSql);
    $tRecH->execute();

    # Get each record and output the csv line
    $tCount = 0;
    while ($tRec = $tRecH->fetch(PDO::FETCH_ASSOC)) {
        echo ".";
        ++$tCount;
        if ( ! fputcsv($tFileH, array_values($tRec), $gSep))
            throw new Exception("Error writing: record $tCount. [export-lo-2-tcsv.php:" . __LINE__ . "]");
    } # while
    echo "\nProcessed: $tCount \n";
    fclose($tFileH);
} # fExportTable

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
    fExportTable();
} catch(Exception $e) {
    echo "Problem creating table: " . $e->getMessage() . "\n";
    exit(3);     # ---------->
}

exit(0);     # ---------->
?>
