<?php

# ========================================
# util.php - the utility classes used by every CLI script.
#
#     Db   - the DB connection and every SQL call (holds the PDO handle)
#     Util - conf loading, dates, bools, odt pack/unpack
#     Xml  - DOMDocument load/save/find
#     Map  - the column-name and type-number maps (pure data)
#
# Db and Util hold configuration in m* properties, injected through the
# constructor. Xml and Map need no configuration, so their methods are
# static.
#
# Error convention
# ----------------
# A method that cannot finish its job throws. The message begins with
# "Error:" when the caller should give up, or "Warning:" when the caller
# can carry on without that one item. It is the caller that decides:
#
#     try {
#         ...
#     } catch (Exception $e) {          # Warning: echo and keep going
#         echo $e->getMessage() . "\n";
#         continue;
#     }
#
# and the CLI wrapper catches whatever reaches the top and exits.
#
# See style.org for the naming convention.
# ========================================

class Db {
    # The DB connection. One instance per script, built by uFromConf().

    public $mPdo;         # PDO handle
    public $mDebug;
    public $mNoExec;
    public $mPassword;

    # --------------------
    public function __construct($pPdo, $pConf) {
        $this->mPdo = $pPdo;
        $this->mDebug = $pConf["cgDebug"];
        $this->mNoExec = $pConf["cgNoExec"];
        $this->mPassword = isset($pConf["gPassword"]) ? $pConf["gPassword"] : "";
    } # __construct

    # --------------------
    public static function uFromConf($pConf) {
        # Connect with the values in pConf and return a Db. Was
        # uValidateCommon().

        if ( ! file_exists($pConf["cgDbPassCache"]))
            throw new Exception("Error: Missing: cgDbPassCache " .
                $pConf["cgDbPassCache"] . ". To set it, run: bib connect [util.php:" .
                __LINE__ . "]");

        $tPassword = rtrim(shell_exec("/bin/bash -c 'cat " .
                $pConf["cgDbPassCache"] . " | rot13.sh -d'"));
        if ("$tPassword" == "")
            echo "\nWarning: Password is null [util.php:" . __LINE__ . "]\n";

        $tDsn = "mysql:dbname=" . $pConf["cgDbName"] . ";charset=UTF8;host=" .
            $pConf["cgDbHost"] . ";port=" . $pConf["cgDbPortLocal"];

        if ($pConf["cgDebug"]) echo "$tDsn, " . $pConf["cgDbUser"] . " \n";

        # A bad connection throws PDOException, which the wrapper catches.
        $tPdo = new PDO($tDsn, $pConf["cgDbUser"], "$tPassword");

        $pConf["gPassword"] = $tPassword;
        return new Db($tPdo, $pConf);    # ---------->
    } # uFromConf

    # --------------------
    public function uAt($pFile, $pFunc, $pLine) {
        # Format the caller's location for an error message. The caller
        # passes __FILE__, __METHOD__ (or __FUNCTION__) and __LINE__,
        # because __LINE__ here would only ever name util.php.

        return " [" . basename($pFile) . ":$pFunc:$pLine]";    # ---------->
    } # uAt

    # --------------------
    public function uErrInfo() {
        # The DB's complaint about the last statement, as one string.

        if ($this->mPdo === null)
            return "";    # ---------->

        $tInfo = $this->mPdo->errorInfo();
        return isset($tInfo[2]) ? $tInfo[2] : "";    # ---------->
    } # uErrInfo

    # --------------------
    public function uExecSql($pSql, $pFile, $pFunc, $pLine) {
        # Run one statement. Throws if the DB rejects it, so callers do
        # not have to check the return value.

        if ($this->mDebug) print_r("\n$pSql \n[util.php:" . __LINE__ . "]\n\n");
        if ($this->mNoExec)
            return true;    # ---------->

        $tRet = $this->mPdo->query($pSql);
        if ($tRet === false) {
            $tInfo = $this->uErrInfo();
            if ($this->mDebug)
                $tInfo .= "\n\tSQL: $pSql";
            throw new Exception("\nError: $tInfo" .
                $this->uAt($pFile, $pFunc, $pLine));
        }

        return $tRet;    # ---------->
    } # uExecSql

    # --------------------
    public function uExec($pSql, $pFile, $pFunc, $pLine) {
        # Like uExecSql(), but returns the number of affected rows.

        if ($this->mDebug) print_r("\n$pSql \n[util.php:" . __LINE__ . "]\n\n");
        if ($this->mNoExec)
            return 0;    # ---------->

        $tCount = $this->mPdo->exec($pSql);
        if ($tCount === false) {
            $tInfo = $this->uErrInfo();
            if ($this->mDebug)
                $tInfo .= "\n\tSQL: $pSql";
            throw new Exception("\nError: $tInfo" .
                $this->uAt($pFile, $pFunc, $pLine));
        }

        return $tCount;    # ---------->
    } # uExec

    # --------------------
    public function uPrepare($pSql, $pFile, $pFunc, $pLine) {
        # Return a prepared statement. The caller runs execute() and
        # fetch() on it.

        if ($this->mDebug) print_r("\n$pSql \n[util.php:" . __LINE__ . "]\n\n");

        $tRecH = $this->mPdo->prepare($pSql);
        if ($tRecH === false)
            throw new Exception("\nError: " . $this->uErrInfo() .
                $this->uAt($pFile, $pFunc, $pLine));

        return $tRecH;    # ---------->
    } # uPrepare

    # --------------------
    public function uTableExists($pName) {
        return $this->mPdo->query("SHOW TABLES LIKE '" . $pName . "'")->rowCount() > 0;
    } # uTableExists

    # --------------------
    public function uListBackup($pName) {
        $tRecH = $this->mPdo->prepare("SHOW TABLES LIKE '" . $pName . "_%'");
        $tRecH->execute();
        return $tRecH->fetch(PDO::FETCH_BOTH);    # ---------->
    } # uListBackup

    # --------------------
    public function uRenameTable($pTable, $pFile, $pFunc, $pLine) {
        # Append: "_MM-DD_HH-MM"

        $tNewName = "$pTable" . "_" . Util::uDate("iso");

        $tSql = "RENAME TABLE `$pTable` TO `$tNewName`";
        $this->uExecSql("$tSql", $pFile, $pFunc, $pLine);
        if ($this->mNoExec)
            return $tNewName;    # ---------->

        if ( ! $this->uTableExists("$tNewName"))
            throw new Exception("\nError: Backup failed: $tSql" .
                $this->uAt($pFile, $pFunc, $pLine));

        echo "Created: $tNewName \n";
        return $tNewName;    # ---------->
    } # uRenameTable

} # Db

# ========================================

class Util {
    # Conf loading and the odd jobs that need conf values.

    public $mDebug;
    public $mNoExec;
    public $mDirTmp;

    # --------------------
    public function __construct($pConf) {
        $this->mDebug = $pConf["cgDebug"];
        $this->mNoExec = $pConf["cgNoExec"];
        $this->mDirTmp = $pConf["cgDirTmp"];
    } # __construct

    # --------------------
    public static function uLoadConf() {
        # Read etc/conf.php and return its cg* values as an array, ready
        # to hand to a constructor. conf.php still sets the globals, so
        # anything not yet converted keeps working.

        if ( ! isset($_ENV["cgDirApp"]))
            throw new Exception("\nError: cgDirApp is not set. Run: . bib-env [util.php:" .
                __LINE__ . "]");

        $tConf = $_ENV["cgDirApp"] . "/etc/conf.php";
        if ( ! file_exists($tConf))
            throw new Exception("\nError: Missing: $tConf [util.php:" . __LINE__ . "]");

        require "$tConf";

        self::uFixBool();

        $tList = array("cgBin", "cgDirApp", "cgDebug", "cgNoExec", "cgVerbose",
            "cgDirBackup", "cgDirEtc", "cgDirStatus", "cgDirTmp", "cgDirCache",
            "cgDirLibreofficeConf", "cgBackupNum", "cgDbHost", "cgDbName",
            "cgDbPassCache", "cgDbPassHint", "cgDbPortLocal", "cgDbTblBib",
            "cgDbTblLo", "cgDbUser", "cgLoFile", "cgDocFile", "cgBackupFile");

        $tRet = array();
        foreach ($tList as $tName)
            $tRet[$tName] = isset($GLOBALS[$tName]) ? $GLOBALS[$tName] : "";

        return $tRet;    # ---------->
    } # uLoadConf

    # --------------------
    public static function uRunTest($pTestClass, $pTest) {
        # The -T option: run this script's phpunit test. pTest is "all",
        # or the name of one test method. Returns the exit status.

        $tDirApp = isset($_ENV["cgDirApp"]) ? $_ENV["cgDirApp"] : dirname(__DIR__);
        $tPhpUnit = __DIR__ . "/phpunit";
        $tDirTest = "$tDirApp/test";

        if ( ! file_exists($tPhpUnit))
            throw new Exception("\nError: Missing: $tPhpUnit [util.php:" . __LINE__ . "]");
        if ( ! file_exists("$tDirTest/$pTestClass.php"))
            throw new Exception("\nError: Missing: $tDirTest/$pTestClass.php [util.php:" .
                __LINE__ . "]");

        $tCmd = escapeshellcmd($tPhpUnit) .
            " --configuration " . escapeshellarg("$tDirTest/phpunit.xml") .
            " " . escapeshellarg("$tDirTest/$pTestClass.php");
        if ($pTest != "all")
            $tCmd .= " --filter " . escapeshellarg($pTest);

        $tStatus = 0;
        passthru($tCmd, $tStatus);
        return $tStatus;    # ---------->
    } # uRunTest

    # --------------------
    public static function uDate($pStyle = "iso") {
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
            return date($tFmt[$pStyle]);    # ---------->
        return date($tFmt["iso"]);    # ---------->
    } # uDate

    # --------------------
    public static function uBool($pVal) {
        $tMap = array("false"=>0, "true"=>1 );

        if (array_key_exists($pVal, $tMap))
            return $tMap[$pVal];    # ---------->
        return 0;    # ---------->
    } # uBool

    # --------------------
    public static function uFixBool() {
        # The env values arrive as the strings "true"/"false".

        global $cgDebug;
        global $cgNoExec;
        global $cgVerbose;

        $cgDebug = self::uBool($cgDebug);
        $cgNoExec = self::uBool($cgNoExec);
        $cgVerbose = self::uBool($cgVerbose);

        if ($cgVerbose) {
            if ($cgDebug)
                echo "Debug is on.\n";
            if ($cgNoExec)
                echo "NoExec is on.\n";
            if ($cgVerbose)
                echo "Verbose is on.\n";
        }
    } # uFixBool

    # --------------------
    public function uUnpackFile($pDocFile, $pFileList) {
        $tList = explode(" ", $pFileList);

        echo "Unpack $pDocFile [util.php:" . __LINE__ . "]\n";
        foreach ($tList as $tFile) {
            shell_exec("/bin/bash -c 'cd " . $this->mDirTmp .
                "; unzip -o ../$pDocFile $tFile.xml'");
            if ( ! file_exists($this->mDirTmp . "/$tFile.xml"))
                throw new Exception("\nError: Could not extract $tFile.xml [util.php:" .
                    __LINE__ . "]");
        }

        return;    # ---------->
    } # uUnpackFile

    # --------------------
    public function uPackFile($pDocFile, $pFileList) {
        if ($this->mNoExec) {
            echo "No changes to $pDocFile See $pFileList in " . $this->mDirTmp .
                " [util.php:" . __LINE__ . "]\n";
            return;    # ---------->
        }

        $tList = explode(" ", $pFileList);

        echo "Repack $pDocFile [util.php:" . __LINE__ . "]\n";
        foreach ($tList as $tFile) {
            # Remove newlines between tags, to remove any spaces in the text
            shell_exec("/bin/bash -c \"cd " . $this->mDirTmp .
                "; sed 's|\\n| |g' <$tFile.new.xml >$tFile.xml\"");
            shell_exec("/bin/bash -c 'cd " . $this->mDirTmp .
                "; zip ../$pDocFile $tFile.xml'");
        }

        return;    # ---------->
    } # uPackFile

} # Util

# ========================================

class Xml {
    # DOMDocument helpers. No configuration, so static.

    # --------------------
    public static function uLoadXml($pPath) {
        # Parse an unpacked odt XML file and return the DOMDocument.
        # A parse error is reported through the exception, not as a php warning.

        if ( ! file_exists($pPath))
            throw new Exception("\nError: Missing: $pPath [util.php:" . __LINE__ . "]");

        $tDoc = new DOMDocument();
        $tDoc->preserveWhiteSpace = true;
        $tDoc->formatOutput = false;

        $tWasQuiet = libxml_use_internal_errors(true);
        $tOk = $tDoc->load($pPath);
        $tErr = libxml_get_last_error();
        libxml_clear_errors();
        libxml_use_internal_errors($tWasQuiet);

        if ( ! $tOk)
            throw new Exception("\nError: Could not parse $pPath: " .
                ($tErr ? trim($tErr->message) . " at line " . $tErr->line : "unknown") .
                " [util.php:" . __LINE__ . "]");

        return $tDoc;    # ---------->
    } # uLoadXml

    # --------------------
    public static function uSaveXml($pDoc, $pPath) {
        # Write a DOMDocument back out.

        if ($pDoc->save($pPath) === false)
            throw new Exception("\nError: Could not write $pPath [util.php:" . __LINE__ . "]");

        return;    # ---------->
    } # uSaveXml

    # --------------------
    public static function uFindElement($pDoc, $pName) {
        # Return the first element with local name pName, or null.
        # local-name() is used so the namespace prefix does not have to be
        # registered with the XPath object.

        $tXpath = new DOMXPath($pDoc);
        $tFound = $tXpath->query("//*[local-name()='" . $pName . "']");
        if ($tFound === false or $tFound->length == 0)
            return null;    # ---------->

        return $tFound->item(0);    # ---------->
    } # uFindElement

    # --------------------
    public static function uFindElementList($pDoc, $pName) {
        # Return an array of every element with local name pName. An array,
        # not a DOMNodeList, so the caller can safely replace nodes while
        # walking it.

        $tXpath = new DOMXPath($pDoc);
        $tRet = array();
        foreach ($tXpath->query("//*[local-name()='" . $pName . "']") as $tNode)
            $tRet[] = $tNode;

        return $tRet;    # ---------->
    } # uFindElementList

    # ========================================
    # Map Functions

} # Xml

# ========================================

class Map {
    # The column-name and type-number maps. Pure data, so static.

    # --------------------
    public static function uLibCol() {
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
        return $tCol;
    } # uLibCol

    # --------------------
    public static function uLoCol() {
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
    } # uLoCol

    # --------------------
    public static function uLoColValue() {
        $tColList = self::uLoCol();
        foreach (array_values($tColList) as $tCol)
            $tColVal["$tCol"] = "";
        return $tColVal;
    } # uLoColValue

    # --------------------
    public static function uTxt2LoMap($pTxt = "") {
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
    } # uTxt2LoMap

    # --------------------
    public static function uLo2TxtMap($pLo = "") {
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
    } # uLo2TxtMap

    # --------------------
    public static function uMedia2RepType($pMedia = "") {
        # Return associative array for lib Media names to lo RepType names
        # or if pMedia is defined, return the matching value.
        # What if no match?

        $tMap = array(
            "a/v"=>"video",
            "article"=>"article",
            "audio"=>"audio",
            "blu-ray"=>"blu-ray",
            "bluray"=>"blu-ray",
            "book"=>"book",
            "dvd"=>"dvd",
            "ebook"=>"ebook",
            "hardcover"=>"hardcover",
            "kindle"=>"ebook",
            "laserdisc"=>"laserdisc",
            "link"=>"article",
            "media"=>"video",
            "mp3"=>"mp3",
            "mp4"=>"mp4",
            "org"=>"org",
            "paperback"=>"paperback",
            "paperbook"=>"paperback",
            "paper book"=>"paperback",
            "podcast"=>"podcast",
            "product"=>"product",
            "site"=>"site",
            "ted"=>"video",
            "unknown"=>"unknown",
            "vhs"=>"vhs",
            "video"=>"video",
            "website"=>"website",
            "youtube"=>"youtube",
            "{article}"=>"article",
            "{audio}"=>"audio",
            "{book}"=>"book",
            "{kindle}"=>"ebook",
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
    } # uMedia2RepType

    # --------------------
    public static function uRepType2Type($pMedia = "") {
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
    } # uRepType2Type

    # --------------------
    public static function uType2Txt($pType = "") {
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
    } # uType2Txt

    # --------------------
    public static function uTxt2Type($pType = "") {
        return self::uRepType2Type($pType);
    } # uTxt2Type

    # --------------------
    public static function uTxt2RepType($pTxt = "") {
        return self::uMedia2RepType($pTxt);
    } # uTxt2RepType

    # --------------------
    public static function uLib2Lo($pCol = "") {
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
    } # uLib2Lo

    # --------------------
    public static function uBib2Xml($pCol = "") {
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
    } # uBib2Xml

    # --------------------
    public static function uBibType2Xml($pType = "") {
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
    } # uBibType2Xml

} # Map

?>
