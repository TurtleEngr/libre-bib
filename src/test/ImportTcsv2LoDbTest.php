<?php

# ========================================
# ImportTcsv2LoDbTest.php - tests for class ImportTcsv2LoDb, in src/bin/import-tcsv-2-lo-db.inc
#
# cgNoExec is on throughout, so no SQL reaches a DB and mDb never needs
# a PDO handle. Anything that has to talk to a DB or open a real .odt is
# marked incomplete rather than skipped silently.
#
# Run:  src/bin/phpunit src/test
#   or: src/bin/import-tcsv-2-lo-db.php -T all
# ========================================

require_once __DIR__ . "/bootstrap.php";

use PHPUnit\Framework\TestCase;

class ImportTcsv2LoDbTest extends TestCase {

    private $tApp;

    public static function setUpBeforeClass(): void {
        uTestLoadScript("import-tcsv-2-lo-db");
    }

    protected function setUp(): void {
        uTestResetGlobals();
        $this->tApp = new ImportTcsv2LoDb(uTestConf());
        $this->tApp->mDb = new Db(null, uTestConf());
    } # setUp

    # ----------------------------------------

    public function testMethodsAreDefined() {
        foreach (array("fUsage", "fCleanUp", "fGetOps", "fValidate", "fRun", "fCreateTable", "fInsertRec") as $tName)
            $this->assertTrue(method_exists("ImportTcsv2LoDb", $tName), "Missing method: $tName");
    } # testMethodsAreDefined

    public function testCleanUp() {
        $tApp = $this->tApp;

        $tOut = uTestCapture(function () use ($tApp) { $tApp->fCleanUp(); });

        $this->assertSame("\n", $tOut);
    } # testCleanUp

    # ----------------------------------------
    # fGetOps - no conf and no DB needed, so -h and -T always work

    public function testGetOpsDefaultsToHelpWithNoArgs() {
        $tOpt = ImportTcsv2LoDb::fGetOps(array("import-tcsv-2-lo-db.php"), 1);

        $this->assertTrue($tOpt["help"]);
    } # testGetOpsDefaultsToHelpWithNoArgs

    public function testGetOpsReturnsEveryOption() {
        $tOpt = ImportTcsv2LoDb::fGetOps(array("import-tcsv-2-lo-db.php", "-c"), 2);

        foreach (array("help", "change", "sep", "test") as $tKey)
            $this->assertArrayHasKey($tKey, $tOpt);
    } # testGetOpsReturnsEveryOption

    public function testGetOpsTestDefaultsToEmpty() {
        $tOpt = ImportTcsv2LoDb::fGetOps(array("import-tcsv-2-lo-db.php", "-c"), 2);

        $this->assertSame("", $tOpt["test"]);
    } # testGetOpsTestDefaultsToEmpty

    # ----------------------------------------
    # The constructor takes conf; nothing reads a global any more.

    public function testConstructorTakesConf() {
        $tConf = uTestConf();
        $tApp = new ImportTcsv2LoDb($tConf);

        $this->assertSame($tConf["cgBackupFile"], $tApp->mBackupFile);
    } # testConstructorTakesConf

    public function testConstructorMakesAUtil() {
        $this->assertInstanceOf("Util", $this->tApp->mUtil);
    } # testConstructorMakesAUtil

    public function testConstructorKeepsTheOptions() {
        $tApp = new ImportTcsv2LoDb(uTestConf(), array("test"=>"all"));

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

    public function testCreateTableNeedsADb() {
        $this->markTestIncomplete("fCreateTable needs a live DB connection");
    } # testCreateTableNeedsADb

    public function testSepDefaultsToComma() {
        # The -s option used to be set as a local in fGetOps, so the
        # global fValidate read was never set and every run threw
        # "Missing -s option". It is in the returned array now.
        $tOpt = ImportTcsv2LoDb::fGetOps(array("import-tcsv-2-lo-db.php", "-c"), 2);

        $this->assertSame("c", $tOpt["sep"]);
    } # testSepDefaultsToComma

} # ImportTcsv2LoDbTest
