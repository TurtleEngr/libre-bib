<?php

# ========================================
# bootstrap.php - common setup for all libre-bib2 phpunit tests.
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
global $cgDirTmpTest;

$cgDirTest = __DIR__;
$cgDirBin = dirname(__DIR__) . "/bin";
$cgDirSample = __DIR__ . "/sample";
$cgDirTmpTest = sys_get_temp_dir() . "/libre-bib2-test";

if ( ! is_dir($cgDirTmpTest))
    mkdir($cgDirTmpTest, 0755, true);

require_once "$cgDirBin/util.php";

# --------------------
function uTestLoadScript($pName) {
    # Make the functions in a CLI script available to phpunit, without
    # running the script's main section.
    #
    # If bin/$pName.inc exists (the "wrapper" convention in style.org)
    # it is simply included.
    #
    # Until a script has been split into wrapper + .inc, the functions
    # are copied out of bin/$pName.php: everything above the first
    # "# ******" separator line, which is where the main section starts.
    # Nothing in bin/ is modified.
    #
    # Returns the path of the file that was included.

    global $cgDirBin;
    global $cgDirTmpTest;

    $tInc = "$cgDirBin/$pName.inc";
    if (file_exists($tInc)) {
        require_once "$tInc";
        return $tInc;    # ---------->
    }

    $tScript = "$cgDirBin/$pName.php";
    if ( ! file_exists($tScript))
        throw new Exception("Missing: $tScript [bootstrap.php:" . __LINE__ . "]");

    $tText = file_get_contents($tScript);

    # Drop "#!/usr/bin/env php" so the include does not echo it.
    $tText = preg_replace('/\A#![^\n]*\n/', "", $tText, 1);

    # Cut off the main section.
    $tPart = preg_split('/^#\s*\*{10,}.*$/m', $tText, 2);
    if (count($tPart) < 2)
        throw new Exception("No '# ******' separator found in $tScript [bootstrap.php:" . __LINE__ . "]");

    $tCopy = "$cgDirTmpTest/$pName.inc";
    file_put_contents($tCopy, $tPart[0]);
    require_once $tCopy;
    return $tCopy;    # ---------->
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
    $GLOBALS["cgUseLib"] = 0;
    $GLOBALS["cgUseRemote"] = 0;

    $GLOBALS["cgDbTblBib"] = "bib";
    $GLOBALS["cgDbHost"] = "127.0.0.1";
    $GLOBALS["cgDbLib"] = "lib";
    $GLOBALS["cgDbTblLo"] = "lo";
    $GLOBALS["cgDbName"] = "biblio_test";
    $GLOBALS["cgDbPassCache"] = "$cgDirTmpTest/no-such-pass-cache";
    $GLOBALS["cgDbPortLocal"] = "3306";
    $GLOBALS["cgDbPortRemote"] = "3308";
    $GLOBALS["cgDbUser"] = "test";

    $GLOBALS["cgDirTmp"] = $cgDirTmpTest;
    $GLOBALS["cgLoFile"] = "";
    $GLOBALS["cgDocFile"] = "";

    $GLOBALS["gDb"] = null;
    $GLOBALS["gFileH"] = null;
    $GLOBALS["gPassword"] = "";
    $GLOBALS["gNumLine"] = 0;
    $GLOBALS["gNumRec"] = 0;
    $GLOBALS["gBackupName"] = "";
} # uTestResetGlobals

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
