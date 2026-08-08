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
    global $cgDbTblBib;
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
function fReplaceElement($pXmlFile, $pName, $pNewFile, $pOutFile) {
    # Replace one element in an unpacked odt XML file with the copy
    # saved by bib-style-save.php, and write the result to pOutFile.

    global $cgDebug;
    global $cgDocFile;

    echo "Start processing $pXmlFile [bib-style-update.php:" . __LINE__ . "]\n";

    $tDoc = uLoadXml($pXmlFile);
    $tNode = uFindElement($tDoc, $pName);

    if ($tNode === null)
        throw new Exception("\nError: A bibliography has not been added to $cgDocFile: no $pName in $pXmlFile. [bib-style-update.php:" . __LINE__ . "]");

    $tXml = trim(file_get_contents($pNewFile));

    # The saved copy carries no namespace declarations of its own: it is
    # parsed against this document, which declares them on its root.
    $tFrag = $tDoc->createDocumentFragment();
    if ( ! @$tFrag->appendXML($tXml))
        throw new Exception("\nError: Could not parse $pNewFile [bib-style-update.php:" . __LINE__ . "]");

    $tNode->parentNode->replaceChild($tFrag, $tNode);

    uSaveXml($tDoc, $pOutFile);
    echo "Updated $pName from $pNewFile [bib-style-update.php:" . __LINE__ . "]\n";

    return;    # ---------->
} # fReplaceElement

# -----------------------------
function fProcessStyleFile() {
    global $cgDirEtc;
    global $cgDirTmp;

    # This assumes there is only one "bibliography-configuration" tag
    # in the styles.xml file.

    fReplaceElement("$cgDirTmp/styles.xml", "bibliography-configuration",
        "$cgDirEtc/bib-style.xml", "$cgDirTmp/styles.new.xml");

    return;    # ---------->
} # fProcessStyleFile

# -----------------------------
function fProcessContentFile() {
    global $cgDirEtc;
    global $cgDirTmp;

    # This assumes there is only one "bibliography-source" tag in the
    # content.xml file.

    fReplaceElement("$cgDirTmp/content.xml", "bibliography-source",
        "$cgDirEtc/bib-template.xml", "$cgDirTmp/content.new.xml");

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
