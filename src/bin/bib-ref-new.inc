<?php

# ========================================
# bib-ref-new.inc - functions for bib-ref-new.php
#
# See bib-ref-new.php for the usage POD.
# ========================================

# -----------------------------
function fUsage() {
    global $argv;

    system("pod2text $argv[0]");
    exit(1);    # ---------->
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
    global $cgBin;
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

    return;    # ---------->
} # fGetOps

# -----------------------------
function fValidate() {
    global $cgDbTblBib;
    global $cgDirEtc;

    uValidateCommon();

    if ( ! uTableExists($cgDbTblBib))
        throw new Exception("\nError: Table $cgDbTblBib does not exist. [bib-ref-new.inc:" . __LINE__ . "]");

    if ( ! file_exists("$cgDirEtc/cite-new.xml"))
        throw new Exception("\nError: Missing file: $cgDirEtc/cite-new.xml [bib-ref-new.inc:" . __LINE__ . "]");

    return;    # ---------->
} # fValidate

# -----------------------------
function fXmlValue($pValue) {
    # Escape a DB value so it is safe in an XML attribute or in text.

    return htmlspecialchars($pValue, ENT_QUOTES | ENT_XML1, "UTF-8");    # ---------->
} # fXmlValue

# -----------------------------
function fUseTemplate($pCite) {
    global $cgDebug;
    global $cgDirEtc;

    # Partial template, see: etc/cite-new.xml
    #    <text:span text:style-name="Endnote_20_Symbol">{</text:span><text:span
    #      text:style-name="Endnote_20_Symbol"><text:bibliography-mark
    #      text:identifier="{BibId}"
    #      text:bibliography-type="{BibType}"
    #      {BibData}
    #      >{BibId}</text:bibliography-mark></text:span><text:span
    #      text:style-name="Endnote_20_Symbol">{BibLoc}}</text:span>

    # trim: the file ends with a newline, which would become document text.
    $tTemplate = trim(file_get_contents("$cgDirEtc/cite-new.xml"));

    # One str_replace pass, so a DB value that happens to contain a
    # "{Bib...}" name is not substituted again.
    $tXml = str_replace(
        array("{BibId}", "{BibType}", "{BibData}", "{BibLoc}"),
        array(
            fXmlValue($pCite['id']),
            fXmlValue($pCite['type']),
            $pCite['data'],
            fXmlValue($pCite['loc'])
        ),
        $tTemplate);

    if ($cgDebug) echo "replace: {" . $pCite['id'] . $pCite['loc'] . "}\n";

    return $tXml;    # ---------->
} # fUseTemplate

# -----------------------------
function fBibLookup($pId) {
    global $cgDbTblBib;
    global $cgDebug;
    global $gBibCache;
    global $gDb;

    # Ids used as examples in the documentation, not real citations.
    $cSkipId = array("REF", "example-01", "example-02", "example-youtube-95");

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

    if (in_array($pId, $cSkipId))
        return false;    # ---------->

    $tSql = "select * from $cgDbTblBib where Identifier = ?";
    $tRecH = $gDb->prepare($tSql);
    $tRecH->execute(array($pId));
    $tRow = $tRecH->fetch(PDO::FETCH_ASSOC);

    if ( ! $tRow) {
        echo "\nWarning: $pId is not in DB. [bib-ref-new.inc:" . __LINE__ . "]\n";
        return false;    # ---------->
    }

    $tCite = array(
        'id'=>$pId,
        'type'=>uBibType2Xml($tRow['Type']),
        'data'=>"",
        'loc'=>""
    );

    foreach ($tRow as $tCol => $tValue) {
        if ("$tValue" == "")
            continue;
        if (in_array($tCol, $cSkipCol))
            continue;
        $tCite['data'] .=
            " text:" . uBib2Xml($tCol) . '="' . fXmlValue($tValue) . '"';
    }

    if ($cgDebug) print_r($tCite);

    $gBibCache[$pId] = $tCite;
    return $tCite;    # ---------->
} # fBibLookup

# -----------------------------
function fProcessCite($pNode) {
    # Replace every "bare" cite in one text node with the filled out
    # cite-new.xml template.
    #
    # A cite is {Id} or {Id:Loc}. A text node never contains a '<', so a
    # cite that has been split across elements is not matched here.

    global $cgDebug;
    global $gNumCite;

    $cPatSplit = '/(\{[^{}:]+(?::[^{}]*)?\})/';
    $cPatCite = '/^\{([^{}:]+)(:[^{}]*)?\}$/';

    if ( ! preg_match($cPatSplit, $pNode->nodeValue))
        return;    # ---------->

    if ($cgDebug) echo "Processing: " . $pNode->nodeValue . "\n";

    $tDoc = $pNode->ownerDocument;
    $tFrag = $tDoc->createDocumentFragment();
    $tFound = false;

    $tPart = preg_split($cPatSplit, $pNode->nodeValue, -1,
        PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

    foreach ($tPart as $tText) {
        # Not a cite, or not in the DB: keep the text as-is.
        if ( ! preg_match($cPatCite, $tText, $tMatch)) {
            $tFrag->appendChild($tDoc->createTextNode($tText));
            continue;
        }

        $tCite = fBibLookup($tMatch[1]);
        if ( ! $tCite) {
            $tFrag->appendChild($tDoc->createTextNode($tText));
            continue;
        }
        $tCite['loc'] = isset($tMatch[2]) ? $tMatch[2] : "";

        $tSub = $tDoc->createDocumentFragment();
        if ( ! @$tSub->appendXML(fUseTemplate($tCite))) {
            echo "\nWarning: Could not parse the XML built for " . $tMatch[1] .
                ". [bib-ref-new.inc:" . __LINE__ . "]\n";
            $tFrag->appendChild($tDoc->createTextNode($tText));
            continue;
        }

        $tFrag->appendChild($tSub);
        $tFound = true;
        ++$gNumCite;
        if ($gNumCite % 10 == 0)
            echo '.';
    }

    if ( ! $tFound)
        return;    # ---------->

    # If the cite is all of a <text:span>, replace the span, so that its
    # character style is dropped. The template brings its own spans.
    $tOld = $pNode;
    if ($pNode->parentNode->nodeName == "text:span"
        and $pNode->parentNode->childNodes->length == 1
        and preg_match($cPatCite, $pNode->nodeValue))
        $tOld = $pNode->parentNode;

    $tOld->parentNode->replaceChild($tFrag, $tOld);

    return;    # ---------->
} # fProcessCite

# -----------------------------
function fProcessFile() {
    global $cgDebug;
    global $cgDirTmp;
    global $gNumCite;

    echo "Start processing [bib-ref-new.inc:" . __LINE__ . "]\n";

    $tDoc = uLoadXml("$cgDirTmp/content.xml");

    # Collect the text nodes first: fProcessCite() replaces nodes, which
    # is not safe while walking the DOMNodeList.
    $tXpath = new DOMXPath($tDoc);
    $tNodeList = array();
    foreach ($tXpath->query("//text()") as $tNode)
        $tNodeList[] = $tNode;

    $gNumCite = 0;
    foreach ($tNodeList as $tNode)
        fProcessCite($tNode);

    echo "\nScanned " . count($tNodeList) . " text nodes. [bib-ref-new.inc:" . __LINE__ . "]\n";
    echo "Replaced $gNumCite citations. [bib-ref-new.inc:" . __LINE__ . "]\n";

    uSaveXml($tDoc, "$cgDirTmp/content.new.xml");

    return;    # ---------->
} # fProcessFile

?>
