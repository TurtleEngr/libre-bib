#!/usr/bin/env php
<?php

# -----------------------------
function fusage() {
    global $argc;
    global $argv;

    system("pod2text $argv[0]");
    exit(1);    # ---------->

    /* ...

=pod

=head1 NAME

bib-style-save.php - extract the biliography style settings

=head1 SYNOPSIS

 ./bib-style-save.php -c [-h]

=head1 DESCRIPTION


=head1 OPTIONS

See also ENVIRONMENT section.

=over 4

=item B<-h> - help

This help.

=back

=for comment =head1 RETURN VALUE

=for comment =head1 ERRORS

=head1 ENVIRONMENT

=for comment =head1 FILES

=for comment =head1 SEE ALSO

=for comment =head1 NOTES

=for comment =head1 CAVEATS

=for comment =head1 DIAGNOSTICS

=for comment =head1 BUGS

=for comment =head1 RESTRICTIONS

=for comment =head1 AUTHOR

=head1 HISTORY

 $Revision: 1.1 $ $Date: 2023/05/28 01:09:04 $ GMT

=cut

... */
} # fUsage

# -----------------------------
function fCleanUp() {
    echo "\n";
    return;    # ---------->
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
    fFixBool();

    return;    # ---------->
} # fGetOps

# -----------------------------
function fValidate() {
    global $cgDocFile;
    global $cgDbBib;
    global $cgBin;
    global $cgDirApp;

    # DB not used
    #fValidateCommon();

    # $cgDocFile should exist

    return;    # ---------->
} # fValidate

# -----------------------------
function fProcessLine($pLine) {
    $tStartRegEx = '/<text:bibliography-configuration text:prefix=""/';
    $tEndRegEx1   = '/<\/text:bibliography-configuration>/';
    $tEndRegEx2   = '/ \/>$/';

    # Two end states: '<text:bibliography-configuration' ends with '
    # />$' after attributes. Or it ends with:
    # </text:bibliography-configuration>$'

    if (preg_match($tStartRegEx, $pLine))
        return "start";    # ---------->
    if (preg_match($tEndRegEx, $pLine))
        return "end";    # ---------->
    return "";    # ---------->
} # fProcessLine

# -----------------------------
function fProcessFile() {
    global $gInH;
    global $gOutH;
    global $gNumRef;
    global $cgDebug;
    global $cgDocFile;
    global $cgDirEtc;
    global $cgDirTmp;

    echo "Start processing [" . __LINE__ . "]\n";

    $gInH = fopen("$cgDirTmp/styles.xml", 'r');
    $gOutH = fopen("$cgDirEtc/bib-style.xml", 'w');

    $tFound = 0;
    $tIn = 0;
    $tNumLine = 0;
    while ($tLine = fgets($gInH)) {
        ++$tNumLine;
        $tResult = fProcessLine($tLine);
        if ($tResult == "start") {
            $tIn = 1;
            $tFound = 1;
        }
        if ($tIn)
            fputs($gOutH, $tLine);
        if ($tResult == "end")
            $tIn = 0;
    }
    echo "\nProcessed $tNumLine lines. [" . __LINE__ . "]\n";

    if ( ! $tFound)
        throw new Exception("Error: A bibliography has not been added to $cgDocFile. [" . __LINE__ . "]");

    fclose($gInH);
    fclose($gOutH);

    return;    # ---------->
} # fProcessFile

# ========================================
# Includes, GetOps, Validate, ReadOnly

try {
    fGetOps();
    fValidate();
} catch(Exception $e) {
    echo "Problem with setup: " . $e->getMessage() . " [" . __LINE__ . "]\n";
    exit(3);    # ---------->
}

# ========================================
# Write section
try {
    fUnpackFile($cgDocFile, "content styles");
    fProcessFile();
} catch(Exception $e) {
    echo "Problem: " . $e->getMessage() . " ["
        . __LINE__ . "]\n";
    exit(4);    # ---------->
}

echo "Done. [" . __LINE__ . "]\n";
exit(0);    # ---------->
?>
