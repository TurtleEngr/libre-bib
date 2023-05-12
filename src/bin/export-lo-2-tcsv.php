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

export-lo-2-tcvs.php - export lo db to csv or tsv file

=head1 SYNOPSIS

 ./export-lo-2-tcvs.php [-c Conf] [-t Table] [-s Sep] [-o OutFile]
                        [-n] [-v] [-d] [-h]

=head1 DESCRIPTION

[Describe the script's purpose]

=head1 OPTIONS

=over 4

=item B<-c Conf>

Default: config/conf.php

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

=item B<-s Sep>

Separator. c - comma; t - tab. Default: c

=item B<-o OutFile>

Name of csv or tsv file to be output. Default: lo.csv

=item B<-n> - noexecute

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

.pass.tmp, config/conf.php, bin/util.php, /usr/local/bin/mkconf.sh,
config/ref-biblio-col.csv

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

$Revision: 1.2 $ $Date: 2023/05/12 02:46:39 $ GMT 

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
    global $gpConf;
    global $gpDebug;
    global $gpFile;
    global $gpHelp;
    global $gpNoExec;
    global $gpSep;
    global $gpTable;
    global $gpVerbose;

    $gpConf = "config/conf.php";
    $gpDebug = false;
    $gpHelp = false;
    $gpFile = "lo.csv";
    $gpNoExec = false;
    $gpSep = 'c';
    $gSep = ",";
    $gpTable = "lo";
    $gpVerbose = false;

    $tOpt = getopt("c:o:s:t:ndvh");
    
    if (isset($tOpt['c']))
        $gpConf = $tOpt['c'];

    if (isset($tOpt['o']))
        $gpFile = $tOpt['o'];
        
    if (isset($tOpt['s']))
        $gpSep = $tOpt['s'];

    if (isset($tOpt['t']))
        $gpTable = $tOpt['t'];

    $gpNoExec = isset($tOpt['n']);
    $gpDebug = isset($tOpt['d']);
    $gpVerbose = isset($tOpt['v']);
    $gpHelp = isset($tOpt['h']);

    if ($gpHelp or $argc < 2)
        fUsage();
    
    if ($gpDebug)
        echo "Debug is on.\n" . __FILE__ . "[" . __LINE__ . "]\n";
} # fGetOps

# -----------------------------
function fValidate() {
    global $gDb;
    global $gDsn;
    global $gPassCache;
    global $gPassword;
    global $gUserName;
    global $gSep;
    global $gpConf;
    global $gpDebug;
    global $gpFile;
    global $gpSep;
    global $gpTable;

    if ("$gpConf" == "")
        throw new Exception("Error: Missing -c option. [" . __LINE__ . "]");

    if (! file_exists("$gpConf"))
        throw new Exception("Error: Bad -c option. [" . __LINE__ . "]");
    require_once($gpConf);

    if (! file_exists("bin/util.php"))
        throw new Exception("Error: Missing bin/util.php [" . __LINE__ . "]");
    require_once("bin/util.php");
    
    if ("$gpTable" == "")
        throw new Exception("Error: Missing -t option. [" . __LINE__ . "]");

    if ("$gpSep" == "")
        throw new Exception("Error: Missing -s option. [" . __LINE__ . "]");
    switch ($gpSep) {
        case "c":
            $gSep = ",";
            break;
        case "t":
            $gSep = "\t";
            break;
        default:
            throw new Exception("Error: Bad -s. Should be 'c' or 's'. [" . __LINE__ . "]");
    }

    if ("$gpFile" == "")
        throw new Exception("Error: Missing -o option. [" . __LINE__ . "]");

    if (! file_exists("src/ref-biblio-col.csv"))
        throw new Exception("Error: Missing file: src/ref-biblio-col.csv [" . __LINE__ . "]");

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
    
    # Create database connection
    if ($gpDebug) echo "$gDsn, $gUserName \n";
    $gDb = new PDO($gDsn, $gUserName, $gPassword);

    if (! fTableExists($gpTable))
        throw new Exception("Error: -t Table $gpTable does not exist. [" . __LINE__ . "]");
} # fValidate

# -----------------------------
function fExportTable() {
    global $gDb;
    global $gpDebug;
    global $gpFile;
    global $gSep;
    global $gpTable;

    # Get header "official" header from src/biblio.dbf
    shell_exec("/bin/bash -c 'head -n 1 src/ref-biblio-col.csv >$gpFile'");

    if (($tFileH = fopen($gpFile, "a")) == FALSE)
        throw new Exception("Cannot write to $gpFile. [" . __LINE__ . "]");

    # Get all columns
    $tSql= "select * from bib";
    $tRecH = $gDb->prepare($tSql);
    $tRecH->execute();

    # Get each record and output the csv line
    $tCount = 0;
    while ($tRec = $tRecH->fetch(PDO::FETCH_ASSOC)) {
        echo ".";
        ++$tCount;
        if (! fputcsv($tFileH, array_values($tRec), $gSep))
            throw new Exception("Error writing: record $tCount. [" . __LINE__ . "]");
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
    exit(1);
}

# Write section
try {
    fExportTable();
} catch(Exception $e) {
    echo "Problem creating table: " . $e->getMessage() . "\n";
    exit(2);
}

exit(0);
?>
