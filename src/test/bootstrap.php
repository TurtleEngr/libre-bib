<?php

# ========================================
# bootstrap.php - common setup for all libre-bib phpunit tests.
#
# Every *Test.php file begins with:
#
#     require_once __DIR__ . "/bootstrap.php";
#
# so the tests work no matter which dir phpunit is run from.
#
# Naming (see style.org):
#     cg*, g*  - globals
#     p*       - function parameters
#     t*       - local variables
#     u*       - utility functions
# ========================================

global $cgDirTest;
global $cgDirBin;
global $cgDirSample;
global $cgDirExample;
global $cgDirTmpTest;

$cgDirTest = __DIR__;
$cgDirBin = dirname(__DIR__) . "/bin";
$cgDirSample = __DIR__ . "/sample";
$cgDirExample = dirname(__DIR__) . "/doc/example";
$cgDirTmpTest = sys_get_temp_dir() . "/libre-bib-test";

if ( ! is_dir($cgDirTmpTest))
    mkdir($cgDirTmpTest, 0755, true);

require_once "$cgDirBin/util.php";

# --------------------
function uTestLoadScript($pName) {
    # Make a CLI script's class available to phpunit, without running
    # the script's main section. Every script in bin/ is now a wrapper
    # (bin/$pName.php) plus a class (bin/$pName.inc); only the .inc is
    # loaded here.
    #
    # Returns the path of the file that was included.

    global $cgDirBin;

    $tInc = "$cgDirBin/$pName.inc";
    if ( ! file_exists($tInc))
        throw new Exception("Missing: $tInc [bootstrap.php:" . __LINE__ . "]");

    require_once "$tInc";
    return $tInc;    # ---------->
} # uTestLoadScript

# --------------------
function uTestResetGlobals() {
    # Set the globals that util.php and the script functions expect.
    # cgNoExec is always on, so the tests never send SQL to a DB and
    # never need $gDb.
    #
    # Call this from setUp() in every test class.

    global $cgDirTmpTest;

    $GLOBALS["cgDebug"] = 0;
    $GLOBALS["cgNoExec"] = 1;
    $GLOBALS["cgVerbose"] = 0;

    $GLOBALS["cgDbTblBib"] = "bib";
    $GLOBALS["cgDbHost"] = "127.0.0.1";
    $GLOBALS["cgDbLib"] = "lib";
    $GLOBALS["cgDbTblLo"] = "lo";
    $GLOBALS["cgDbName"] = "biblio_test";
    $GLOBALS["cgDbPassCache"] = "$cgDirTmpTest/no-such-pass-cache";
    $GLOBALS["cgDbPortLocal"] = "3306";
    $GLOBALS["cgDbUser"] = "test";

    $GLOBALS["cgDirTmp"] = $cgDirTmpTest;
    $GLOBALS["cgLoFile"] = "";
    $GLOBALS["cgDocFile"] = "";

    # A Db with no PDO handle: with cgNoExec on, no method needs one.
    $GLOBALS["gConf"] = uTestConf();
    $GLOBALS["gDb"] = new Db(null, $GLOBALS["gConf"]);
    $GLOBALS["gUtil"] = new Util($GLOBALS["gConf"]);
    $GLOBALS["gFileH"] = null;
    $GLOBALS["gPassword"] = "";
    $GLOBALS["gNumLine"] = 0;
    $GLOBALS["gNumRec"] = 0;
    $GLOBALS["gBackupName"] = "";
} # uTestResetGlobals

# --------------------
function uTestConf() {
    # The conf array that the classes take in their constructors. Same
    # values as uTestResetGlobals() puts in the globals.

    global $cgDirTmpTest;

    return array(
        "cgBin"=>dirname(__DIR__) . "/bin",
        "cgDirApp"=>dirname(__DIR__),
        "cgDebug"=>0,
        "cgNoExec"=>1,
        "cgVerbose"=>0,
        "cgDirEtc"=>dirname(__DIR__) . "/etc",
        "cgDirTmp"=>$cgDirTmpTest,
        "cgDbHost"=>"127.0.0.1",
        "cgDbName"=>"biblio_test",
        "cgDbPassCache"=>"$cgDirTmpTest/no-such-pass-cache",
        "cgDbPortLocal"=>"3306",
        "cgDbTblBib"=>"bib",
        "cgDbTblLo"=>"lo",
        "cgDbUser"=>"test",
        "cgLoFile"=>"",
        "cgDocFile"=>"",
        "cgBackupFile"=>""
    );    # ---------->
} # uTestConf

# --------------------
function uTestCapture($pFunc) {
    # Run $pFunc (a closure) and return everything it echoed.
    # Several of the functions report through stdout rather than a
    # return value.

    ob_start();
    try {
        $pFunc();
    } finally {
        $tOut = ob_get_clean();
    }
    return $tOut;    # ---------->
} # uTestCapture

# ========================================
# A Db that answers one canned row, so the code paths that read a
# record can be tested without a live DB. uPrepare() is the only
# method the lookups use.

class uTestStmt {
    public $mRow;

    public function __construct($pRow) {
        $this->mRow = $pRow;
    } # __construct

    public function execute($pArgs = array()) {
        return true;    # ---------->
    } # execute

    public function fetch($pMode = null) {
        return $this->mRow;    # ---------->
    } # fetch
} # uTestStmt

class uTestDb extends Db {
    public $mRow;

    public function __construct($pConf, $pRow) {
        parent::__construct(null, $pConf);
        $this->mRow = $pRow;
    } # __construct

    public function uPrepare($pSql, $pFile, $pFunc, $pLine) {
        return new uTestStmt($this->mRow);    # ---------->
    } # uPrepare
} # uTestDb
