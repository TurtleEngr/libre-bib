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

bib-ref-update.php - update bib refs into Libreoffice odt document

=head1 SYNOPSIS

 ./bib-ref-update.php [-h]

=head1 DESCRIPTION

First run bib-ref-new.php.

The existing biblio references will be replace with new entries from
the cgDbTblBib table. There is no comparison to see if things are
different.  Everything is just replaced, even things are the same.

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

    cgDocFile              # Your doc file, to whole reason for this app
    cgDbTblBib                # Partially formatted cgDbTblLo table

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
    global $gpHelp;

    $gpHelp = false;
    $tOpt = getopt("ch");
    $gpHelp = isset($tOpt['h']);
    if ($gpHelp or $argc < 2)
        fUsage();

    $tConf = $_ENV['cgDirApp'] . "/etc/conf.php";
    require_once "$tConf";
    require_once "$cgBin/util.php";
    uFixBool();

    return; # ---------->
} # fGetOps

# -----------------------------
function fValidate() {
    global $cgBin;
    global $cgDocFile;
    global $cgDbTblBib;
    global $cgDirApp;
    global $cgDirEtc;

    uValidateCommon();

    if ( ! uTableExists($cgDbTblBib))
        throw new Exception("\nError: Table $cgDbTblBib does not exist. [bib-ref-update.php:" . __LINE__ . "]");

    return;    # ---------->
} # fValidate

# -----------------------------
function fBibLookup($pId) {
    global $cgDbTblBib;
    global $cgDebug;
    global $gBibCache;
    global $gDb;

    # Bib cols that are not put in the bibliography-mark.
    $cSkipCol = array("Identifier", "Type", "Annote", "Note",
        "Custom1", "Custom2", "Custom3", "Custom5");

    if ( ! isset($gBibCache))
        $gBibCache = array();

    # One lookup per Id, however many times the Id is cited. This also
    # keeps the "not in DB" warning down to one per Id.
    if (isset($gBibCache[$pId]))
        return $gBibCache[$pId];    # ---------->

    $gBibCache[$pId] = false;

    $tSql = "select * from $cgDbTblBib where Identifier = ?";
    $tRecH = $gDb->prepare($tSql);
    $tRecH->execute(array($pId));
    $tRow = $tRecH->fetch(PDO::FETCH_ASSOC);

    if ( ! $tRow) {
        echo "\nWarning: $pId is not in DB. [bib-ref-update.php:" . __LINE__ . "]\n";
        return false;    # ---------->
    }

    # 'data' is an XML-attribute-name => value map, so the DOM can set
    # the attributes itself and do its own escaping.
    $tRef = array(
        'id'=>$pId,
        'type'=>uBibType2Xml($tRow['Type']),
        'data'=>array()
    );

    foreach ($tRow as $tCol => $tValue) {
        if ("$tValue" == "")
            continue;
        if (in_array($tCol, $cSkipCol))
            continue;
        $tRef['data'][uBib2Xml($tCol)] = $tValue;
    }

    if ($cgDebug) print_r($tRef);

    $gBibCache[$pId] = $tRef;
    return $tRef;    # ---------->
} # fBibLookup

# -----------------------------
function fUpdateMark($pMark) {
    global $cgDebug;
    global $gNumRef;

    # Refresh one <text:bibliography-mark> from the bib table. The
    # identifier is what ties the mark to the DB, so it is the one
    # attribute that is kept; everything else is rewritten.

    $tId = $pMark->getAttribute("text:identifier");
    if ($tId == "") {
        echo "\nWarning: No Id found for biblio entry. [bib-ref-update.php:" . __LINE__ . "]\n";
        return;    # ---------->
    }

    $tRef = fBibLookup($tId);
    if ( ! $tRef)
        return;    # ---------->

    if ($cgDebug) echo "Updating: $tId\n";

    # Collect the names first: removing while walking ->attributes skips
    # entries, because the map is live.
    $tOldName = array();
    foreach ($pMark->attributes as $tAttr)
        $tOldName[] = $tAttr->nodeName;

    foreach ($tOldName as $tName) {
        if ($tName != "text:identifier")
            $pMark->removeAttribute($tName);
    }

    $pMark->setAttribute("text:bibliography-type", $tRef['type']);
    foreach ($tRef['data'] as $tName => $tValue)
        $pMark->setAttribute("text:" . $tName, $tValue);

    ++$gNumRef;
    if ($gNumRef % 10 == 0)
        echo '.';

    return;    # ---------->
} # fUpdateMark

# -----------------------------
function fProcessFile() {
    global $cgDebug;
    global $cgDirTmp;
    global $gNumRef;

    echo "Start processing [bib-ref-update.php:" . __LINE__ . "]\n";

    $tDoc = uLoadXml("$cgDirTmp/content.xml");
    $tMarkList = uFindElementList($tDoc, "bibliography-mark");

    $gNumRef = 0;
    foreach ($tMarkList as $tMark)
        fUpdateMark($tMark);

    echo "\nFound " . count($tMarkList) . " bibliography marks. [bib-ref-update.php:" . __LINE__ . "]\n";
    echo "Updated $gNumRef references. [bib-ref-update.php:" . __LINE__ . "]\n";

    uSaveXml($tDoc, "$cgDirTmp/content.new.xml");

    return;    # ---------->
} # fProcessFile

# ========================================
# Includes, GetOps, Validate, ReadOnly

try {
    fGetOps();
    fValidate();
} catch(Exception $e) {
    echo "Problem with setup: " . $e->getMessage() . "\n";
    exit(3);    # ---------->
}

# ========================================
# Write section
try {
    uUnpackFile($cgDocFile, "content");
    fProcessFile();
    uPackFile($cgDocFile, "content");
} catch(Exception $e) {
    echo "Problem creating table: " . $e->getMessage() . "\n";
    exit(4);    # ---------->
}

echo "Done. [bib-ref-update.php:" . __LINE__ . "]\n";
exit(0);    # ---------->
?>
