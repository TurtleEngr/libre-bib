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

 $Revision: 1.1 $ $Date: 2023/05/17 01:13:24 $ GMT

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

    fValidateCommon();

    if ( ! fTableExists($cgDbBib))
        throw new Exception("Error: -t Table $gpFromTable does not exist. [" . __LINE__ . "]");

    if ( ! file_exists("$cgDirApp/etc/cite-new.xml"))
        throw new Exception("Error: Missing file: $cgDirApp/etc/cite-new.xml [" . __LINE__ . "]");

    return;    # ---------->
} # fValidate

# -----------------------------
function fUseTemplate($pRef) {
    global $cgDebug;
    global $cgDirApp;

    $tTemplate = file_get_contents("$cgDirApp/etc/cite-new.xml");
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

    $cBib2Xml = fBib2Xml();

    foreach (array_keys($pRefList) as $tRef) {
        $tSql = "select * from $cgDbBib where Identifier = '" . $pRefList[$tRef]['id'] . "'";
        $tRecH = $gDb->prepare($tSql);
        $tRecH->execute();
        $tRow = $tRecH->fetch(PDO::FETCH_ASSOC);

        if ( ! $tRow) {
            # TBD, make these a "skip" option
            if ( ! in_array($pRefList[$tRef]['id'],
                    array("example-01", "example-02", "example-youtube-95")))
                echo "\nWarning: " . $pRefList[$tRef]['id'] .
                    " is not in DB. [" . __LINE__ . "]\n";
            unset($pRefList[$tRef]);
            continue;
        }

        $pRefList[$tRef]['type'] = fBibType2Xml($tRow['Type']);
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

    echo "Start processing [" . __LINE__ . "]\n";

    $gInH = fopen('content.xml', 'r');
    $gOutH = fopen('content-new.xml', 'w');

    $gNumRef = 0;
    $tNumLine = 0;
    while ($tLine = fgets($gInH)) {
        ++$tNumLine;
        fProcessLine($tLine);
    }
    echo "\nProcessed $tNumLine lines. [" . __LINE__ . "]\n";
    echo "Found $gNumRef references. [" . __LINE__ . "]\n";

    fclose($gInH);
    fclose($gOutH);

    return;    # ---------->
} # fProcessFile

# -----------------------------
function fUnpackFile() {
    global $cgDocFile;
    global $cgDebug;

    $cTidyOpt = "-m -q --tidy-mark no --break-before-br yes --indent-attributes yes --indent-spaces 2 --indent auto --input-xml yes --output-xml yes --vertical-space no --wrap 78 -xml";

    echo "Unpack $cgDocFile [" . __LINE__ . "]\n";
    shell_exec("/bin/bash -c 'unzip -o $cgDocFile content.xml'");
    if ( ! file_exists("content.xml"))
        throw new Exception("Error: Could not extract content.xml [" . __LINE__ . "]");

    # tidy content.xml
    shell_exec("/bin/bash -c 'tidy $cTidyOpt content.xml &>/dev/null'");

    return;    # ---------->
} # fUnpackFile

# -----------------------------
function fPackFile() {
    global $cgDocFile;
    global $cgDebug;
    global $cgNoExec;

    $cTidyOpt = "-m -q --tidy-mark no --break-before-br no --indent-attributes no --indent no --input-xml yes --output-xml yes --vertical-space no --wrap 0 -xml";

    if ( ! $cgNoExec) {
        echo "Backup $cgDocFile [" . __LINE__ . "]\n";
        shell_exec("/bin/bash -c 'cp --backup=t $cgDocFile $cgDocFile.bak'");
    }

    echo "Final clean-up with tidy [" . __LINE__ . "]\n";
    shell_exec("/bin/bash -c 'tidy $cTidyOpt content-new.xml'");

    # Remove newlines between tags, to remove any spaces in the text
    shell_exec("/bin/bash -c \"tr -d '\n' <content-new.xml >content.xml\"");

    if ($cgNoExec) {
        echo "Nothing done to $cgDocFile [" . __LINE__ . "]\n";
        echo "content-new.xml can be inspected for the changes. ["
            . __LINE__ . "]\n";
    } else {
        echo "Repack $cgDocFile [" . __LINE__ . "]\n";
        shell_exec("/bin/bash -c 'zip $cgDocFile content.xml'");
        unlink("content.xml");
        unlink("content-new.xml");
    }

    return;    # ---------->
} # fPackFile

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
    fUnpackFile();
    fProcessFile();
    fPackFile();
} catch(Exception $e) {
    echo "Problem creating table: " . $e->getMessage() . " ["
        . __LINE__ . "]\n";
    exit(4);    # ---------->
}

echo "Done. [" . __LINE__ . "]\n";
exit(0);    # ---------->
?>
