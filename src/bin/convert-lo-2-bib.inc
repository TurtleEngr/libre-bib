|<?php

# ========================================
# convert-lo-2-bib.inc - class ConvertLo2Bib, the work done by convert-lo-2-bib.php.
#
# The wrapper (convert-lo-2-bib.php) holds the usage POD and the main section;
# everything else is a method here, so phpunit can drive it.
#
# See style.org for the naming convention.
# ========================================

require_once __DIR__ . "/util.php";

class ConvertLo2Bib {

    # Config, injected by the constructor.
    public $mConf;
    public $mDb;
    public $mUtil;
    public $mDbTblBib;
    public $mDbTblLo;
    public $mDebug;
    public $mNoExec;

    # Options, from fGetOps().
    public $mOpt;

    # -----------------------------
    public function __construct($pConf, $pOpt = array()) {
        $this->mConf = $pConf;
        $this->mDbTblBib = isset($pConf["cgDbTblBib"]) ? $pConf["cgDbTblBib"] : "";
        $this->mDbTblLo = isset($pConf["cgDbTblLo"]) ? $pConf["cgDbTblLo"] : "";
        $this->mDebug = isset($pConf["cgDebug"]) ? $pConf["cgDebug"] : "";
        $this->mNoExec = isset($pConf["cgNoExec"]) ? $pConf["cgNoExec"] : "";
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

        $this->fCreateBibTable();
        $this->fUpdateBibTable();
        return;    # ---------->
    } # fRun

    # -----------------------------
    public function fCleanUp() {
        echo "\n";
    } # fCleanUp

    # -----------------------------
    public function fValidate() {
        $this->mDb = Db::uFromConf($this->mConf);

        if ( ! $this->mDb->uTableExists($this->mDbTblLo))
            throw new Exception("\nError: Missing $this->mDbTblLo Table. [convert-lo-2-bib.php:" . __LINE__ . "]");
    } # fValidate

    # -----------------------------
    public function fCreateBibTable() {
        if ($this->mDb->uTableExists($this->mDbTblBib))
            $this->mDb->uRenameTable($this->mDbTblBib, __FILE__, __METHOD__, __LINE__);

        if ($this->mDb->uTableExists($this->mDbTblBib))
            $this->mDb->uExecSql("drop table $this->mDbTblBib", __FILE__, __METHOD__, __LINE__);

        $this->mDb->uExecSql("CREATE TABLE $this->mDbTblBib SELECT * FROM $this->mDbTblLo", __FILE__, __METHOD__, __LINE__);
        $this->mDb->uExecSql("alter table $this->mDbTblBib add primary key (Identifier)", __FILE__, __METHOD__, __LINE__);
    } # fCreateBibTable

    # -----------------------------
    public function fUpdateRec($pRec) {
        $tSql = "update $this->mDbTblBib set";
        foreach (array_keys($pRec) as $tCol) {
            if ($pRec[$tCol] == '')
                continue;
            switch ($tCol) {
            case "Identifier":
            case "Address":
            case "Annote":
            case "Edition":
            case "Note":
            case "Title":
            case "Type":
            case "Custom2":
            case "Custom3":
                # These are not changed
                continue 2;
            }
            $tSql .= ' ' . $tCol . ' = "' . $pRec[$tCol] . '",';
        }
        $tSql = preg_replace('/",$/', '"', $tSql);
        $tSql .= ' where Identifier = "' . $pRec['Identifier'] . '"';

        $this->mDb->uExecSql($tSql, __FILE__, __METHOD__, __LINE__);
    } # fUpdateRec

    # -----------------------------
    public function fUpdateBibTable() {
        $tAltList =  array();

        # Get col to be updated
        $tSql = "select * from $this->mDbTblBib";
        $tRecH = $this->mDb->uPrepare($tSql, __FILE__, __METHOD__, __LINE__);
        $tRecH->execute();

        $tCount = 0;
        while ($tRec = $tRecH->fetch(PDO::FETCH_ASSOC)) {
            ++$tCount;
            if ($tCount % 50 == 0)
                echo ".";

            # Put a ', ' before each non-blank column, but process
            # certain col differently.
            foreach (array_keys($tRec) as $tCol) {
                if ($tRec[$tCol] == '')
                    continue;
                switch ($tCol) {
                case "Identifier":
                case "Address":
                case "Annote":
                case "Edition":
                case "Note":
                case "Title":
                case "Type":
                case "Custom1":
                case "Custom2":
                case "Custom3":
                    # These are not changed
                    break;
                case "Booktitle":
                    if ($tRec['Title'] != '')
                        $tRec[$tCol] .= ': ' . $tRec['Title'];
                    if ($tRec['Edition'] != '')
                        $tRec[$tCol] .= ' (' . $tRec['Edition'] . ' ed.)';
                    $tRec[$tCol] .= '.';
                    break;
                case "Author":
                    if ($tRec['Custom2'] != '')
                        $tRec[$tCol] .= ', and ' . $tRec['Custom2'];
                    $tRec[$tCol] = ' ' . $tRec[$tCol] . '.';
                    break;
                case "Publisher":
                    if ($tRec[$tCol] != '')
                        if ($tRec['Address'] != '')
                            $tRec[$tCol] = ' ' . $tRec['Address'] . ': ' . $tRec[$tCol] . '.';
                        else
                            $tRec[$tCol] = ' ' . $tRec[$tCol] . '.';
                        break;
                case "ISBN":
                    $tRec[$tCol] = ' ISBN:' . $tRec[$tCol] . '.';
                    break;
                case "URL":
                    # Merge URL and Custom1 into Custom1. LO rewrites
                    # text:url as a URI (spaces become %20), but it
                    # leaves text:custom1 alone.
                    $tUrl = ' URL:' . $tRec[$tCol];
                    if ($tRec['Custom1'] != '') {
                        # Use only the first entry (space separator)
                        $tAltList = explode(' ', trim($tRec['Custom1']));
                        $tUrl .= ' Alt:' . $tAltList[0];
                    }
                    $tRec['Custom1'] = $tUrl;
                    break;
                case "Custom4":
                    $tRec[$tCol] = ' Seen: ' . $tRec[$tCol] . '.';
                    break;
                default:
                    if ($tRec[$tCol] != '')
                        $tRec[$tCol] = ' ' . $tRec[$tCol] . '.';
                }
            }
            if ($tRec['ISBN'] == '' and $tRec['Custom3'] != '')
                $tRec['ISBN'] = ', ASIN:' . $tRec['Custom3'];
            $this->fUpdateRec($tRec);
        } # while
        echo "\nProcessed: $tCount [convert-lo-2-bib.php:" . __LINE__ . "]\n";
    } # fUpdateBibTable

} # ConvertLo2Bib

?>
