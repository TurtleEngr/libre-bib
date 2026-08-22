<?php

# ========================================
# bib-ref-update.inc - class BibRefUpdate, the work done by bib-ref-update.php.
#
# The wrapper (bib-ref-update.php) holds the usage POD and the main section;
# everything else is a method here, so phpunit can drive it.
#
# See style.org for the naming convention.
# ========================================

require_once __DIR__ . "/util.php";

class BibRefUpdate {

    # Config, injected by the constructor.
    public $mConf;
    public $mDb;
    public $mUtil;
    public $mDbTblBib;
    public $mDebug;
    public $mDirApp;
    public $mDirEtc;
    public $mDirTmp;
    public $mDocFile;

    # Options, from fGetOps().
    public $mOpt;

    # Working state.
    public $mBibCache;
    public $mNumRef;

    # -----------------------------
    public function __construct($pConf, $pOpt = array()) {
        $this->mConf = $pConf;
        $this->mDbTblBib = isset($pConf["cgDbTblBib"]) ? $pConf["cgDbTblBib"] : "";
        $this->mDebug = isset($pConf["cgDebug"]) ? $pConf["cgDebug"] : "";
        $this->mDirApp = isset($pConf["cgDirApp"]) ? $pConf["cgDirApp"] : "";
        $this->mDirEtc = isset($pConf["cgDirEtc"]) ? $pConf["cgDirEtc"] : "";
        $this->mDirTmp = isset($pConf["cgDirTmp"]) ? $pConf["cgDirTmp"] : "";
        $this->mDocFile = isset($pConf["cgDocFile"]) ? $pConf["cgDocFile"] : "";
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

        $this->mUtil->uUnpackFile($this->mDocFile, "content");
        $this->fProcessFile();
        $this->mUtil->uPackFile($this->mDocFile, "content");
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

        if ( ! $this->mDb->uTableExists($this->mDbTblBib))
            throw new Exception("\nError: Table $this->mDbTblBib does not exist. [bib-ref-update.php:" . __LINE__ . "]");

        return;    # ---------->
    } # fValidate

    # -----------------------------
    public function fBibLookup($pId) {
        # Bib cols that are not put in the bibliography-mark.
        $cSkipCol = array("Identifier", "Type", "Annote", "Note",
            "URL", "Custom2", "Custom3", "Custom5");

        if ( ! isset($this->mBibCache))
            $this->mBibCache = array();

        # One lookup per Id, however many times the Id is cited. This also
        # keeps the "not in DB" warning down to one per Id.
        if (isset($this->mBibCache[$pId]))
            return $this->mBibCache[$pId];    # ---------->

        $this->mBibCache[$pId] = false;

        $tSql = "select * from $this->mDbTblBib where Identifier = ?";
        $tRecH = $this->mDb->uPrepare($tSql, __FILE__, __METHOD__, __LINE__);
        $tRecH->execute(array($pId));
        $tRow = $tRecH->fetch(PDO::FETCH_ASSOC);

        if ( ! $tRow) {
            echo "\nWarning: $pId is not in DB. [bib-ref-update.php:" . __LINE__ . "]\n";
            return false;    # ---------->
        }

        # 'data' is an XML-attribute-name => value map, so the DOM can set
        # the attributes itself and do its own escaping.
        $tRef = array(
            'id'=>$pId,
            'type'=>Map::uBibType2Xml($tRow['Type']),
            'data'=>array()
        );

        foreach ($tRow as $tCol => $tValue) {
            if ("$tValue" == "")
                continue;
            if (in_array($tCol, $cSkipCol))
                continue;
            $tRef['data'][Map::uBib2Xml($tCol)] = $tValue;
        }

        if ($this->mDebug) print_r($tRef);

        $this->mBibCache[$pId] = $tRef;
        return $tRef;    # ---------->
    } # fBibLookup

    # -----------------------------
    public function fUpdateMark($pMark) {
        # Refresh one <text:bibliography-mark> from the bib table. The
        # identifier is what ties the mark to the DB, so it is the one
        # attribute that is kept; everything else is rewritten.

        $tId = $pMark->getAttribute("text:identifier");
        if ($tId == "") {
            echo "\nWarning: No Id found for biblio entry. [bib-ref-update.php:" . __LINE__ . "]\n";
            return;    # ---------->
        }

        $tRef = $this->fBibLookup($tId);
        if ( ! $tRef)
            return;    # ---------->

        if ($this->mDebug) echo "Updating: $tId\n";

        # Collect the names first: removing while walking ->attributes skips
        # entries, because the map is live.
        $tOldName = array();
        foreach ($pMark->attributes as $tAttr)
            $tOldName[] = $tAttr->nodeName;

        foreach ($tOldName as $tName) {
            if ($tName != "text:identifier")
                $pMark->removeAttribute($tName);
        }

        $pMark->setAttribute("text:bibliography-type", $tRef['type']);
        foreach ($tRef['data'] as $tName => $tValue)
            $pMark->setAttribute("text:" . $tName, $tValue);

        ++$this->mNumRef;
        if ($this->mNumRef % 10 == 0)
            echo '.';

        return;    # ---------->
    } # fUpdateMark

    # -----------------------------
    public function fProcessFile() {
        echo "Start processing [bib-ref-update.php:" . __LINE__ . "]\n";

        $tDoc = Xml::uLoadXml("$this->mDirTmp/content.xml");
        $tMarkList = Xml::uFindElementList($tDoc, "bibliography-mark");

        $this->mNumRef = 0;
        foreach ($tMarkList as $tMark)
            $this->fUpdateMark($tMark);

        echo "\nFound " . count($tMarkList) . " bibliography marks. [bib-ref-update.php:" . __LINE__ . "]\n";
        echo "Updated $this->mNumRef references. [bib-ref-update.php:" . __LINE__ . "]\n";

        Xml::uSaveXml($tDoc, "$this->mDirTmp/content.new.xml");

        return;    # ---------->
    } # fProcessFile

} # BibRefUpdate

?>
