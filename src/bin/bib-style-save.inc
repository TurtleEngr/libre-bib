<?php

# ========================================
# bib-style-save.inc - class BibStyleSave, the work done by bib-style-save.php.
#
# The wrapper (bib-style-save.php) holds the usage POD and the main section;
# everything else is a method here, so phpunit can drive it.
#
# See style.org for the naming convention.
# ========================================

require_once __DIR__ . "/util.php";

class BibStyleSave {

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
    public $mNoExec;

    # Options, from fGetOps().
    public $mOpt;

    # -----------------------------
    public function __construct($pConf, $pOpt = array()) {
        $this->mConf = $pConf;
        $this->mDbTblBib = isset($pConf["cgDbTblBib"]) ? $pConf["cgDbTblBib"] : "";
        $this->mDebug = isset($pConf["cgDebug"]) ? $pConf["cgDebug"] : "";
        $this->mDirApp = isset($pConf["cgDirApp"]) ? $pConf["cgDirApp"] : "";
        $this->mDirEtc = isset($pConf["cgDirEtc"]) ? $pConf["cgDirEtc"] : "";
        $this->mDirTmp = isset($pConf["cgDirTmp"]) ? $pConf["cgDirTmp"] : "";
        $this->mDocFile = isset($pConf["cgDocFile"]) ? $pConf["cgDocFile"] : "";
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

        $this->mUtil->uUnpackFile($this->mDocFile, "content styles");
        $this->fProcessStyleFile();
        $this->fProcessContentFile();
        return;    # ---------->
    } # fRun

    # -----------------------------
    public function fCleanUp() {
        echo "\n";
        return;    # ---------->
    } # fCleanUp

    # -----------------------------
    public function fValidate() {
        # DB not used
        #$this->mDb = Db::uFromConf($this->mConf);

        if ( ! file_exists("$this->mDocFile"))
            throw new Exception("Missing: cgDocFile $this->mDocFile [bib-style-save.php:" . __LINE__ . "]");

        return;    # ---------->
    } # fValidate

    # -----------------------------
    public function fSaveElement($pXmlFile, $pName, $pOutFile) {
        # Copy one element out of an unpacked odt XML file, and write it to
        # pOutFile. The element is written exactly as it appears in the
        # source, so it can be spliced straight back in by bib-style-update.
        echo "Start processing $pXmlFile [bib-style-save.php:" . __LINE__ . "]\n";

        $tDoc = Xml::uLoadXml($pXmlFile);
        $tNode = Xml::uFindElement($tDoc, $pName);

        if ($tNode === null)
            throw new Exception("\nError: A bibliography has not been added to $this->mDocFile: no $pName in $pXmlFile. [bib-style-save.php:" . __LINE__ . "]");

        $tXml = $tDoc->saveXML($tNode);
        if ($tXml === false)
            throw new Exception("\nError: Could not read $pName from $pXmlFile [bib-style-save.php:" . __LINE__ . "]");

        if (file_put_contents($pOutFile, $tXml . "\n") === false)
            throw new Exception("\nError: Could not write $pOutFile [bib-style-save.php:" . __LINE__ . "]");

        echo "Saved $pName to $pOutFile [bib-style-save.php:" . __LINE__ . "]\n";

        return;    # ---------->
    } # fSaveElement

    # -----------------------------
    public function fProcessStyleFile() {
        # This assumes there is only one "bibliography-configuration" tag
        # in the styles.xml file.

        $this->fSaveElement("$this->mDirTmp/styles.xml", "bibliography-configuration",
            "$this->mDirEtc/bib-style.xml");

        return;    # ---------->
    } # fProcessStyleFile

    # -----------------------------
    public function fProcessContentFile() {
        # This assumes there is only one "bibliography-source" tag in the
        # content.xml file.

        $this->fSaveElement("$this->mDirTmp/content.xml", "bibliography-source",
            "$this->mDirEtc/bib-template.xml");

        return;    # ---------->
    } # fProcessContentFile

} # BibStyleSave

?>
