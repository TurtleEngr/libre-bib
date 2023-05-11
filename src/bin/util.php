<?php

/*
These globals are expected for some functions.
    global $gDb;      - DB Handle
    global $gpDebug;  - true: output debug, false: no output
    global $gpNoExec; - true: no-exec, false: exec
*/

# -----------------------------
function fExecSql($pSql) {
    global $gDb;
    global $gpDebug;
    global $gpNoExec;

    if ($gpDebug) print_r("\n$pSql \n" . __FILE__ . "[" . __LINE__ . "]\n\n");
    if (! $gpNoExec)
        return $gDb->query($pSql);
    return true;
} # fExecSql

# --------------------
function fRenameTable($pTable) {
    global $gpNoExec;
    
    # Append: "_MM-DD_HH-MM"
    $tNewName = "$pTable" . "_" . fDate("iso");

    $tSql = "RENAME TABLE `$pTable` TO `$tNewName`";
    fExecSql("$tSql");
    if ($gpNoExec)
        return $tNewName;

    if (! fTableExists("$tNewName"))
        throw new Exception("Error: Backup failed: $tSql \n" . __FILE__ . "[" . __LINE__ . "]");

    echo "Created: $tNewName \n" . __FILE__ . "[" . __LINE__ . "]\n";
    return $tNewName;
} # fRenameTable

# --------------------
function fTableExists($pName) {
    global $gDb;
    
    return $gDb->query("SHOW TABLES LIKE '" . $pName . "'")->rowCount() > 0;
} # fTableExists

# --------------------
function fListBackup($pName) {
    global $gDb;
    
    $tRecH = $gDb->prepare("SHOW TABLES LIKE '" . $pName . "_%'");
    $tRecH->execute();
    return $tRecH->fetch(PDO::FETCH_BOTH);
    # print_r(ReturnArray);
} # fListBackup

# --------------------
function fDate($pStyle = "iso") {
    # iso - 2004-02-12_15-19-21
    # min - 02-12_15-19
    # num - 20040212151921
    # ymd - 2004-02-12

    $tFmt = array(
        "iso"=>"Y-m-d_H-i-s",
        "min"=>"m-d_H-i",
        "n"=>"YmdHis",
        "num"=>"YmdHis",
        "ymd"=>"Y-m-d"
    );

    return date($tFmt[strtolower($pStyle)]);
} # fDate

# --------------------
function fLibCol() {
    # Return zero based array of column names for lib tables.
    
    $tCol = array(
        "Book_Id",
        "Title",
        "Sort_Character",
        "Primary_Author",
        "Primary_Author_Role",
        "Secondary_Author",
        "Secondary_Author_Roles",
        "Publication",
        "Date",
        "Review",
        "Rating",
        "Comment",
        "Private_Comment",
        "Summary",
        "Media",
        "Physical_Description",
        "Weight",
        "Height",
        "Thickness",
        "Length",
        "Dimensions",
        "Page_Count",
        "LCCN",
        "Acquired",
        "Date_Started",
        "Date_Read",
        "Barcode",
        "BCID",
        "Tags",
        "Collections",
        "Languages",
        "Original_Languages",
        "LC_Classification",
        "ISBN",
        "ISBNs",
        "Subjects",
        "Dewey_Decimal",
        "Dewey_Wording",
        "Other_Call_Number",
        "Copies",
        "Source",
        "Entry_Date",
        "From_Where",
        "OCLC",
        "Work_id",
        "Lending_Patron",
        "Lending_Status",
        "Lending_Start",
        "Lending_End"
    );
    return $tLibId;
} # fLibCol

# --------------------
function fLoCol() {
    # Return zero based array of column names for lo and bib tables.
    
    $tCol = array(
        "Identifier",
        "Type",
        "Address",
        "Annote",
        "Author",
        "Booktitle",
        "Chapter",
        "Edition",
        "Editor",
        "Howpublish",
        "Institutn",
        "Journal",
        "Month",
        "Note",
        "Number",
        "Organizat",
        "Pages",
        "Publisher",
        "School",
        "Series",
        "Title",
        "RepType",
        "Volume",
        "Year",
        "URL",
        "Custom1",
        "Custom2",
        "Custom3",
        "Custom4",
        "Custom5",
        "ISBN"
    );
    return $tCol;
} # fLoCol

function fLoColValue() {
    $tColList = fLoCol();
    foreach (array_values($tColList) as $tCol)
        $tColVal["$tCol"] = "";
    return $tColVal;
} # fLoColValue

# --------------------
function fTxt2LoMap($pTxt = "") {
    # Return associative array for txt name to lo col names.
    # or if pTxt is defined, return the matching value.
    # (some are aliases)
    # What if no match?

    # SubTitle should be appended to Booktitle, then cleared

    $tLowerMap = array();

    $tMap = array(    
        "ASIN"=>"Custom3",
        "Address"=>"Address",
        "Alt"=>"Custom1",
        "AltLink"=>"Custom1",
        "Annote"=>"Annote",
        "Author"=>"Author",
        "Authors"=>"Custom2",
        "Booktitle"=>"Booktitle",
        "Channel"=>"Publisher",
        "Chapter"=>"Chapter",
        "Custom1"=>"Custom1",
        "Custom2"=>"Custom2",
        "Custom3"=>"Custom3",
        "Custom4"=>"Custom4",
        "Custom5"=>"Custom5",
        "Date"=>"Year",
        "DateSeen"=>"Custom4",
        "Edition"=>"Edition",
        "Editor"=>"Editor",
        "Howpublish"=>"Howpublish",
        "ISBN"=>"ISBN",
        "Id"=>"Identifier",
        "Identifier"=>"Identifier",
        "Institution"=>"Institutn",
        "Institutn"=>"Institutn",
        "Journal"=>"Journal",
        "Link"=>"URL",
        "LongDesc"=>"Annote",
        "Media"=>"RepType",
        "Month"=>"Month",
        "Note"=>"Note",
        "Number"=>"Number",
        "Organizat"=>"Organizat",
        "Organization"=>"Organizat",
        "Pages"=>"Pages",
        "Producer"=>"Publisher",
        "Publication"=>"Publisher",
        "Publisher"=>"Publisher",
        "RepType"=>"RepType",
        "School"=>"School",
        "Series"=>"Series",
        "SubTitle"=>"Title",
        "Tags"=>"Note",
        "Title"=>"Booktitle",
        "Type"=>"Type",
        "URL"=>"URL",
        "University"=>"School",
        "Volume"=>"Volume",
        "Year"=>"Year",
        "{article}"=>"RepType",
        "{audio}"=>"RepType",
        "{book}"=>"RepType",
        "{link}"=>"RepType",
        "{ted}"=>"RepType",
        "{video}"=>"RepType",
        "{youtube}"=>"RepType",
        "article"=>"RepType",
        "audio"=>"RepType",
        "book"=>"RepType",
        "link"=>"RepType",
        "ted"=>"RepType",
        "video"=>"RepType",
        "youtube"=>"RepType",
    );

    if ("$pTxt" != "") {
        $tName = $tMap["$pTxt"];
        if ($tName != "")
            return $tName;

        # Try to match with lowercase names
        foreach (array_key($tMap) as $tKey)
            $tLowerMap[strtolower($tKey)] = $tMap[$tKey];

        return $tLowerMap[strtolower($pTxt)];
    } else {
        return $tMap;
    }
} # fTxt2LoMap

# --------------------
function fLo2TxtMap($pLo = "") {
    # Return associative array for lo col names to txt names
    # or if pLo is defined, return the matching value.
    # What if no match?

    $tMap = array(
        "Address"=>"Address",
        "Annote"=>"Annote",
        "Author"=>"Author",
        "Booktitle"=>"Title",
        "Chapter"=>"Chapter",
        "Custom1"=>"AltLink",
        "Custom2"=>"Authors",
        "Custom3"=>"ASIN",
        "Custom4"=>"DateSeen",
        "Custom5"=>"Custom5",
        "Edition"=>"Edition",
        "Editor"=>"Editor",
        "Howpublish"=>"Howpublish",
        "ISBN"=>"ISBN",
        "Identifier"=>"Id",
        "Institutn"=>"Institution",
        "Journal"=>"Journal",
        "Month"=>"Month",
        "Note"=>"Tags",
        "Number"=>"Number",
        "Organizat"=>"Organization",
        "Pages"=>"Pages",
        "Publisher"=>"Publisher",
        "RepType"=>"Media",
        "School"=>"School",
        "Series"=>"Series",
        "Title"=>"Err-SubTitle",
        "Type"=>"Type",
        "URL"=>"Link",
        "Volume"=>"Volume",
        "Year"=>"Date"
    );
    if ("$pLo" != "")
        return $tMap["$pLo"];
    else
        return $tMap;
} # fLo2TxtMap

# --------------------
function fMedia2RepType($pMedia = "") {
    # Return associative array for lib Media names to lo RepType names
    # or if pMedia is defined, return the matching value.
    # What if no match?

    $tMap = array(
        "a/v"=>"video",
        "article"=>"article",
        "audio"=>"audio",
        "blu-ray"=>"Blu-ray",
        "bluray"=>"Blu-ray",
        "book"=>"book",
        "dvd"=>"DVD",
        "ebook"=>"Ebook",
        "hardcover"=>"Hardcover",
        "kindle"=>"Ebook",
        "laserdisc"=>"Laserdisc",
        "link"=>"article",
        "media"=>"video",
        "mp3"=>"mp3",
        "mp4"=>"mp4",
        "org"=>"org",
        "paperback"=>"Paperback",
        "paperbook"=>"Paperback",
        "podcast"=>"podcast",
        "product"=>"product",
        "site"=>"site",
        "ted"=>"video",
        "unknown"=>"unknown",
        "vhs"=>"VHS",
        "video"=>"video",
        "website"=>"website",
        "youtube"=>"youtube",
        "{article}"=>"article",
        "{audio}"=>"audio",
        "{book}"=>"book",
        "{kindle}"=>"Ebook",
        "{link}"=>"article",
        "{mp3}"=>"mp3",
        "{mp4}"=>"mp4",
        "{ted}"=>"video",
        "{video}"=>"video",
        "{youtube}"=>"youtube"
    );
    if ("$pMedia" != "") {
        if (! in_array($pMedia, $tMap))
            $pMedia = "unknown";
        return $tMap[strtolower($pMedia)];
    } else {
        return $tMap;
    }
} # fMedia2RepType

# --------------------
function fRepType2Type($pMedia = "") {
    # Return associative array for RepType Media names to Type numbers
    # or if pMedia is defined, return the matching value.
    # What if no match?

    $tMap = array(
        "a/v"=>10,
        "article"=>0,
        "audio"=>10,
        "blu-ray"=>10,
        "bluray"=>10,
        "book"=>1,
        "dvd"=>10,
        "ebook"=>1,
        "hardcover"=>1,
        "laserdisc"=>16,
        "link"=>0,
        "media"=>10,
        "mp3"=>10,
        "mp4"=>10,
        "org"=>16,
        "paper book"=>1,
        "paperback"=>1,
        "podcast"=>10,
        "product"=>16,
        "site"=>16,
        "ted"=>10,
        "unknown"=>16,
        "vhs"=>10,
        "video"=>10,
        "website"=>16,
        "youtube"=>10
    );
    if ("$pMedia" != "")
        return $tMap[strtolower($pMedia)];
    else
        return $tMap;
} # fRepType2Type

# --------------------
function fType2Txt($pType = "") {

    $tMap = array(
        0=>"article",
        1=>"book",
        10=>"media",
        16=>"site"
    );
    if ("$pType" != "")
        return $tMap[$pType];
    else
        return $tMap;
} # fType2Txt

# --------------------
function fTxt2Type($pType = "") {
    return fRepType2Type($pType);
} # fTxt2Type

# --------------------
function fTxt2RepType($pTxt = "") {
    return fMedia2RepType($pTxt);
} # fTxt2RepType

# --------------------
function fLib2Lo($pCol = "") {
    # Return associative array for Lib col names to Lo col names
    # or if pCol is defined, return the matching value.
    # What if no match?

    # "Book_Id"=>"Identifier";  # join
    # "Title"=>"Booktitle";     # join match left 40
    # "Media"=>"RepType";       # convert value to Type(N)
    # "ISBN"=>"ISBN";           # fix value: s/[\[\]]//g

    $tMap = array(
        "Book_Id"=>"Identifier",
        "Title"=>"Booktitle",
        "Primary_Author"=>"Author",
        "Secondary_Author"=>"Custom2",
        "Publication"=>"Publisher",
        "Date"=>"Year",
        "Media"=>"RepType",
        "ISBN"=>"ISBN",
        "Work_id"=>"Custom3"
    );
    if ("$pCol" != "")
        return $tMap["$pCol"];
    else
        return $tMap;
}; # fLib2Lo


# --------------------
function fBib2Xml($pCol = "") {
    # Return associative array for Bib col names to XML names
    # or if pCol is defined, return the matching value.
    # What if no match?

    $tMap = array(
        "Identifier"=>"identifier",
        "Type"=>"bibliography-type",
        "Address"=>"address",
        "Annote"=>"annote",
        "Author"=>"author",
        "Booktitle"=>"booktitle",
        "Chapter"=>"chapter",
        "Edition"=>"edition",
        "Editor"=>"editor",
        "Howpublish"=>"howpublished",
        "Institutn"=>"institution",
        "Journal"=>"journal",
        "Month"=>"month",
        "Note"=>"note",
        "Number"=>"number",
        "Organizat"=>"organizations",
        "Pages"=>"pages",
        "Publisher"=>"publisher",
        "School"=>"school",
        "Series"=>"series",
        "Title"=>"title",
        "RepType"=>"report-type",
        "Volume"=>"volume",
        "Year"=>"year",
        "URL"=>"url",
        "Custom1"=>"custom1",
        "Custom2"=>"custom2",
        "Custom3"=>"custom3",
        "Custom4"=>"custom4",
        "Custom5"=>"custom5",
        "ISBN"=>"isbn"
    );
    if ("$pCol" != "")
        return $tMap["$pCol"];
    else
        return $tMap;
} # fBib2Xml

# --------------------
function fBibType2Xml($pType = "") {
    # Return associative array for Bib Type to Txt names
    # or if pCol is defined, return the matching value.
    # What if no match?

    $tMap = array(
        0=>'article',
        1=>'book',
        10=>'misc',
        16=>'www'
    );
    if ("$pType" != "")
        return $tMap["$pType"];
    else
        return $tMap;
} # fBibType2Xml

?>
