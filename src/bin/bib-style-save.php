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
    global $cgDbTblBib;
    global $cgBin;
    global $cgDirApp;

    # DB not used
    #uValidateCommon();

    if ( ! file_exists("$cgDocFile"))
        throw new Exception("Missing: cgDocFile $cgDocFile [bib-style-save.php:" . __LINE__ . "]");

    return;    # ---------->
} # fValidate

# -----------------------------
function fSaveElement($pXmlFile, $pName, $pOutFile) {
    # Copy one element out of an unpacked odt XML file, and write it to
    # pOutFile. The element is written exactly as it appears in the
    # source, so it can be spliced straight back in by bib-style-update.

    global $cgDebug;
    global $cgDocFile;

    echo "Start processing $pXmlFile [bib-style-save.php:" . __LINE__ . "]\n";

    $tDoc = uLoadXml($pXmlFile);
    $tNode = uFindElement($tDoc, $pName);

    if ($tNode === null)
        throw new Exception("\nError: A bibliography has not been added to $cgDocFile: no $pName in $pXmlFile. [bib-style-save.php:" . __LINE__ . "]");

    $tXml = $tDoc->saveXML($tNode);
    if ($tXml === false)
        throw new Exception("\nError: Could not read $pName from $pXmlFile [bib-style-save.php:" . __LINE__ . "]");

    if (file_put_contents($pOutFile, $tXml . "\n") === false)
        throw new Exception("\nError: Could not write $pOutFile [bib-style-save.php:" . __LINE__ . "]");

    echo "Saved $pName to $pOutFile [bib-style-save.php:" . __LINE__ . "]\n";

    return;    # ---------->
} # fSaveElement

# -----------------------------
function fProcessStyleFile() {
    global $cgDirEtc;
    global $cgDirTmp;

    # This assumes there is only one "bibliography-configuration" tag
    # in the styles.xml file.

    fSaveElement("$cgDirTmp/styles.xml", "bibliography-configuration",
        "$cgDirEtc/bib-style.xml");

    return;    # ---------->
} # fProcessStyleFile

# -----------------------------
function fProcessContentFile() {
    global $cgDirEtc;
    global $cgDirTmp;

    # This assumes there is only one "bibliography-source" tag in the
    # content.xml file.

    fSaveElement("$cgDirTmp/content.xml", "bibliography-source",
        "$cgDirEtc/bib-template.xml");

    return;    # ---------->
} # fProcessContentFile

# ========================================
# Includes, GetOps, Validate, ReadOnly

try {
    fGetOps();
    fValidate();
} catch(Exception $e) {
    echo "Problem with setup: " . $e->getMessage() . " [bib-style-save.php:" . __LINE__ . "]\n";
    exit(3);    # ---------->
}


# ========================================
# Write section
try {
    uUnpackFile($cgDocFile, "content styles");
    fProcessStyleFile();
    fProcessContentFile();
} catch(Exception $e) {
    echo "Problem: " . $e->getMessage() . " [bib-style-save.php:"
        . __LINE__ . "]\n";
    exit(4);    # ---------->
}


echo "Done. [bib-style-save.php:" . __LINE__ . "]\n";
exit(0);    # ---------->
?>
