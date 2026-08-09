<?php

# ========================================
# import-tcsv-2-lo-db.inc - class ImportTcsv2LoDb, the work done by import-tcsv-2-lo-db.php.
#
# The wrapper (import-tcsv-2-lo-db.php) holds the usage POD and the main section;
# everything else is a method here, so phpunit can drive it.
#
# See style.org for the naming convention.
# ========================================

require_once __DIR__ . "/util.php";

class ImportTcsv2LoDb {

    # Config, injected by the constructor.
    public $mConf;
    public $mDb;
    public $mUtil;
    public $mBackupFile;
    public $mDbLo;
    public $mDebug;

    # Options, from fGetOps().
    public $mOpt;

    # Working state.
    public $mBackupName;
    public $mFieldList;
    public $mFiileH;
    public $mSep;

    # -----------------------------
    public function __construct($pConf, $pOpt = array()) {
        $this->mConf = $pConf;
        $this->mBackupFile = isset($pConf["cgBackupFile"]) ? $pConf["cgBackupFile"] : "";
        $this->mDbLo = isset($pConf["cgDbLo"]) ? $pConf["cgDbLo"] : "";
        $this->mDebug = isset($pConf["cgDebug"]) ? $pConf["cgDebug"] : "";
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

        $tOpt = getopt("cs:hT:");

        return array(
            "help"=>isset($tOpt['h']) or $pArgc < 2,
            "change"=>isset($tOpt['c']),
            "sep"=>isset($tOpt['s']) ? $tOpt['s'] : "c",
            "test"=>isset($tOpt['T']) ? $tOpt['T'] : ""
        );    # ---------->
    } # fGetOps

    # -----------------------------
    public function fRun() {
        # The write section, as run by the wrapper.

        $this->fCreateTable();
        $this->fInsertRec();
        return;    # ---------->
    } # fRun

    # -----------------------------
    public function fCleanUp() {
        echo "\n";
    } # fCleanUp

    # -----------------------------
    public function fValidate() {
        $this->mDb = Db::uFromConf($this->mConf);

        if ($this->mOpt["sep"] == "")
            throw new Exception("\nError: Missing -s option. [import-tcsv-2-lo-db.php:" . __LINE__ . "]");
        switch ($this->mOpt["sep"]) {
        case "c":
            $this->mSep = ",";
            break;
        case "t":
            $this->mSep = "\t";
            break;
        default:
            throw new Exception("\nError: Bad -s. Should be 'c' or 's'. [import-tcsv-2-lo-db.php:" . __LINE__ . "]");
        }

        if (($this->mFiileH = fopen($this->mBackupFile, "r")) == FALSE)
            throw new Exception("Cannot open $this->mBackupFile. [import-tcsv-2-lo-db.php:" . __LINE__ . "]");
    } # fValidate

    # -----------------------------
    public function fCreateTable() {
        $this->mBackupName = "";
        if ($this->mDb->uTableExists($this->mDbLo))
            $this->mBackupName = $this->mDb->uRenameTable($this->mDbLo, __FILE__, __METHOD__, __LINE__);

        #  Get the fields from the first row
        $tTmpList = fgetcsv($this->mFiileH, 15000, $this->mSep);

        $this->mFieldList = array();
        foreach ($tTmpList as $tField) {
            $tField = preg_replace('/\s+/', '_', $tField);
            array_push($this->mFieldList, $tField);
        }

        # Create the table from header record
        $tSql = "CREATE TABLE IF NOT EXISTS $this->mDbLo (";
        foreach ($this->mFieldList as $tField)
            $tSql .= "`$tField` VARCHAR(255),";
        $tSql = rtrim($tSql, ",") . ")";
        $this->mDb->uExecSql("$tSql", __FILE__, __METHOD__, __LINE__);
        $this->mDb->uExecSql("alter table $this->mDbLo add primary key (Identifier)", __FILE__, __METHOD__, __LINE__);
    } # fCreateTable

    # -----------------------------
    public function fInsertRec() {
        # Use this to trim all fields in array to be < 254 char
        $fTrimLen = function($pElement) {
            return substr( $pElement, 0, 254 );     # ---------->
        };

        # Insert data into the table
        $tRec = 0;
        while (($tData = fgetcsv($this->mFiileH, 15000, $this->mSep)) !== FALSE) {
            ++$tRec;
            echo ".";
            $tData = array_map($fTrimLen, $tData);
            $tValueStr = implode('","', $tData);
            $tValueStr = utf8_encode($tValueStr);

            $tSql = "INSERT INTO $this->mDbLo (`" . implode("`, `", $this->mFieldList) . "`) VALUES (\"$tValueStr\")";

            $tCount = $this->mDb->uExec($tSql, __FILE__, __METHOD__, __LINE__);
            if ($tCount != 1) {
                echo "\nRecord: $tRec \n";
                echo $this->mDb->uErrInfo() . "\n";
                if ($this->mDebug) {
                    echo "$tSql \n";
                    # var_dump($tValueStr);
                    throw new Exception("Insert error. [import-tcsv-2-lo-db.php:" . __LINE__ . "]");
                }
            }
        } # while
        echo "\nProcessed: $tRec \n";
    } # fInsertRec

} # ImportTcsv2LoDb

?>
