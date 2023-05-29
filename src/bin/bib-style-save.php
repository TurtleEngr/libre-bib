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

 $Revision: 1.5 $ $Date: 2023/05/29 02:54:22 $ GMT

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
    uFixBool();

    return;    # ---------->
} # fGetOps

# -----------------------------
function fValidate() {
    global $cgDocFile;
    global $cgDbBib;
    global $cgBin;
    global $cgDirApp;

    # DB not used
    #uValidateCommon();

    if ( ! file_exists("$cgDocFile"))
        throw new Exception("Missing: cgDocFile $cgDocFile [" . __LINE__ . "]");

    return;    # ---------->
} # fValidate

# -----------------------------
function fProcessStyleLine($pLine) {
    $tStartRegEx = '/<text:bibliography-configuration text:prefix=""/';
    $tEndRegEx1   = '/ \/>$/';
    $tEndRegEx2   = '/>$/';
    $tEndRegEx3   = '/<\/text:bibliography-configuration>/';

    # The first '/>$' ends bibliography-configuration
    # If '>$' is seen, then don't look for '/>$', look for end tag

    if (preg_match($tStartRegEx, $pLine))
        return "start";    # ---------->

    if (preg_match($tEndRegEx1, $pLine))
        return "end1";    # ---------->

    if (preg_match($tEndRegEx3, $pLine))
        return "end3";    # ---------->

    if (preg_match($tEndRegEx2, $pLine))
        return "end2";    # ---------->

    return "";    # ---------->
} # fProcessStyleLine

# -----------------------------
function fProcessStyleFile() {
    global $gInH;
    global $gOutH;
    global $cgDebug;
    global $cgDocFile;
    global $cgDirEtc;
    global $cgDirTmp;

    # This assumes there is only one "bibliography-configuration" tag
    # in the styles.xml file.

    echo "Start processing styles.xml [" . __LINE__ . "]\n";

    $gInH = fopen("$cgDirTmp/styles.xml", 'r');
    $gOutH = fopen("$cgDirEtc/bib-style.xml", 'w');

    $tFound = 0;
    $tIn = 0;
    $tInAttr = 0;
    $tNumLine = 0;
    while ($tLine = fgets($gInH)) {
        ++$tNumLine;
        if ($tNumLine % 100 == 0)
            echo '.';

        $tResult = fProcessStyleLine($tLine);
        if ($tResult == "start")
            $tIn = $tInAttr = $tFound = 1;

        if ($tIn)
            fputs($gOutH, $tLine);

        # if '/>', done
        if ($tIn && $tInAttr && $tResult == "end1")
            break;

        # if '>', now just look for end tag
        if ($tIn && $tInAttr && $tResult == "end2")
            $tInAttr = 0;

        # End tag?
        if ($tIn && $tResult == "end3")
            break;
    }
    echo "\nProcessed $tNumLine lines in styles.xml. [" . __LINE__ . "]\n";

    if ( ! $tFound)
        throw new Exception("Error: A bibliography has not been added to $cgDocFile. [" . __LINE__ . "]");

    fclose($gInH);
    fclose($gOutH);

    return;    # ---------->
} # fProcessStyleFile

# -----------------------------
function fProcessContentLine($pLine) {
    $tStartRegEx = '/<text:bibliography-source>/';
    $tEndRegEx   = '<\/text:bibliography-source>';

    if (preg_match($tStartRegEx, $pLine))
        return "start";    # ---------->

    if (preg_match($tEndRegEx, $pLine))
        return "end";    # ---------->

    return "";    # ---------->
} # fProcessContentLine

# -----------------------------
function fProcessContentFile() {
    global $gInH;
    global $gOutH;
    global $cgDebug;
    global $cgDocFile;
    global $cgDirEtc;
    global $cgDirTmp;

    # This assumes there is only one "bibliography-source" tag in the
    # content.xml file.

    echo "Start processing content.xml [" . __LINE__ . "]\n";

    $gInH = fopen("$cgDirTmp/content.xml", 'r');
    $gOutH = fopen("$cgDirEtc/bib-template.xml", 'w');

    $tFound = 0;
    $tIn = 0;
    $tNumLine = 0;
    while ($tLine = fgets($gInH)) {
        ++$tNumLine;
        if ($tNumLine % 500 == 0)
            echo '.';

        $tResult = fProcessContentLine($tLine);
        if ($tResult == "start")
            $tIn = $tFound = 1;

        if ($tIn)
            fputs($gOutH, $tLine);

        if ($tIn && $tResult == "end")
            break;
    }
    echo "\nProcessed $tNumLine lines in content.xml. [" . __LINE__ . "]\n";

    if ( ! $tFound)
        throw new Exception("Error: A bibliography has not been added to $cgDocFile. [" . __LINE__ . "]");

    fclose($gInH);
    fclose($gOutH);

    return;    # ---------->
} # fProcessContentFile

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
    uUnpackFile($cgDocFile, "content styles");
    fProcessStyleFile();
    fProcessContentFile();
} catch(Exception $e) {
    echo "Problem: " . $e->getMessage() . " ["
        . __LINE__ . "]\n";
    exit(4);    # ---------->
}

echo "Done. [" . __LINE__ . "]\n";
exit(0);    # ---------->
?>
