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

convert-lo-2-bib.php - copy lo table to create partially formatted bib fields.

=head1 SYNOPSIS

 ./convert-lo-2-bib.php [-h]

=head1 DESCRIPTION

Generate the cgDbBib table from the $cgDbLo table. Make a backup of
the cgDbBib table.

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

    cgDbBib
    cgDbLo

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
    global $cgDebug;
    global $gpHelp;
    global $cgNoExec;

    $gpHelp = false;
    $tOpt = getopt("ch");
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
    global $cgBin;
    global $cgDbLo;

    uValidateCommon();

    if ( ! uTableExists($cgDbLo))
        throw new Exception("Error: Missing $cgDbLo Table. [convert-lo-2-bib.php:" . __LINE__ . "]");
} # fValidate

# -----------------------------
function fCreateBibTable() {
    global $cgDbLo;
    global $cgDbBib;

    if (uTableExists($cgDbBib))
        uRenameTable($cgDbBib);

    if (uTableExists($cgDbBib))
        uExecSql("drop table $cgDbBib");

    uExecSql("CREATE TABLE $cgDbBib SELECT * FROM $cgDbLo");
    uExecSql("alter table $cgDbBib add primary key (Identifier)");
} # fCreateBibTable

# -----------------------------
function fUpdateRec($pRec) {
    global $cgDbBib;

    $tSql = "update $cgDbBib set";
    foreach (array_keys($pRec) as $tCol) {
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
            if ( ! preg_match("/; /", $pRec['Author']))
                continue 2;
        }
        $tSql .= ' ' . $tCol . ' = "' . $pRec[$tCol] . '",';
    }
    $tSql = preg_replace('/",$/', '"', $tSql);
    $tSql .= ' where Identifier = "' . $pRec['Identifier'] . '"';

    uExecSql($tSql);
} # fUpdateRec

# -----------------------------
function fUpdateBibTable() {
    global $gDb;
    global $cgDbBib;
    global $cgDebug;

    # Get col to be updated
    $tSql = "select * from $cgDbBib";
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
    echo "\nProcessed: $tCount [convert-lo-2-bib.php:" . __LINE__ . "]\n";
} # fUpdateBibTable

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
    fCreateBibTable();
    fUpdateBibTable();
} catch(Exception $e) {
    echo "Problem creating table: " . $e->getMessage() . "\n";
    exit(2);
}

exit(0);
?>
