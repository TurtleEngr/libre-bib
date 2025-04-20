<?php

/*
These globals are expected for some functions.
    global $gDb;      - DB Handle
    global $cgDebug;  - true: output debug, false: no output
    global $cgNoExec; - true: no-exec, false: exec
*/

# -----------------------------
function uExecSql($pSql) {
    global $gDb;
    global $cgDebug;
    global $cgNoExec;

    if ($cgDebug) print_r("\n$pSql \n[util.php:" . __LINE__ . "]\n\n");
    if ( ! $cgNoExec)
        return $gDb->query($pSql);
    return true;
} # fExecSql

# --------------------
function uRenameTable($pTable) {
    global $cgNoExec;

    # Append: "_MM-DD_HH-MM"
    $tNewName = "$pTable" . "_" . uDate("iso");

    $tSql = "RENAME TABLE `$pTable` TO `$tNewName`";
    uExecSql("$tSql");
    if ($cgNoExec)
        return $tNewName;

    if ( ! uTableExists("$tNewName"))
        throw new Exception("\nError: Backup failed: $tSql \n[util.php:" . __LINE__ . "]");

    echo "Created: $tNewName \n";
    return $tNewName;
} # fRenameTable

# --------------------
function uTableExists($pName) {
    global $gDb;

    return $gDb->query("SHOW TABLES LIKE '" . $pName . "'")->rowCount() > 0;
} # fTableExists

# --------------------
function uListBackup($pName) {
    global $gDb;

    $tRecH = $gDb->prepare("SHOW TABLES LIKE '" . $pName . "_%'");
    $tRecH->execute();
    return $tRecH->fetch(PDO::FETCH_BOTH);
    # print_r(ReturnArray);
} # fListBackup

# --------------------
function uDate($pStyle = "iso") {
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

    $pStyle = strtolower($pStyle);
    if (array_key_exists($pStyle, $tFmt))
        return date($tFmt[$pStyle]);
    return date($tFmt["iso"]);
} # fDate

# -----------------------------
function uValidateCommon() {
    global $cgDbHost;
    global $cgDbName;
    global $cgDbPassCache;
    global $cgDbPortLocal;
    global $cgDbPortRemote;
    global $cgDbUser;
    global $cgDebug;
    global $cgUseRemote;
    global $gDb;
    global $gPassword;

    if ( ! file_exists("$cgDbPassCache"))
        throw new Exception("Missing: cgDbPassCache $cgDbPassCache. To set it, run: bib connect [util.php:" . __LINE__ . "]");

    $gPassword = rtrim(shell_exec("/bin/bash -c 'cat $cgDbPassCache'"));
    if ("$gPassword" == "")
        echo "\nWarning: Password is null [util.php:" . __LINE__ . "]\n";

    # Create database connection
    $tDsn = "mysql:dbname=$cgDbName;charset=UTF8;host=$cgDbHost;port=";
    if ($cgUseRemote)
        $tDsn .= $cgDbPortRemote;
    else
        $tDsn .= $cgDbPortLocal;

    if ($cgDebug) { echo "$tDsn, $cgDbUser \n"; }
    $gDb = new PDO($tDsn, $cgDbUser, "$gPassword");
    # This will throw a fatal error if cannot connect
} # fValidateCommon

# -----------------------------
function uBool($pVal) {
    #$tMap = array("0"=>0, "1"=>1, "f"=>0, "false"=>0, "t"=>1,
    #    "true"=>1, "n"=>0, "no"=>0, "y"=>1, "yes"=>1, 0=>0, 1=>1);
    #$pVal = strtolower($pVal);

    $tMap = array("false"=>0, "true"=>1 );

    if (array_key_exists($pVal, $tMap))
        return $tMap[$pVal];
    return 0;
} # fBool

# -----------------------------
function uFixBool() {
    global $cgDebug;
    global $cgNoExec;
    global $cgUseLib;
    global $cgUseRemote;
    global $cgVerbose;

    $cgDebug = uBool($cgDebug);
    $cgNoExec = uBool($cgNoExec);
    $cgUseLib = uBool($cgUseLib);
    $cgUseRemote = uBool($cgUseRemote);
    $cgVerbose = uBool($cgVerbose);

    if ($cgVerbose) {
        if ($cgDebug)
            echo "Debug is on.\n";
        if ($cgNoExec)
            echo "NoExec is on.\n";
        if ($cgVerbose)
            echo "Verbose is on.\n";
        if ($cgUseRemote)
            echo "UseRemote is on.\n";
        if ($cgUseLib)
            echo "UseLib is on.\n";
    }
} # fFixBool

# -----------------------------
function uUnpackFile($pDocFile, $pFileList) {
    global $cgDebug;
    global $cgDirTmp;

    $cTidyOpt = "-m -q --tidy-mark no --break-before-br yes --indent-attributes yes --indent-spaces 2 --indent auto --input-xml yes --output-xml yes --vertical-space no --wrap 78 -xml";

    $tList = explode(" ", $pFileList);

    echo "Unpack $pDocFile [util.php:" . __LINE__ . "]\n";
    foreach ($tList as $tFile) {
        shell_exec("/bin/bash -c 'cd $cgDirTmp; unzip -o ../$pDocFile $tFile.xml'");
        if ( ! file_exists("$cgDirTmp/$tFile.xml"))
            throw new Exception("\nError: Could not extract $tFile.xml [util.php:" . __LINE__ . "]");
        # tidy the xml files
        shell_exec("/bin/bash -c 'cd $cgDirTmp; tidy $cTidyOpt $tFile.xml &>/dev/null'");
    }

    return;    # ---------->
} # fUnpackFile

# -----------------------------
function uPackFile($pDocFile, $pFileList) {
    global $cgDebug;
    global $cgNoExec;
    global $cgDirTmp;

    $cTidyOpt = "-m -q --tidy-mark no --break-before-br no --indent-attributes no --indent no --input-xml yes --output-xml yes --vertical-space no --wrap 4000 -xml";

    if ($cgNoExec) {
        echo "No changes to $pDocFile See $pFileList in $cgDirTmp [util.php:" . __LINE__ . "]\n";
        return;    # ---------->
    }

    $tList = explode(" ", $pFileList);

    echo "Repack $pDocFile [util.php:" . __LINE__ . "]\n";
    foreach ($tList as $tFile) {
        shell_exec("/bin/bash -c 'cd $cgDirTmp; tidy $cTidyOpt $tFile.xml'");
        # Remove newlines between tags, to remove any spaces in the text
        shell_exec("/bin/bash -c \"cd $cgDirTmp; sed -i 's|\n*| |g' $tFile.xml\"");
        shell_exec("/bin/bash -c 'cd $cgDirTmp; zip ../$pDocFile $tFile.xml'");
    }

    return;    # ---------->
} # fPackFile

# ========================================
# Map Functions

# --------------------
function uLibCol() {
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
function uLoCol() {
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
        "ISBN",
    );
    return $tCol;
} # fLoCol

function uLoColValue() {
    $tColList = uLoCol();
    foreach (array_values($tColList) as $tCol)
        $tColVal["$tCol"] = "";
    return $tColVal;
} # fLoColValue

# --------------------
function uTxt2LoMap($pTxt = "") {
    # Return associative array for txt name to lo col names.
    # or if pTxt is defined, return the matching value.
    # (some are aliases)
    # What if no match?

    $tLowerMap = array();

    $tMap = array(
        "ASIN"=>"Custom3",
        "Address"=>"Address",
        "Alt"=>"Custom1",
        "AltLink"=>"Custom1",
        "Altlink"=>"Custom1",
        "Annotate"=>"Annote",
        "Annot"=>"Annote",
        "Annote"=>"Annote",
        "Author"=>"Author",
        "Authors"=>"Custom2",
        "Booktitle"=>"Booktitle",
        "BookTitle"=>"Booktitle",
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
        "Location"=>"Address",
        "LongDesc"=>"Annote",
        "Media"=>"RepType",
        "Month"=>"Month",
        "Note"=>"Note",
        "Notes"=>"Note",
        "Number"=>"Number",
        "Organizat"=>"Organizat",
        "Organization"=>"Organizat",
        "Pages"=>"Pages",
        "Place"=>"Address",
        "Producer"=>"Publisher",
        "Publication"=>"Publisher",
        "Publisher"=>"Publisher",
        "RepType"=>"RepType",
        "School"=>"School",
        "Series"=>"Series",
        "SubTitle"=>"Title",
        "Subtitle"=>"Title",
        "Tag"=>"Note",
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
        if (array_key_exists($pTxt, $tMap)) {
            $tName = $tMap["$pTxt"];
            return $tName;
        }

        # Make a lowercase map
        foreach (array_keys($tMap) as $tKey) {
            $tLowerMap[strtolower($tKey)] = $tMap[$tKey];
        }
        $pTxt = strtolower($pTxt);
        if (array_key_exists($pTxt, $tLowerMap))
            return $tLowerMap[$pTxt];
        return "Unknown";
    } else {
        return $tMap;
    }
} # fTxt2LoMap

# --------------------
function uLo2TxtMap($pLo = "") {
    # Return associative array for lo col names to txt names
    # or if pLo is defined, return the matching value.
    # What if no match?

    $tMap = array(
        "Address"=>"Place",
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
        "Title"=>"Subtitle",
        "Type"=>"Type",
        "URL"=>"Link",
        "Volume"=>"Volume",
        "Year"=>"Date"
    );
    if ("$pLo" != "") {
        if (array_key_exists($pLo, $tMap))
            return $tMap["$pLo"];
        return "Unknown";
    } else {
        return $tMap;
    }
} # fLo2TxtMap

# --------------------
function uMedia2RepType($pMedia = "") {
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
        if ( ! array_key_exists($pMedia, $tMap))
            $pMedia = "unknown";
        return $tMap[strtolower($pMedia)];
    } else {
        return $tMap;
    }
} # fMedia2RepType

# --------------------
function uRepType2Type($pMedia = "") {
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
    if ("$pMedia" != "") {
        $pMedia = strtolower($pMedia);
        if (array_key_exists($pMedia, $tMap))
            return $tMap[$pMedia];
        return 16;
    } else {
        return $tMap;
    }
} # fRepType2Type

# --------------------
function uType2Txt($pType = "") {
    $tMap = array(
        0=>"article",
        1=>"book",
        10=>"media",
        16=>"site"
    );
    if ("$pType" != "") {
        if (array_key_exists($pType, $tMap))
            return $tMap[$pType];
        return "site";
    } else {
        return $tMap;
    }
} # fType2Txt

# --------------------
function uTxt2Type($pType = "") {
    return uRepType2Type($pType);
} # fTxt2Type

# --------------------
function uTxt2RepType($pTxt = "") {
    return uMedia2RepType($pTxt);
} # fTxt2RepType

# --------------------
function uLib2Lo($pCol = "") {
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
    if ("$pCol" != "") {
        if (array_key_exists($pCol, $tMap))
            return $tMap["$pCol"];
        return "Unknown";
    } else {
        return $tMap;
    }
}; # fLib2Lo

# --------------------
function uBib2Xml($pCol = "") {
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
    if ("$pCol" != "") {
        if (array_key_exists($pCol, $tMap))
            return $tMap["$pCol"];
        return "custom5";
    } else {
        return $tMap;
    }
} # fBib2Xml

# --------------------
function uBibType2Xml($pType = "") {
    # Return associative array for Bib Type to Txt names
    # or if pCol is defined, return the matching value.
    # What if no match?

    $tMap = array(
        0=>'article',
        1=>'book',
        10=>'misc',
        16=>'www'
    );
    if ("$pType" != "") {
        if (array_key_exists($pType, $tMap))
            return $tMap["$pType"];
        return "www";
    } else {
        return $tMap;
    }
} # fBibType2Xml

?>
