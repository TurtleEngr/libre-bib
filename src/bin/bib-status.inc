<?php

# ========================================
# bib-status.inc - class BibStatus, the work done by bib-status.php.
#
# The wrapper (bib-status.php) holds the usage POD and the main section;
# everything else is a method here, so phpunit can drive it.
#
# See style.org for the naming convention.
# ========================================

require_once __DIR__ . "/util.php";

class BibStatus {

    # Config, injected by the constructor.
    public $mConf;
    public $mDb;
    public $mUtil;
    public $mDbName;
    public $mDbTblBib;
    public $mDbTblLo;
    public $mDirStatus;
    public $mDocFile;
    public $mLoFile;
    public $mVerbose;

    # Options, from fGetOps().
    public $mOpt;

    # -----------------------------
    public function __construct($pConf, $pOpt = array()) {
        $this->mConf = $pConf;
        $this->mDbName = isset($pConf["cgDbName"]) ? $pConf["cgDbName"] : "";
        $this->mDbTblBib = isset($pConf["cgDbTblBib"]) ? $pConf["cgDbTblBib"] : "";
        $this->mDbTblLo = isset($pConf["cgDbTblLo"]) ? $pConf["cgDbTblLo"] : "";
        $this->mDirStatus = isset($pConf["cgDirStatus"]) ? $pConf["cgDirStatus"] : "";
        $this->mDocFile = isset($pConf["cgDocFile"]) ? $pConf["cgDocFile"] : "";
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

        $this->fStatus();
        $this->fDbStatus();
        return;    # ---------->
    } # fRun

    # -----------------------------
    public function fCleanUp() {
        echo "\n";
        return;    # ---------->
    } # fCleanUp

    # -----------------------------
    public function fValidate() {
        $this->mDb = Db::uFromConf($this->mConf);

        return;    # ---------->
    } # fValidate

    # -----------------------------
    public function fStatus() {
        if ( ! file_exists("$this->mDirStatus/import-lo.date")) {
            echo "Time to run: bib import-lo\n";
        } else {
            if (filemtime($this->mLoFile) > filemtime("$this->mDirStatus/import-lo.date")) {
                echo "$this->mLoFile is newer, run: bib import-lo\n";
            }
        }

        if (file_exists("$this->mDirStatus/import-lo.date") and
            file_exists("$this->mDirStatus)/backup-lo.date")) {
            if (filemtime("$this->mDirStatus/import-lo.date") >
                filemime("$this->mDirStatus)/backup-lo.date")) {
                echo "lo table is newer, maybe backup? Run: bib backup-lo\n";
            }
        }

        if (file_exists("$this->mDirStatus)/bib-update.date")) {
            if (filemtime($this->mDocFile) > filemtime("$this->mDirStatus)/bib-update.date")) {
                echo "$this->mDocFile is new, maybe run: bib bib-new and bib-update\n";
            }
        }

        if (file_exists("$this->mDirStatus/import-lo.date")) {
            if (filemtime("$this->mDirStatus/import-lo.date") > filemtime($this->mDocFile)) {
                echo "lo table is newer than $this->mDocFile, maybe run: bib bib-new and bib-update\n";
            }
        }

        return;    # ---------->
    } # fStatus

    # -----------------------------
    public function fDbStatus() {
        if ( ! $this->mVerbose)
            return 0;

        echo "\nVerbose is on so listing DB information.\n";

        $tStmt = $this->mDb->uExecSql("show databases", __FILE__, __METHOD__, __LINE__);
        $tResult = $tStmt->fetchAll(PDO::FETCH_COLUMN);
        echo "show databases\n\t", implode("\n\t", $tResult) . "\n";
        echo "Your DB is: $this->mDbName\n";

        $tStmt = $this->mDb->uExecSql("show tables", __FILE__, __METHOD__, __LINE__);
        $tResult = $tStmt->fetchAll(PDO::FETCH_COLUMN);
        echo "show tables\n\t" . implode("\n\t", $tResult) . "\n";

        if ( ! $this->mDb->uTableExists($this->mDbTblLo)) {
            echo "$this->mDbTblLo table is not defined. Create with: bib import-lo";
        } else {
            $tStmt = $this->mDb->uExecSql("select column_name from information_schema.columns where table_name = '" . $this->mDbTblLo . "'", __FILE__, __METHOD__, __LINE__);
            $tResult = $tStmt->fetchAll(PDO::FETCH_COLUMN);
            echo "\nfields for table $this->mDbTblLo\n\t" . implode(", ", $tResult) . "\n";

            $tStmt = $this->mDb->uExecSql("select count(*) from $this->mDbTblLo", __FILE__, __METHOD__, __LINE__);
            $tResult = $tStmt->fetchAll(PDO::FETCH_COLUMN);
            echo "\t" . $tResult[0] . " rows\n";
        }

        if ( ! $this->mDb->uTableExists($this->mDbTblBib)) {
            echo "$this->mDbTblBib table is not defined. Create with: bib import-lo";
        } else {
            $tStmt = $this->mDb->uExecSql("select column_name from information_schema.columns where table_name = '" . $this->mDbTblBib . "'", __FILE__, __METHOD__, __LINE__);
            $tResult = $tStmt->fetchAll(PDO::FETCH_COLUMN);
            echo "\nfields for table $this->mDbTblBib\n\t" . implode(", ", $tResult) . "\n";

            $tStmt = $this->mDb->uExecSql("select count(*) from $this->mDbTblBib", __FILE__, __METHOD__, __LINE__);
            $tResult = $tStmt->fetchAll(PDO::FETCH_COLUMN);
            echo "\t" . $tResult[0] . " rows\n";
        }
    } # fDbStatus

} # BibStatus

?>
