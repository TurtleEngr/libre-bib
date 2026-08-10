<?php

# ========================================
# bib-style-update.inc - class BibStyleUpdate, the work done by bib-style-update.php.
#
# The wrapper (bib-style-update.php) holds the usage POD and the main section;
# everything else is a method here, so phpunit can drive it.
#
# See style.org for the naming convention.
# ========================================

require_once __DIR__ . "/util.php";

class BibStyleUpdate {

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
        $this->mUtil->uPackFile($this->mDocFile, "content styles");
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

        if ( ! file_exists("$this->mDirEtc/bib-style.xml"))
            throw new Exception("Missing: $this->mDirEtc/bib-style.xml [bib-style-update.php:" . __LINE__ . "]");

        if ( ! file_exists("$this->mDirEtc/bib-template.xml"))
            throw new Exception("Missing: $this->mDirEtc/bib-template.xml [bib-style-update.php:" . __LINE__ . "]");

        return;    # ---------->
    } # fValidate

    # -----------------------------
    public function fReplaceElement($pXmlFile, $pName, $pNewFile, $pOutFile) {
        # Replace one element in an unpacked odt XML file with the copy
        # saved by bib-style-save.php, and write the result to pOutFile.
        echo "Start processing $pXmlFile [bib-style-update.php:" . __LINE__ . "]\n";

        $tDoc = Xml::uLoadXml($pXmlFile);
        $tNode = Xml::uFindElement($tDoc, $pName);

        if ($tNode === null)
            throw new Exception("\nError: A bibliography has not been added to $this->mDocFile: no $pName in $pXmlFile. [bib-style-update.php:" . __LINE__ . "]");

        $tXml = trim(file_get_contents($pNewFile));

        # The saved copy carries no namespace declarations of its own: it is
        # parsed against this document, which declares them on its root.
        $tFrag = $tDoc->createDocumentFragment();
        if ( ! @$tFrag->appendXML($tXml))
            throw new Exception("\nError: Could not parse $pNewFile [bib-style-update.php:" . __LINE__ . "]");

        $tNode->parentNode->replaceChild($tFrag, $tNode);

        Xml::uSaveXml($tDoc, $pOutFile);
        echo "Updated $pName from $pNewFile [bib-style-update.php:" . __LINE__ . "]\n";

        return;    # ---------->
    } # fReplaceElement

    # -----------------------------
    public function fProcessStyleFile() {
        # This assumes there is only one "bibliography-configuration" tag
        # in the styles.xml file.

        $this->fReplaceElement("$this->mDirTmp/styles.xml", "bibliography-configuration",
            "$this->mDirEtc/bib-style.xml", "$this->mDirTmp/styles.new.xml");

        return;    # ---------->
    } # fProcessStyleFile

    # -----------------------------
    public function fProcessContentFile() {
        # This assumes there is only one "bibliography-source" tag in the
        # content.xml file.

        $this->fReplaceElement("$this->mDirTmp/content.xml", "bibliography-source",
            "$this->mDirEtc/bib-template.xml", "$this->mDirTmp/content.new.xml");

        return;    # ---------->
    } # fProcessContentFile

} # BibStyleUpdate

?>
