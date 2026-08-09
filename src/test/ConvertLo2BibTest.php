<?php

# ========================================
# ConvertLo2BibTest.php - tests for class ConvertLo2Bib, in src/bin/convert-lo-2-bib.inc
#
# cgNoExec is on throughout, so no SQL reaches a DB and mDb never needs
# a PDO handle. Anything that has to talk to a DB or open a real .odt is
# marked incomplete rather than skipped silently.
#
# Run:  src/bin/phpunit src/test
#   or: src/bin/convert-lo-2-bib.php -T all
# ========================================

require_once __DIR__ . "/bootstrap.php";

use PHPUnit\Framework\TestCase;

class ConvertLo2BibTest extends TestCase {

    private $tApp;

    public static function setUpBeforeClass(): void {
        uTestLoadScript("convert-lo-2-bib");
    }

    protected function setUp(): void {
        uTestResetGlobals();
        $this->tApp = new ConvertLo2Bib(uTestConf());
        $this->tApp->mDb = new Db(null, uTestConf());
    } # setUp

    # ----------------------------------------

    public function testMethodsAreDefined() {
        foreach (array("fUsage", "fCleanUp", "fGetOps", "fValidate", "fRun", "fCreateBibTable", "fUpdateRec", "fUpdateBibTable") as $tName)
            $this->assertTrue(method_exists("ConvertLo2Bib", $tName), "Missing method: $tName");
    } # testMethodsAreDefined

    public function testCleanUp() {
        $tApp = $this->tApp;

        $tOut = uTestCapture(function () use ($tApp) { $tApp->fCleanUp(); });

        $this->assertSame("\n", $tOut);
    } # testCleanUp

    # ----------------------------------------
    # fGetOps - no conf and no DB needed, so -h and -T always work

    public function testGetOpsDefaultsToHelpWithNoArgs() {
        $tOpt = ConvertLo2Bib::fGetOps(array("convert-lo-2-bib.php"), 1);

        $this->assertTrue($tOpt["help"]);
    } # testGetOpsDefaultsToHelpWithNoArgs

    public function testGetOpsReturnsEveryOption() {
        $tOpt = ConvertLo2Bib::fGetOps(array("convert-lo-2-bib.php", "-c"), 2);

        foreach (array("help", "change", "test") as $tKey)
            $this->assertArrayHasKey($tKey, $tOpt);
    } # testGetOpsReturnsEveryOption

    public function testGetOpsTestDefaultsToEmpty() {
        $tOpt = ConvertLo2Bib::fGetOps(array("convert-lo-2-bib.php", "-c"), 2);

        $this->assertSame("", $tOpt["test"]);
    } # testGetOpsTestDefaultsToEmpty

    # ----------------------------------------
    # The constructor takes conf; nothing reads a global any more.

    public function testConstructorTakesConf() {
        $tConf = uTestConf();
        $tApp = new ConvertLo2Bib($tConf);

        $this->assertSame($tConf["cgDbTblBib"], $tApp->mDbTblBib);
        $this->assertSame($tConf["cgDbTblLo"], $tApp->mDbTblLo);
    } # testConstructorTakesConf

    public function testConstructorMakesAUtil() {
        $this->assertInstanceOf("Util", $this->tApp->mUtil);
    } # testConstructorMakesAUtil

    public function testConstructorKeepsTheOptions() {
        $tApp = new ConvertLo2Bib(uTestConf(), array("test"=>"all"));

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

    public function testCreateBibTableNeedsADb() {
        $this->markTestIncomplete("fCreateBibTable needs a live DB connection");
    } # testCreateBibTableNeedsADb

} # ConvertLo2BibTest
