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

convert-lo-2-bib.php - copy lo table to create partially formatted bib fields.

=head1 SYNOPSIS

 ./convert-lo-2-bib.php -c Conf.php -f FromTable -t ToTable [-b]
                        [-n] [-v] [-d] [-h]

=head1 DESCRIPTION

Generate the ToTable (bib) table from the FromTable (lo) table. If -b
option make a backup of the bib table

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

=item B<-f FromTable>

Source lo schema table to be copied. Default: lo

=item B<-t ToTable>

Destination lo schema table to be created FromTable. Default: bib

A number of non-empty column values are edited so they will print
correctly in a bibliography reference. For example, ", " may be put
before the value.

=item B<-b> - backup

Make a backup of the ToTable. This renames the current ToTable, if it
exists, to a name with a datestamp (_YYYY-MM-DD_HH-MM-SS)
appended. For example, bib -> bib_2023-04-02_14-18-37

You will need to manually drop the backup tables you don't want.

Example restore from a backup table:

  drop table `bib`;
  RENAME TABLE `bib_2023-04-02_14-18-37` TO bib;

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

Values in the conf file?

Is the ssh tunnel setup?

Does the DB exist?

Does the user have grants needed to access DB and it's tables?

Do expected files exist?

=for comment =head1 EXAMPLES

=head1 ENVIRONMENT

=head1 FILES

.pass.tmp, conf.php, mkconf.sh util.php

=head1 SEE ALSO

Makefile, /usr/local/bin/mkver.pl

=head1 NOTES

 https://www.php.net/manual/en/book.pdo.php

 alter table bib add primary key (Identifier);

=for comment =head1 CAVEATS

=for comment =head1 DIAGNOSTICS

=for comment =head1 BUGS

=for comment =head1 RESTRICTIONS

=for comment =head1 AUTHOR

=head1 HISTORY

$Revision: 1.1 $ $Date: 2023/05/11 20:16:15 $ GMT 

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
    global $gpFromTable;
    global $gpHelp;
    global $gpNoExec;
    global $gpToTable;
    global $gpVerbose;

    $gpBackup = false;
    $gpConf = "conf.php";
    $gpDebug = false;
    $gpFromTable = "lo";
    $gpHelp = false;
    $gpNoExec = false;
    $gpToTable = "bib";
    $gpVerbose = false;

    $tOpt = getopt("bc:f:t:ndvh");
    
    $gpBackup = isset($tOpt['b']);
    
    if (isset($tOpt['c']))
        $gpConf = $tOpt['c'];

    if (isset($tOpt['f']))
        $gpFromTable = $tOpt['f'];
        
    if (isset($tOpt['t']))
        $gpToTable = $tOpt['t'];
        
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
    global $gpBackup;
    global $gpConf;
    global $gpDebug;
    global $gpFromTable;
    global $gpToTable;
    global $gpVerbose;

    if ("$gpConf" == "")
        throw new Exception("Error: Missing -c option. [" . __LINE__ . "]");

    if (! file_exists("$gpConf"))
        throw new Exception("Error: Bad -c option. [" . __LINE__ . "]");
    require_once($gpConf);

    if (! file_exists("bin/util.php"))
        throw new Exception("Error: Missing bin/util.php [" . __LINE__ . "]");
    require_once("bin/util.php");

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
    if ($gpDebug) { echo "$gDsn, $gUserName [" . __LINE__ . "]\n"; }
    $gDb = new PDO($gDsn, $gUserName, $gPassword);

    if (! fTableExists($gpFromTable))
        throw new Exception("Error: -f FromTable $gpFromTable does not exist. [" . __LINE__ . "]");
} # fValidate

# -----------------------------
function fCreateToTable() {
    global $gpBackup;
    global $gpFromTable;
    global $gpToTable;

    if ($gpBackup and fTableExists($gpToTable))
        fRenameTable($gpToTable);

    if (fTableExists($gpToTable))
        fExecSql("drop table $gpToTable");
    
    fExecSql("CREATE TABLE $gpToTable SELECT * FROM $gpFromTable");
    fExecSql("alter table $gpToTable add primary key (Identifier)");
} # fCreateToTable

# -----------------------------
function fUpdateRec($pRec) {
    global $gpToTable;

    $tSql = "update $gpToTable set";
    foreach(array_keys($pRec) as $tCol) {
        if ($pRec[$tCol] == '')
            continue;
        switch ($tCol) {
            case "Identifier":
            case "Type":
            case "Annote":
            case "Booktitle":
            case "Title":
            case "Note":
            case "Custom1":
            case "Custom2":
            case "Custom3":
                # These are not changed
                continue 2;
            case "Author":
                # Update only if Authors added
                if (! preg_match("/; /", $pRec['Author']))
                    continue 2;
        }
        $tSql .= ' ' . $tCol . ' = "' . $pRec[$tCol] . '",';
    }
    $tSql = preg_replace('/",$/', '"', $tSql);
    $tSql .= ' where Identifier = "' . $pRec['Identifier'] . '"';

    fExecSql($tSql);
} # fUpdateRec

# -----------------------------
function fUpdateToTable() {
    global $gDb;
    global $gpToTable;
    global $gpDebug;

    # Get col to be updated
    $tSql = "select * from $gpToTable";
    $tRecH = $gDb->prepare($tSql);
    $tRecH->execute();

    $tCount = 0;
    while ($tRec = $tRecH->fetch(PDO::FETCH_ASSOC)) {
        ++$tCount;
        if ($tCount % 50 == 0)
            echo ".";
        
        # Put a ', ' before each non-blank column, but process
        # certain col differently.
        foreach (array_keys($tRec) as $tCol) {
            if ($tRec[$tCol] == '')
                continue;
            switch ($tCol) {
                case "Identifier":
                case "Type":
                case "Annote":
                case "Booktitle":
                case "Title":
                case "Note":
                case "Custom1":
                case "Custom2":
                case "Custom3":
                    # These are not changed
                    break;
                case "URL":
                     $tRec[$tCol] = ', URL:' . $tRec[$tCol];
                     if ($tRec['Custom1'] != '')
                         $tRec[$tCol] .= '; Alt:' . $tRec['Custom1'];
                     break;
                case "Author":
                    if ($tRec['Custom2'] != '')
                        $tRec[$tCol] .= '; ' . $tRec['Custom2'];
                    break;
                case "Custom4":
                    $tRec[$tCol] = ', DateSeen:' . $tRec[$tCol];
                    break;
                default:
                    $tRec[$tCol] = ', ' . $tRec[$tCol];
            }
        }
        if ($tRec['ISBN'] == '' and $tRec['Custom3'] != '')
            $tRec['ISBN'] = ', ASIN:' . $tRec['Custom3'];
        fUpdateRec($tRec);
    } # while
    echo "\nProcessed: $tCount [" . __LINE__ . "]\n";
} # fUpdateToTable

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
    fCreateToTable();
    fUpdateToTable();
} catch(Exception $e) {
    echo "Problem creating table: " . $e->getMessage() . "\n";
    exit(2);
}

exit(0);
?>
