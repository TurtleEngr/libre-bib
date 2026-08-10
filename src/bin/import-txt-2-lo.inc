<?php

# ========================================
# import-txt-2-lo.inc - class ImportTxt2Lo, the work done by import-txt-2-lo.php.
#
# The wrapper (import-txt-2-lo.php) holds the usage POD and the main section;
# everything else is a method here, so phpunit can drive it.
#
# See style.org for the naming convention.
# ========================================

require_once __DIR__ . "/util.php";

class ImportTxt2Lo {

    # Config, injected by the constructor.
    public $mConf;
    public $mDb;
    public $mUtil;
    public $mDbTblLo;
    public $mDebug;
    public $mLoFile;
    public $mVerbose;

    # Options, from fGetOps().
    public $mOpt;

    # Working state.
    public $mBackupName;
    public $mFileH;
    public $mNumLine;
    public $mNumRec;

    # -----------------------------
    public function __construct($pConf, $pOpt = array()) {
        $this->mConf = $pConf;
        $this->mDbTblLo = isset($pConf["cgDbTblLo"]) ? $pConf["cgDbTblLo"] : "";
        $this->mDebug = isset($pConf["cgDebug"]) ? $pConf["cgDebug"] : "";
        $this->mLoFile = isset($pConf["cgLoFile"]) ? $pConf["cgLoFile"] : "";
        $this->mVerbose = isset($pConf["cgVerbose"]) ? $pConf["cgVerbose"] : "";
        $this->mOpt = $pOpt;
        $this->mUtil = new Util($pConf);
    } # __construct

    # -----------------------------
    public static function fUsage($pScript) {
        # The POD is in the wrapper, not here.

        system("pod2text $pScript");
        exit(1);    # ---------->
    } # fUsage

    # -----------------------------
    public static function fGetOps($pArgv, $pArgc) {
        # Parse the command line only. Nothing here needs conf or a DB,
        # so -h and -T still work when neither is set up.

        $tOpt = getopt("chT:");

        return array(
            "help"=>isset($tOpt['h']) or $pArgc < 2,
            "change"=>isset($tOpt['c']),
            "test"=>isset($tOpt['T']) ? $tOpt['T'] : ""
        );    # ---------->
    } # fGetOps

    # -----------------------------
    public function fRun() {
        # The write section, as run by the wrapper.

        $this->fCreateTable();
        $this->fImportTxt();
        return;    # ---------->
    } # fRun

    # -----------------------------
    public function fCleanUp() {
        echo "\n";
    } # fCleanUp

    # -----------------------------
    public function fValidate() {
        $this->mDb = Db::uFromConf($this->mConf);

        if ( ! file_exists("$this->mLoFile"))
            throw new Exception("\nError: Missing file: $this->mLoFile. [import-txt-2-lo.php:" . __LINE__ . "]");
        if (($this->mFileH = fopen($this->mLoFile, "r")) == FALSE)
            throw new Exception("Cannot open $this->mLoFile. [import-txt-2-lo.php:" . __LINE__ . "]");
    } # fValidate

    # -----------------------------
    public function fCreateTable() {
        $this->mBackupName = "";
        if ($this->mDb->uTableExists($this->mDbTblLo))
            $this->mBackupName = $this->mDb->uRenameTable($this->mDbTblLo, __FILE__, __METHOD__, __LINE__);

        if ($this->mDb->uTableExists($this->mDbTblLo))
            $this->mDb->uExecSql("drop table $this->mDbTblLo", __FILE__, __METHOD__, __LINE__);

        $cLoCol = Map::uLoCol();
        $tSql = "CREATE TABLE $this->mDbTblLo (";
        foreach (array_values($cLoCol) as $tCol)
            $tSql .= "`$tCol` VARCHAR(255),";
        $tSql = rtrim($tSql, ",") . ")";

        $this->mDb->uExecSql("$tSql", __FILE__, __METHOD__, __LINE__);
        $this->mDb->uExecSql("alter table $this->mDbTblLo add primary key (Identifier)", __FILE__, __METHOD__, __LINE__);
    } # fCreateTable

    # -----------------------------
    public function fInsertRec($pRec) {
        $tSql = "INSERT INTO $this->mDbTblLo (`" .
            implode("`,`", array_keys($pRec)) .
            "`) VALUES (\"" .
            implode('","', array_values($pRec)) .
            '")';
        # uExecSql() throws with the DB's complaint; add which record it was.
        try {
            $this->mDb->uExecSql("$tSql", __FILE__, __METHOD__, __LINE__);
        } catch (Exception $e) {
            throw new Exception("\nError: FileLine: $this->mNumLine, Rec: $this->mNumRec\n\t" .
                $e->getMessage());
        }
    } # fInsertRec

    # -----------------------------
    public function fParseLine($pLine) {
        $tData = array("key"=>"", "val"=>"");

        $tKey = preg_replace("/: .*/", "", $pLine);
        if ($tKey == $pLine)
            return $tData;    # ---------->

        $tData["key"] = $tKey;

        $tVal = preg_replace("/^[^:]*: /", "", $pLine, 1);
        if ($tVal == $pLine)
            return $tData;    # ---------->

        $tVal = substr($tVal, 0, 254);
        $tVal = trim($tVal);
        $tData["val"] = $tVal;

        if ($this->mDebug) print_r($tData);
        return $tData;    # ---------->
    } # fParseLine($pLine)

    # -----------------------------
    public function fAddRec($pRec) {
        ++$this->mNumRec;
        if ($this->mNumRec % 50 == 0)
            echo "+";

        # Fixup Type/RepType mess (i.e. override Type setting)
        if ($pRec["RepType"] == "") {
            if (is_numeric($pRec["Type"])) {
                $pRec["RepType"] = Map::uType2Txt($pRec["Type"]);
            } else {
                echo "\nWarning: Missing Media and Type in " .
                    $pRec["Identifier"] . " [import-txt-2-lo.php:" . __LINE__ . "]\n";
                $pRec["RepType"] = "unknown";
            }
        }
        $pRec["Type"] = Map::uRepType2Type($pRec["RepType"]);

        $this->fInsertRec($pRec);
    } # fAddRec

    # -----------------------------
    public function fImportTxt() {
        $tRec = Map::uLoColValue();

        # Get lines from txt file
        $this->mNumLine = 0;
        $this->mNumRec = 0;
        while ($tLine = fgets($this->mFileH)) {
            if ($this->mVerbose)
                if ($this->mNumLine % 500 == 0)
                    echo ".";
                ++$this->mNumLine;

            if ($this->mDebug) echo "Read: $tLine\n";

            $tLine = trim($tLine);

            # Skip blank lines
            if ($tLine == "")
                continue;

            # Skip comment lines
            if (preg_match("/^#/", $tLine))
                continue;

            $tData = $this->fParseLine($tLine);

            if ($tData["key"] == "") {
                echo "\nWarning: no key found at: FileLine: $this->mNumLine, Rec: $this->mNumRec [import-txt-2-lo.php:" . __LINE__ . "]\n";
                continue;
            }

            # If Id found, insert any previous record's values to DB
            if ($tData["key"] == "Id" and $tRec["Identifier"] != "") {
                $this->fAddRec($tRec);
                foreach (array_keys($tRec) as $tCol)
                    $tRec["$tCol"] = "";
                if ($tData["val"] == "")
                    throw new Exception("\nError: No name after Id: at FileLine: $this->mNumLine, Rec: $this->mNumRec [import-txt-2-lo.php:" . __LINE__ . "]");
            }

            # Do nothing, already empty
            if ($tData["val"] == "")
                continue;

            $tKey = $tData["key"];
            $tLoCol = Map::uTxt2LoMap($tKey);
            if ($tLoCol == "Unknown") {
                echo "\nWarning: $tKey not found in KeyMap at: FileLine: $this->mNumLine, Rec: $this->mNumRec [import-txt-2-lo.php:" . __LINE__ . "]\n";
                continue;
            }

            if ($tLoCol == "Type")
                $tData["val"] = Map::uRepType2Type($tData["val"]);

            $tRec[$tLoCol] = $tData["val"];
        } # while

        # Don't forget the last record!
        $this->fAddRec($tRec);

        echo "\nProcessed $this->mNumLine lines. [import-txt-2-lo.php:" . __LINE__ . "]\n";
        echo "Inserted $this->mNumRec records. [import-txt-2-lo.php:" . __LINE__ . "]\n";
        fclose($this->mFileH);
    } # fImportTxt

} # ImportTxt2Lo

?>
