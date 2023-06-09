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

bib-ref-new.php - insert new bib refs into Libreoffice odt document

=head1 SYNOPSIS

 ./bib-ref-new.php [-h]

=head1 DESCRIPTION

bib-ref-new.php will look for {REF} tags in cgDocFile, and replace
them with bibliography-mark tags. The cgDbib Table will be used to
look up the values for the bibliography-marks.

If a {REF} tag is not found in the DB, then it will be left in the
file and a warning will be output.

=head1 OPTIONS

See also ENVIRONMENT section.

=over 4

=item B<-h> - help

This help.

=back

=for comment =head1 RETURN VALUE

=for comment =head1 ERRORS

=head1 ENVIRONMENT

cgDirApp is required

Set these in conf.env

    cgDocFile              # Your doc file, to whole reason for this app
    cgDbBib                # Partially formatted cgDbLo table

=for comment =head1 FILES

=for comment =head1 SEE ALSO

=for comment =head1 NOTES

=for comment =head1 CAVEATS

=for comment =head1 DIAGNOSTICS

=for comment =head1 BUGS

=for comment =head1 RESTRICTIONS

=for comment =head1 AUTHOR

=head1 HISTORY

 $Revision: 1.3 $ $Date: 2023/05/29 02:54:22 $ GMT

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

    uValidateCommon();

    if ( ! uTableExists($cgDbBib))
        throw new Exception("Error: -t Table $gpFromTable does not exist. [bib-ref-new.php:" . __LINE__ . "]");

    if ( ! file_exists("$cgDirEtc/cite-new.xml"))
        throw new Exception("Error: Missing file: $cgDirEtc/cite-new.xml [bib-ref-new.php:" . __LINE__ . "]");

    return;    # ---------->
} # fValidate

# -----------------------------
function fUseTemplate($pRef) {
    global $cgDebug;
    global $cgDirApp;
    global $cgDirEtc;

    $tTemplate = file_get_contents("$cgDirEtc/cite-new.xml");
    #    '
    #      <text:span text:style-name="Endnote_20_Symbol">{</text:span><text:span text:style-name="Endnote_20_Symbol"><text:bibliography-mark text:identifier="{BibId}"
    #      text:bibliography-type="{BibType}"
    #      {BibData}
    #      >{BibId}</text:bibliography-mark></text:span><text:span text:style-name="Endnote_20_Symbol">{BibLoc}}</text:span>
    #    ';

    $tTemplate = preg_replace("/{BibId}/",   $pRef['id'],   $tTemplate);
    $tTemplate = preg_replace("/{BibType}/", $pRef['type'], $tTemplate);
    $tTemplate = preg_replace("/{BibLoc}/",  $pRef['loc'],  $tTemplate);
    $tTemplate = preg_replace("/{BibData}/", $pRef['data'], $tTemplate);

    if ($cgDebug) echo "replace: " . $pRef['full'] . "\n";

    return $tTemplate;    # ---------->
} # fUseTemplate

# -----------------------------
function fBibLookup($pRefList) {
    global $gDb;
    global $cgDbBib;
    global $cgDebug;

    # Add 'type', 'data' from DB col or 'id'

    $cBib2Xml = uBib2Xml();

    foreach (array_keys($pRefList) as $tRef) {
        $tSql = "select * from $cgDbBib where Identifier = '" . $pRefList[$tRef]['id'] . "'";
        $tRecH = $gDb->prepare($tSql);
        $tRecH->execute();
        $tRow = $tRecH->fetch(PDO::FETCH_ASSOC);

        if ( ! $tRow) {
            # TBD, make these a "skip" option
            if ( ! array_key_exists($pRefList[$tRef]['id'],
                    array("example-01", "example-02", "example-youtube-95")))
                echo "\nWarning: " . $pRefList[$tRef]['id'] .
                    " is not in DB. [bib-ref-new.php:" . __LINE__ . "]\n";
            unset($pRefList[$tRef]);
            continue;
        }

        $pRefList[$tRef]['type'] = uBibType2Xml($tRow['Type']);
        $pRefList[$tRef]['data'] = "";

        foreach (array_keys($tRow) as $tCol) {
            if ($tRow[$tCol] == "")
                continue;
            # TBD, Make some of these a "no-include" option
            switch ($tCol) {
            case "Identifier":
            case "Type":
            case "Annote":
            case "Note":
            case "Custom1":
            case "Custom2":
            case "Custom3":
            case "Custom5":
                continue 2;
            }
            $pRefList[$tRef]['data'] .=
                " text:" . $cBib2Xml[$tCol] . '="' . $tRow[$tCol] . '"';
        }
    }

    return $pRefList;    # ---------->
} # fBibLookup

# -----------------------------
function fGetRefList($pFound1, $pFound2) {
    global $cgDebug;

    foreach (array_keys($pFound1[0]) as $tKey) {
        $tF = $pFound1[0][$tKey];
        $tFN = preg_replace("/[{}]/", "", $tF);

        $tRet[$tKey]['full'] = $tF;
        $tRet[$tKey]['id'] = $tFN;
        $tRet[$tKey]['loc'] = "";
        if ($cgDebug) echo "Found: $tF, $tFN\n";
    }

    $tN = count($pFound1);

    foreach (array_keys($pFound2[0]) as $tKey) {
        $tF = $pFound2[0][$tKey];
        $tFN = preg_replace("/[{}]/", "", $tF);
        $tFN = preg_replace("/:.*/", "", $tFN);
        $tFL = preg_replace("/.*:/", "", $tF);
        $tFL = preg_replace("/}/", "", $tFL);

        $tRet[$tKey + $tN]['full'] = $tF;
        $tRet[$tKey + $tN]['id'] = $tFN;
        $tRet[$tKey + $tN]['loc'] = ':' . $tFL;
        if ($cgDebug) echo "Found: $tF, $tFN, :$tFL\n";
    }

    return $tRet;    # ---------->
} # fGetRefList() {

# -----------------------------
function fProcessLine($pLine) {
    global $gNumRef;
    global $gOutH;
    global $cgDebug;

    # Note '<' is in the don't match pattern, because there shouold be
    # no tags in the middle of the {} references. This might not work out.

    # {foobar-02}
    $cPatShort = "/{[a-z][^-}<]*-\d*}/";

    # {foobar-02:p39-p42}
    $cPatLong = "/{[a-z][^-}<]*-\d*:[^}<]*}/";

    $tFound1 = preg_match_all($cPatShort, $pLine, $tFoundList1);
    $tFound2 = preg_match_all($cPatLong, $pLine, $tFoundList2);

    if ( ! $tFound1 and ! $tFound2) {
        fputs($gOutH, $pLine);
        return;    # ---------->
    }

    if ($cgDebug) echo "Processing: $pLine\n";
    ++$gNumRef;
    if ($gNumRef % 10 == 0)
        echo '.';

    # Add 'full', 'id', 'loc'
    $tRefList = fGetRefList($tFoundList1, $tFoundList2);

    # Add 'type', 'data'
    $tRefList = fBibLookup($tRefList);
    if ($cgDebug) print_r($tRefList);

    foreach (array_keys($tRefList) as $tRef) {
        $tReplace = fUseTemplate($tRefList[$tRef]);
        $tMatch = "/" . $tRefList[$tRef]['full'] . "/";
        $pLine = preg_replace($tMatch, $tReplace, $pLine);
    }

    if ($cgDebug) echo "Updated line: $pLine\n";
    ##if ($cgDebug and $gNumRef == 4) exit(222);    # ---------->

    fputs($gOutH, $pLine);

    return;    # ---------->
} #fProcessLine

# -----------------------------
function fProcessFile() {
    global $gInH;
    global $gOutH;
    global $gNumRef;
    global $cgDebug;
    global $cgDirTmp;

    echo "Start processing [bib-ref-new.php:" . __LINE__ . "]\n";

    $gInH = fopen("$cgDirTmp/content.xml", 'r');
    $gOutH = fopen("$cgDirTmp/content.new.xml", 'w');

    $gNumRef = 0;
    $tNumLine = 0;
    while ($tLine = fgets($gInH)) {
        ++$tNumLine;
        fProcessLine($tLine);
    }
    echo "\nProcessed $tNumLine lines. [bib-ref-new.php:" . __LINE__ . "]\n";
    echo "Found $gNumRef references. [bib-ref-new.php:" . __LINE__ . "]\n";

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
    echo "Problem with setup: " . $e->getMessage() . " [bib-ref-new.php:" . __LINE__ . "]\n";
    exit(3);    # ---------->
}

# ========================================
# Write section
try {
    uUnpackFile($cgDocFile, "content");
    fProcessFile();
    uPackFile($cgDocFile, "content");
} catch(Exception $e) {
    echo "Problem creating table: " . $e->getMessage() . " [bib-ref-new.php:"
        . __LINE__ . "]\n";
    exit(4);    # ---------->
}

echo "Done. [bib-ref-new.php:" . __LINE__ . "]\n";
exit(0);    # ---------->
?>
