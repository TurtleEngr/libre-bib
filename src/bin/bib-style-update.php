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

bib-style-update.php - Update the biliography style settings

=head1 SYNOPSIS

 ./bib-style-update.php -c [-h]

=head1 DESCRIPTION

Update the biliography style settings in cgDirEtc/: bib-style.xml,
bib-template.xml

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

=for comment =head1 HISTORY

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
    global $cgDirEtc;

    # DB not used
    #uValidateCommon();

    if ( ! file_exists("$cgDirEtc/bib-style.xml"))
        throw new Exception("Missing: $cgDirEtc/bib-style.xml [bib-style-update.php:" . __LINE__ . "]");

    if ( ! file_exists("$cgDirEtc/bib-template.xml"))
        throw new Exception("Missing: $cgDirEtc/bib-template.xml [bib-style-update.php:" . __LINE__ . "]");

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
    global $gEtcH;
    global $cgDebug;
    global $cgDocFile;
    global $cgDirEtc;
    global $cgDirTmp;

    # This assumes there is only one "bibliography-configuration" tag
    # in the styles.xml file.

    echo "Start processing styles.xml [bib-style-update.php:" . __LINE__ . "]\n";

    $gInH = fopen("$cgDirTmp/styles.xml", 'r');
    $gNewH = fopen("$cgDirEtc/bib-style.xml", 'r');
    $gOutH = fopen("$cgDirTmp/styles.new.xml", 'w');

    $tFound = 0;
    $tIn = 0;
    $tInAttr = 0;
    $tNumLine = 0;
    while ($tLine = fgets($gInH)) {
        ++$tNumLine;
        if ($tNumLine % 100 == 0)
            echo '.';

        $tResult = fProcessStyleLine($tLine);
        if ($tResult == "start") {
            $tIn = $tInAttr = $tFound = 1;
            while ($tNewLine = fgets($gNewH))
                fputs($gOutH, $tNewLine);
            continue;
        }

        # Skip over the old section
        if ( ! $tIn)
            fputs($gOutH, $tLine);

        # if '/>', done
        if ($tIn && $tInAttr && $tResult == "end1")
            $tIn = $tInAttr = 0;

        # if '>', now just look for end tag
        if ($tIn && $tInAttr && $tResult == "end2")
            $tInAttr = 0;

        # End tag?
        if ($tIn && $tResult == "end3")
            $tIn = 0;
    }
    echo "\nProcessed $tNumLine lines in styles.xml. [bib-style-update.php:" . __LINE__ . "]\n";

    if ( ! $tFound)
        throw new Exception("Error: A bibliography has not been added to $cgDocFile. [bib-style-update.php:" . __LINE__ . "]");

    fclose($gInH);
    fclose($gNewH);
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

    echo "Start processing content.xml [bib-style-update.php:" . __LINE__ . "]\n";

    $gInH = fopen("$cgDirTmp/content.xml", 'r');
    $gNewH = fopen("$cgDirEtc/bib-template.xml", 'r');
    $gOutH = fopen("$cgDirTmp/content.new.xml", 'w');

    $tFound = 0;
    $tIn = 0;
    $tNumLine = 0;
    while ($tLine = fgets($gInH)) {
        ++$tNumLine;
        if ($tNumLine % 500 == 0)
            echo '.';

        $tResult = fProcessContentLine($tLine);
        if ($tResult == "start") {
            $tIn = $tFound = 1;
            while ($tNewLine = fgets($gNewH))
                fputs($gOutH, $tNewLine);
            continue;
        }

        # Skip over the old section
        if ( ! $tIn)
            fputs($gOutH, $tLine);

        if ($tIn && $tResult == "end")
            $tIn = 0;
    }
    echo "\nProcessed $tNumLine lines in content.xml. [bib-style-update.php:" . __LINE__ . "]\n";

    if ( ! $tFound)
        throw new Exception("Error: A bibliography has not been added to $cgDocFile. [bib-style-update.php:" . __LINE__ . "]");

    fclose($gInH);
    fclose($gNewH);
    fclose($gOutH);

    return;    # ---------->
} # fProcessContentFile

# ========================================
# Includes, GetOps, Validate, ReadOnly

try {
    fGetOps();
    fValidate();
} catch(Exception $e) {
    echo "Problem with setup: " . $e->getMessage() . " [bib-style-update.php:" . __LINE__ . "]\n";
    exit(3);    # ---------->
}

# ========================================
# Write section
try {
    uUnpackFile($cgDocFile, "content styles");
    fProcessStyleFile();
    fProcessContentFile();
    uPackFile($cgDocFile, "content styles");
} catch(Exception $e) {
    echo "Problem: " . $e->getMessage() . " [bib-style-update.php:"
        . __LINE__ . "]\n";
    exit(4);    # ---------->
}

echo "Done. [bib-style-update.php:" . __LINE__ . "]\n";
exit(0);    # ---------->
?>
