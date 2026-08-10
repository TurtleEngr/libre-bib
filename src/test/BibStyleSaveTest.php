<?php

# ========================================
# BibStyleSaveTest.php - tests for class BibStyleSave, in src/bin/bib-style-save.inc
#
# cgNoExec is on throughout, so no SQL reaches a DB and mDb never needs
# a PDO handle. Anything that has to talk to a DB or open a real .odt is
# marked incomplete rather than skipped silently.
#
# Run:  src/bin/phpunit src/test
#   or: src/bin/bib-style-save.php -T all
# ========================================

require_once __DIR__ . "/bootstrap.php";

use PHPUnit\Framework\TestCase;

class BibStyleSaveTest extends TestCase {

    private $tApp;

    public static function setUpBeforeClass(): void {
        uTestLoadScript("bib-style-save");
    }

    protected function setUp(): void {
        uTestResetGlobals();
        $this->tApp = new BibStyleSave(uTestConf());
        $this->tApp->mDb = new Db(null, uTestConf());
    } # setUp

    # ----------------------------------------

    public function testMethodsAreDefined() {
        foreach (array("fUsage", "fCleanUp", "fGetOps", "fValidate", "fRun", "fSaveElement", "fProcessStyleFile", "fProcessContentFile") as $tName)
            $this->assertTrue(method_exists("BibStyleSave", $tName), "Missing method: $tName");
    } # testMethodsAreDefined

    public function testCleanUp() {
        $tApp = $this->tApp;

        $tOut = uTestCapture(function () use ($tApp) { $tApp->fCleanUp(); });

        $this->assertSame("\n", $tOut);
    } # testCleanUp

    # ----------------------------------------
    # fGetOps - no conf and no DB needed, so -h and -T always work

    public function testGetOpsDefaultsToHelpWithNoArgs() {
        $tOpt = BibStyleSave::fGetOps(array("bib-style-save.php"), 1);

        $this->assertTrue($tOpt["help"]);
    } # testGetOpsDefaultsToHelpWithNoArgs

    public function testGetOpsReturnsEveryOption() {
        $tOpt = BibStyleSave::fGetOps(array("bib-style-save.php", "-c"), 2);

        foreach (array("help", "change", "test") as $tKey)
            $this->assertArrayHasKey($tKey, $tOpt);
    } # testGetOpsReturnsEveryOption

    public function testGetOpsTestDefaultsToEmpty() {
        $tOpt = BibStyleSave::fGetOps(array("bib-style-save.php", "-c"), 2);

        $this->assertSame("", $tOpt["test"]);
    } # testGetOpsTestDefaultsToEmpty

    # ----------------------------------------
    # The constructor takes conf; nothing reads a global any more.

    public function testConstructorTakesConf() {
        $tConf = uTestConf();
        $tApp = new BibStyleSave($tConf);

        $this->assertSame($tConf["cgDirEtc"], $tApp->mDirEtc);
        $this->assertSame($tConf["cgDirTmp"], $tApp->mDirTmp);
        $this->assertSame($tConf["cgDocFile"], $tApp->mDocFile);
    } # testConstructorTakesConf

    public function testConstructorMakesAUtil() {
        $this->assertInstanceOf("Util", $this->tApp->mUtil);
    } # testConstructorMakesAUtil

    public function testConstructorKeepsTheOptions() {
        $tApp = new BibStyleSave(uTestConf(), array("test"=>"all"));

        $this->assertSame("all", $tApp->mOpt["test"]);
    } # testConstructorKeepsTheOptions

    # ----------------------------------------
    # Needs a DB

    public function testValidateNeedsSetup() {
        # fValidate connects first, which needs the password cache made
        # by: bib connect
        $this->expectException(Exception::class);

        $this->tApp->fValidate();
    } # testValidateNeedsSetup

    public function testProcessStyleFileNeedsAnUnpackedOdt() {
        $this->markTestIncomplete("fProcessStyleFile needs an unpacked styles.xml");
    } # testProcessStyleFileNeedsAnUnpackedOdt

} # BibStyleSaveTest
