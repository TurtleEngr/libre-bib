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
the cgDbBib table. There is no comparison to see if things are
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
    cgDbBib                # Partially formatted cgDbLo table

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
    global $cgDbBib;
    global $cgDirApp;
    global $cgDirEtc;

    uValidateCommon();

    if ( ! uTableExists($cgDbBib))
        throw new Exception("\nError: -t Table $gpFromTable does not exist. [bib-ref-update.php:" . __LINE__ . "]");

    if ( ! file_exists("$cgDirEtc/cite-update.xml"))
        throw new Exception("\nError: Missing file: $cgDirEtc/cite-update.xml [bib-ref-update.php:" . __LINE__ . "]");

    return;    # ---------->
} # fValidate

# -----------------------------
function fUseTemplate($pRef) {
    global $cgDebug;
    global $cgDirApp;
    global $cgDirEtc;

    $tTemplate = file_get_contents("$cgDirEtc/cite-update.xml");
    #    '
    #      text:bibliography-type="{BibType}"
    #      {BibData}>
    #    ';

    $tTemplate = preg_replace("/{BibType}/", $pRef['type'], $tTemplate);
    $tTemplate = preg_replace("/{BibData}/", $pRef['data'], $tTemplate);

    return $tTemplate;    # ---------->
} # fUseTemplate

# -----------------------------
function fBibLookup($pRef) {
    global $gDb;
    global $cgDbBib;
    global $cgDebug;

    $cBib2Xml = uBib2Xml();

    $pRef['tag'] = "";
    $pRef['data'] = "";

    $tSql = "select * from $cgDbBib where Identifier = '" . $pRef['id'] . "'";
    $tRecH = $gDb->prepare($tSql);
    $tRecH->execute();
    $tRow = $tRecH->fetch(PDO::FETCH_ASSOC);

    if ( ! $tRow) {
        # TBD, make these a "skip" option?
        if ( ! array_key_exists($pRef['id'],
                array("REF", "example-01", "example-02", "example-youtube-95")))
            echo "\nWarning: " . $pRef['id'] . " is not in DB. [bib-ref-update.php:" .
                __LINE__ . "]\n";
        return;    # ---------->
    }

    $pRef['type'] = uBibType2Xml($tRow['Type']);
    $pRef['data'] = "";

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
        $pRef['data'] .=
            " text:" . $cBib2Xml[$tCol] . '="' . $tRow[$tCol] . '"';
    }

    return $pRef;    # ---------->
} # fBibLookup

# -----------------------------
function fGetRefId($pLine) {
    global $cgDebug;

    $tRef = array();
    $tRef['id'] = "";
    $tRef['type'] = "";
    $tRef['data'] = "";

    # Pick apart pLine to get at the Id. Just return if no match/replace.
    # <text:bibliography-mark text:identifier="{BibId}"

    $tId = preg_replace('/.*\:identifier="/', '', $pLine, -1, $tCount);
    if ($tCount != 1)
        return $tRef;    # ---------->

    $tId = preg_replace('/"/', '', $tId, -1, $tCount);
    if ($tCount != 1)
        return $tRef;    # ---------->

    $tRef['id'] = trim($tId);

    return $tRef;    # ---------->
} # fGetRefId

# -----------------------------
function fProcessLine($pLine) {
    global $gNumRef;
    global $gInH;
    global $gOutH;
    global $cgDebug;

    # <text:bibliography-mark text:identifier="{BibId}"
    $cPatStart = "/<text:bibliography-mark text:identifier=/";

    # {BibId}</text:bibliography-mark>
    $cPatEnd = "/<\/text:bibliography-mark>/";

    $cMaxCache = 28;

    $tFoundStart = preg_match($cPatStart, $pLine);

    # Always output pLine, whether found or not
    fputs($gOutH, $pLine);

    if ( ! $tFoundStart)
        return $pLine;    # ---------->

    if ($cgDebug) echo "Processing: $pLine\n";

    # Parse pLine to get Id. If not found, just continue.
    $tRef = fGetRefId($pLine);
    if ($tRef['id'] == "") {
        echo "Warning: No Id found for biblio entry. [bib-ref-update.php:" . __LINE__ . "]\n";
        return;    # ---------->
    }

    # See if Id is bib DB.
    # If found, set the tRef assoc values "type", "data"
    # If not found, set tRef['type'] = ""
    $tRef = fBibLookup($tRef);

    # If not found, in/out loop will continue to output the entry, unchanged.
    if ($tRef['type'] == "")
        return;    # ---------->

    ++$gNumRef;
    if ($gNumRef % 10 == 0)
        echo '.';
    ##if ($cgDebug and $gNumRef == 4) exit(222);    # ---------->

    # Read lines until bibliography-mark end tag is found.
    # Cache the lines, for an error state, if end tag is not found.
    $tFoundEnd = 0;
    $tCacheIn = array();
    $tCacheSize = 0;
    while ( ! $tFoundEnd and $tCacheSize < $cMaxCache) {
        $tLine = fgets($gInH);
        $tCacheSize = array_push($tCacheIn, $tLine);
        $tFoundEnd = preg_match($cPatEnd, $tLine);
    }
    if ($tCacheSize >= $cMaxCache) {
        echo "\nWarning: Problem parsing " . $tRef['id'] .
            "bibliography-mark end tag was not found. [bib-ref-update.php:" . __LINE__ . "]\n";
        # Output the saved lines. (reversed, is faster and loop is easier)
        $tCacheOut = array_reverse($tCacheIn);
        while ($tLine = array_pop($tCacheOut))
            fputs($gOutH, $tLine);
        return;    # ---------->
    }

    $tLineNew = fUseTemplate($tRef);
    if ($cgDebug) echo "Updated line: $tLineNew\n";

    fputs($gOutH, $tLineNew);
    fputs($gOutH, $tLine);
    return;    # ---------->
} #fProcessLine

# -----------------------------
function fProcessFile() {
    global $gInH;
    global $gOutH;
    global $gNumRef;
    global $cgDebug;
    global $cgDirTmp;

    echo "Start processing [bib-ref-update.php:" . __LINE__ . "]\n";

    $gInH = fopen("$cgDirTmp/content.xml", 'r');
    $gOutH = fopen("$cgDirTmp/content.new.xml", 'w');

    $gNumRef = 0;
    $tNumLine = 0;
    while ($tLine = fgets($gInH)) {
        ++$tNumLine;
        fProcessLine($tLine);
    }
    echo "\nProcessed $tNumLine lines. [bib-ref-update.php:" . __LINE__ . "]\n";
    echo "Found $gNumRef references. [bib-ref-update.php:" . __LINE__ . "]\n";

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
