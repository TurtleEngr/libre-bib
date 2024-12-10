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
the cgDbBib table. This preprocessing makes it easier to use these fields
in the LibreOffice bibliography, because it only includes prefix and suffix
punctuation if a field is not empty.

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
        throw new Exception("\nError: Missing $cgDbLo Table. [convert-lo-2-bib.php:" . __LINE__ . "]");
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
        case "Address":
        case "Annote":
        case "Edition":
        case "Note":
        case "Title":
        case "Type":
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

    $tAltList =  array();

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
            case "Address":
            case "Annote":
            case "Edition":
            case "Note":
            case "Title":
            case "Type":
            case "Custom1":
            case "Custom2":
            case "Custom3":
                # These are not changed
                break;
            case "Booktitle":
                if ($tRec['Title'] != '')
                    $tRec[$tCol] .= ': ' . $tRec['Title'];
                if ($tRec['Edition'] != '')
                    $tRec[$tCol] .= ' (' . $tRec['Edition'] . ' ed.)';
                $tRec[$tCol] = ' ' . $tRec[$tCol] . '.';
                break;
            case "Author":
                if ($tRec['Custom2'] != '')
                    $tRec[$tCol] .= ', and ' . $tRec['Custom2'];
                $tRec[$tCol] = ' ' . $tRec[$tCol] . '.';
                break;
            case "Publisher":
                if ($tRec[$tCol] != '')
                    if ($tRec['Address'] != '')
                        $tRec[$tCol] = ' ' . $tRec['Address'] . ': ' . $tRec[$tCol] . '.';
                    else
                        $tRec[$tCol] = ' ' . $tRec[$tCol] . '.';
                break;
            case "ISBN":
                $tRec[$tCol] = ' ISBN:' . $tRec[$tCol] . '.';
                break;
            case "URL":
                $tRec[$tCol] = ' URL:' . $tRec[$tCol]';
                if ($tRec['Custom1'] != '') {
                    # Use only the first entry (space separator)
                    $tAltList = explode(' ', trim($tRec['Custom1']));
                    $tRec[$tCol] .= '  Alt:' . $tAltList[0];
                }
                break;
            case "Custom4":
                $tRec[$tCol] = ' Seen: ' . $tRec[$tCol] . '.';
                break;
            default:
                if ($tRec[$tCol] != '')
                    $tRec[$tCol] = ' ' . $tRec[$tCol] . '.';
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
