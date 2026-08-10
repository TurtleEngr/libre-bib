<?php

# ========================================
# ExportLo2TxtTest.php - tests for class ExportLo2Txt, in src/bin/export-lo-2-txt.inc
#
# cgNoExec is on throughout, so no SQL reaches a DB and mDb never needs
# a PDO handle. Anything that has to talk to a DB or open a real .odt is
# marked incomplete rather than skipped silently.
#
# Run:  src/bin/phpunit src/test
#   or: src/bin/export-lo-2-txt.php -T all
# ========================================

require_once __DIR__ . "/bootstrap.php";

use PHPUnit\Framework\TestCase;

class ExportLo2TxtTest extends TestCase {

    private $tApp;

    public static function setUpBeforeClass(): void {
        uTestLoadScript("export-lo-2-txt");
    }

    protected function setUp(): void {
        uTestResetGlobals();
        $this->tApp = new ExportLo2Txt(uTestConf());
        $this->tApp->mDb = new Db(null, uTestConf());
    } # setUp

    # ----------------------------------------

    public function testMethodsAreDefined() {
        foreach (array("fUsage", "fCleanUp", "fGetOps", "fValidate", "fRun", "fExportTable") as $tName)
            $this->assertTrue(method_exists("ExportLo2Txt", $tName), "Missing method: $tName");
    } # testMethodsAreDefined

    public function testCleanUp() {
        $tApp = $this->tApp;

        $tOut = uTestCapture(function () use ($tApp) { $tApp->fCleanUp(); });

        $this->assertSame("\n", $tOut);
    } # testCleanUp

    # ----------------------------------------
    # fGetOps - no conf and no DB needed, so -h and -T always work

    public function testGetOpsDefaultsToHelpWithNoArgs() {
        $tOpt = ExportLo2Txt::fGetOps(array("export-lo-2-txt.php"), 1);

        $this->assertTrue($tOpt["help"]);
    } # testGetOpsDefaultsToHelpWithNoArgs

    public function testGetOpsReturnsEveryOption() {
        $tOpt = ExportLo2Txt::fGetOps(array("export-lo-2-txt.php", "-c"), 2);

        foreach (array("help", "change", "test") as $tKey)
            $this->assertArrayHasKey($tKey, $tOpt);
    } # testGetOpsReturnsEveryOption

    public function testGetOpsTestDefaultsToEmpty() {
        $tOpt = ExportLo2Txt::fGetOps(array("export-lo-2-txt.php", "-c"), 2);

        $this->assertSame("", $tOpt["test"]);
    } # testGetOpsTestDefaultsToEmpty

    # ----------------------------------------
    # The constructor takes conf; nothing reads a global any more.

    public function testConstructorTakesConf() {
        $tConf = uTestConf();
        $tApp = new ExportLo2Txt($tConf);

        $this->assertSame($tConf["cgDbTblLo"], $tApp->mDbTblLo);
        $this->assertSame($tConf["cgLoFile"], $tApp->mLoFile);
    } # testConstructorTakesConf

    public function testConstructorMakesAUtil() {
        $this->assertInstanceOf("Util", $this->tApp->mUtil);
    } # testConstructorMakesAUtil

    public function testConstructorKeepsTheOptions() {
        $tApp = new ExportLo2Txt(uTestConf(), array("test"=>"all"));

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

    public function testExportTableNeedsADb() {
        $this->markTestIncomplete("fExportTable needs a live DB connection");
    } # testExportTableNeedsADb

} # ExportLo2TxtTest
