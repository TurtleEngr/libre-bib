<?php

# ========================================
# export-lo-2-tcsv.inc - class ExportLo2Tcsv, the work done by export-lo-2-tcsv.php.
#
# The wrapper (export-lo-2-tcsv.php) holds the usage POD and the main section;
# everything else is a method here, so phpunit can drive it.
#
# See style.org for the naming convention.
# ========================================

require_once __DIR__ . "/util.php";

class ExportLo2Tcsv {

    # Config, injected by the constructor.
    public $mConf;
    public $mDb;
    public $mUtil;
    public $mBackupFile;
    public $mDbTblLo;
    public $mDebug;
    public $mDirApp;
    public $mNoExec;
    public $mVerbose;

    # Options, from fGetOps().
    public $mOpt;

    # Working state.
    public $mSep;

    # -----------------------------
    public function __construct($pConf, $pOpt = array()) {
        $this->mConf = $pConf;
        $this->mBackupFile = isset($pConf["cgBackupFile"]) ? $pConf["cgBackupFile"] : "";
        $this->mDbTblLo = isset($pConf["cgDbTblLo"]) ? $pConf["cgDbTblLo"] : "";
        $this->mDebug = isset($pConf["cgDebug"]) ? $pConf["cgDebug"] : "";
        $this->mDirApp = isset($pConf["cgDirApp"]) ? $pConf["cgDirApp"] : "";
        $this->mNoExec = isset($pConf["cgNoExec"]) ? $pConf["cgNoExec"] : "";
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

        $this->fExportTable();
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
            throw new Exception("\nError: Missing -s option. [export-lo-2-tcsv.php:" . __LINE__ . "]");
        switch ($this->mOpt["sep"]) {
        case "c":
            $this->mSep = ",";
            break;
        case "t":
            $this->mSep = "\t";
            break;
        default:
            throw new Exception("\nError: Bad -s. Should be 'c' or 's'. [export-lo-2-tcsv.php:" . __LINE__ . "]");
        }

        if ( ! $this->mDb->uTableExists($this->mDbTblLo))
            throw new Exception("\nError: -t Table $this->mDbTblLo does not exist. [export-lo-2-tcsv.php:" . __LINE__ . "]");
    } # fValidate

    # -----------------------------
    public function fExportTable() {
        # Get header "official" header from src/biblio.dbf
        shell_exec("/bin/bash -c 'head -n 1 $this->mDirApp/etc/lo-schema.csv >$this->mBackupFile'");

        if (($tFileH = fopen($this->mBackupFile, "a")) == FALSE)
            throw new Exception("Cannot write to $this->mBackupFile. [export-lo-2-tcsv.php:" . __LINE__ . "]");

        # Get all columns
        $tSql = "select * from bib";
        $tRecH = $this->mDb->uPrepare($tSql, __FILE__, __METHOD__, __LINE__);
        $tRecH->execute();

        # Get each record and output the csv line
        $tCount = 0;
        while ($tRec = $tRecH->fetch(PDO::FETCH_ASSOC)) {
            echo ".";
            ++$tCount;
            if ( ! fputcsv($tFileH, array_values($tRec), $this->mSep))
                throw new Exception("Error writing: record $tCount. [export-lo-2-tcsv.php:" . __LINE__ . "]");
        } # while
        echo "\nProcessed: $tCount \n";
        fclose($tFileH);
    } # fExportTable

} # ExportLo2Tcsv

?>
