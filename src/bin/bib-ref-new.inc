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
function fFindCiteList($pText) {
    # Find every "{Id}" or "{Id:Loc}" cite in pText. Returns an array of
    # array('id'=>.., 'loc'=>.., 'start'=>.., 'end'=>..), where start/end
    # are byte offsets into pText (end is exclusive). 'loc' includes its
    # leading ':', or is "" if the cite has no location.

    $cPatCite = '/\{([^{}:]+)(:[^{}]*)?\}/';

    $tCiteList = array();
    if ( ! preg_match_all($cPatCite, $pText, $tMatch, PREG_OFFSET_CAPTURE))
        return $tCiteList;    # ---------->

    foreach ($tMatch[0] as $tI => $tWhole) {
        $tCiteList[] = array(
            'id'=>$tMatch[1][$tI][0],
            'loc'=>$tMatch[2][$tI][0],
            'start'=>$tWhole[1],
            'end'=>$tWhole[1] + strlen($tWhole[0])
        );
    }

    return $tCiteList;    # ---------->
} # fFindCiteList

# -----------------------------
function fCollectTextNodeList($pBlock) {
    # Return the text() descendants of pBlock (a paragraph or heading),
    # in document order, as a plain array.

    $tXpath = new DOMXPath($pBlock->ownerDocument);
    $tNodeList = array();
    foreach ($tXpath->query(".//text()", $pBlock) as $tNode)
        $tNodeList[] = $tNode;

    return $tNodeList;    # ---------->
} # fCollectTextNodeList

# -----------------------------
function fOuterNode($pNode) {
    # A cite's characters live in a text node, but if that text node is
    # the *only* child of a <text:span>, the span carries the character
    # style for that text. Return the span in that case (removing or
    # keeping the text means removing or keeping the span), else return
    # the text node itself.

    $tParent = $pNode->parentNode;
    if ($tParent->nodeName == "text:span" and $tParent->childNodes->length == 1)
        return $tParent;    # ---------->

    return $pNode;    # ---------->
} # fOuterNode

# -----------------------------
function fReplaceCite($pDoc, $pNodeList, $pOffset, $pCite, $pStart, $pEnd) {
    # Replace the cite text at [pStart, pEnd) -- offsets into the
    # concatenated text of pNodeList, per pOffset -- with the filled out
    # cite-new.xml template. The cite may be split across several text
    # nodes/spans by character-style runs.
    #
    # Returns true if replaced. Returns false, with a warning, if the
    # surrounding XML is too irregular to edit safely; the cite is left
    # untouched in that case.

    global $cgDebug;

    $tNumNode = count($pNodeList);
    $tFirst = -1;
    $tLast = -1;
    for ($tI = 0; $tI < $tNumNode; ++$tI) {
        if ($pOffset[$tI][0] >= $pEnd)
            break;
        if ($pOffset[$tI][1] <= $pStart)
            continue;
        if ($tFirst < 0)
            $tFirst = $tI;
        $tLast = $tI;
    }
    if ($tFirst < 0)
        return false;    # ---------->  (should not happen)

    $tNodeStart = $pNodeList[$tFirst];
    $tNodeEnd = $pNodeList[$tLast];

    $tPrefix = substr($tNodeStart->nodeValue, 0, $pStart - $pOffset[$tFirst][0]);
    $tSuffix = substr($tNodeEnd->nodeValue, $pEnd - $pOffset[$tLast][0]);

    if ($tFirst === $tLast) {
        # The whole cite, and any text kept around it, is in one node.
        # This node's parent (bare text:p, or a styled text:span) is
        # not touched except to shorten its text, so no promotion to
        # an "outer" node is needed here -- unlike the multi-node case
        # below, there's no risk of relocating content to the wrong
        # nesting level.
        $tParent = $tNodeStart->parentNode;
    } else {
        $tOuterStart = fOuterNode($tNodeStart);
        $tOuterEnd = fOuterNode($tNodeEnd);

        if ($tOuterStart->parentNode === null
            or $tOuterStart->parentNode !== $tOuterEnd->parentNode) {
            echo "\nWarning: " . $pCite['id'] .
                " cite spans a nested XML structure that is too irregular to edit; left as-is. [bib-ref-new.inc:" . __LINE__ . "]\n";
            return false;    # ---------->
        }
        $tParent = $tOuterStart->parentNode;

        # A styled run (its own <text:span>) at the edge of a
        # multi-node cite is only ever wholly inside or wholly outside
        # the cite in practice; refuse to split one, rather than guess
        # how to preserve its style.
        if (($tPrefix !== "" and $tOuterStart !== $tNodeStart)
            or ($tSuffix !== "" and $tOuterEnd !== $tNodeEnd)) {
            echo "\nWarning: " . $pCite['id'] .
                " cite starts or ends inside a styled span; left as-is. [bib-ref-new.inc:" . __LINE__ . "]\n";
            return false;    # ---------->
        }
    }

    if ($cgDebug) echo "replace: {" . $pCite['id'] . $pCite['loc'] . "}\n";

    $tFrag = $pDoc->createDocumentFragment();
    if ( ! @$tFrag->appendXML(fUseTemplate($pCite))) {
        echo "\nWarning: Could not parse the XML built for " . $pCite['id'] .
            ". [bib-ref-new.inc:" . __LINE__ . "]\n";
        return false;    # ---------->
    }

    if ($tFirst === $tLast) {
        # Capture the position right after the node before any
        # mutation, since removeChild() invalidates a removed node's
        # sibling links.
        $tAfterEnd = $tNodeStart->nextSibling;
        $tInsertBefore = $tAfterEnd;

        if ($tPrefix !== "" and $tSuffix !== "") {
            $tNodeStart->nodeValue = $tPrefix;
            $tSuffixNode = $pDoc->createTextNode($tSuffix);
            $tParent->insertBefore($tSuffixNode, $tAfterEnd);
            $tInsertBefore = $tSuffixNode;
        } elseif ($tPrefix !== "") {
            $tNodeStart->nodeValue = $tPrefix;
            # $tInsertBefore is already $tAfterEnd
        } elseif ($tSuffix !== "") {
            $tNodeStart->nodeValue = $tSuffix;
            $tInsertBefore = $tNodeStart;
        } elseif ($tParent->nodeName == "text:span"
                and $tParent->childNodes->length == 1) {
            # The node is *all* of a text:span: drop the span too, so
            # the template's own style ("Endnote_20_Symbol") applies
            # instead of the span's.
            $tGrandParent = $tParent->parentNode;
            $tAfterEnd = $tParent->nextSibling;
            $tGrandParent->removeChild($tParent);
            $tParent = $tGrandParent;
            $tInsertBefore = $tAfterEnd;
        } else {
            $tParent->removeChild($tNodeStart);
            # $tInsertBefore is already $tAfterEnd
        }
    } else {
        # Capture the position right after the match before any
        # mutation, since removeChild() invalidates a removed node's
        # sibling links.
        $tAfterEnd = $tOuterEnd->nextSibling;
        $tInsertBefore = $tAfterEnd;

        # Remove every outer node strictly between start and end.
        $tMiddleStart = $tOuterStart->nextSibling;
        $tCur = $tMiddleStart;
        while ($tCur !== null and $tCur !== $tOuterEnd) {
            $tNext = $tCur->nextSibling;
            $tParent->removeChild($tCur);
            $tCur = $tNext;
        }

        if ($tPrefix !== "")
            $tNodeStart->nodeValue = $tPrefix;
        else
            $tParent->removeChild($tOuterStart);

        if ($tSuffix !== "") {
            $tNodeEnd->nodeValue = $tSuffix;
            $tInsertBefore = $tOuterEnd;
        } else {
            $tParent->removeChild($tOuterEnd);
            # $tInsertBefore is already $tAfterEnd
        }
    }

    $tParent->insertBefore($tFrag, $tInsertBefore);

    return true;    # ---------->
} # fReplaceCite

# -----------------------------
function fProcessBlock($pDoc, $pBlock) {
    # Scan one block-level element (a paragraph or heading) for cites,
    # and replace each one found in the DB. Matching is done on the
    # block's whole text, so a cite split across character-style spans
    # is still found. See fReplaceCite().

    global $gNumCite;

    $tNodeList = fCollectTextNodeList($pBlock);
    if (count($tNodeList) == 0)
        return;    # ---------->

    $tFullText = "";
    $tOffset = array();
    foreach ($tNodeList as $tNode) {
        $tStart = strlen($tFullText);
        $tFullText .= $tNode->nodeValue;
        $tOffset[] = array($tStart, strlen($tFullText));
    }

    $tCiteList = fFindCiteList($tFullText);
    if (count($tCiteList) == 0)
        return;    # ---------->

    # Look up every Id first (this is also what prints the "not in DB"
    # warning). Apply replacements back-to-front, so that editing one
    # cite does not disturb the recorded offsets of the others.
    foreach ($tCiteList as &$tMatch)
        $tMatch['cite'] = fBibLookup($tMatch['id']);
    unset($tMatch);

    for ($tI = count($tCiteList) - 1; $tI >= 0; --$tI) {
        $tMatch = $tCiteList[$tI];
        if ( ! $tMatch['cite'])
            continue;    # Not in DB; already warned in fBibLookup().

        $tCite = $tMatch['cite'];
        $tCite['loc'] = $tMatch['loc'];

        if (fReplaceCite($pDoc, $tNodeList, $tOffset, $tCite,
                $tMatch['start'], $tMatch['end'])) {
            ++$gNumCite;
            if ($gNumCite % 10 == 0)
                echo '.';
        }
    }

    return;    # ---------->
} # fProcessBlock

# -----------------------------
function fProcessFile() {
    global $cgDirTmp;
    global $gNumCite;

    echo "Start processing [bib-ref-new.inc:" . __LINE__ . "]\n";

    $tDoc = uLoadXml("$cgDirTmp/content.xml");
    $tXpath = new DOMXPath($tDoc);
    $tNumNode = $tXpath->query("//text()")->length;

    # Process one paragraph/heading at a time: grouping by block (rather
    # than one text node at a time) lets a cite split across
    # character-style spans still be found and replaced.
    $tBlockList = array();
    foreach ($tXpath->query("//*[local-name()='p' or local-name()='h']") as $tBlock)
        $tBlockList[] = $tBlock;

    $gNumCite = 0;
    foreach ($tBlockList as $tBlock)
        fProcessBlock($tDoc, $tBlock);

    echo "\nScanned $tNumNode text nodes. [bib-ref-new.inc:" . __LINE__ . "]\n";
    echo "Replaced $gNumCite citations. [bib-ref-new.inc:" . __LINE__ . "]\n";

    uSaveXml($tDoc, "$cgDirTmp/content.new.xml");

    return;    # ---------->
} # fProcessFile

?>
