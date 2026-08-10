<?php

# ========================================
# BibStyleUpdateTest.php - tests for class BibStyleUpdate, in src/bin/bib-style-update.inc
#
# cgNoExec is on throughout, so no SQL reaches a DB and mDb never needs
# a PDO handle. Anything that has to talk to a DB or open a real .odt is
# marked incomplete rather than skipped silently.
#
# Run:  src/bin/phpunit src/test
#   or: src/bin/bib-style-update.php -T all
# ========================================

require_once __DIR__ . "/bootstrap.php";

use PHPUnit\Framework\TestCase;

class BibStyleUpdateTest extends TestCase {

    private $tApp;

    public static function setUpBeforeClass(): void {
        uTestLoadScript("bib-style-update");
    }

    protected function setUp(): void {
        uTestResetGlobals();
        $this->tApp = new BibStyleUpdate(uTestConf());
        $this->tApp->mDb = new Db(null, uTestConf());
    } # setUp

    # ----------------------------------------

    public function testMethodsAreDefined() {
        foreach (array("fUsage", "fCleanUp", "fGetOps", "fValidate", "fRun", "fReplaceElement", "fProcessStyleFile", "fProcessContentFile") as $tName)
            $this->assertTrue(method_exists("BibStyleUpdate", $tName), "Missing method: $tName");
    } # testMethodsAreDefined

    public function testCleanUp() {
        $tApp = $this->tApp;

        $tOut = uTestCapture(function () use ($tApp) { $tApp->fCleanUp(); });

        $this->assertSame("\n", $tOut);
    } # testCleanUp

    # ----------------------------------------
    # fGetOps - no conf and no DB needed, so -h and -T always work

    public function testGetOpsDefaultsToHelpWithNoArgs() {
        $tOpt = BibStyleUpdate::fGetOps(array("bib-style-update.php"), 1);

        $this->assertTrue($tOpt["help"]);
    } # testGetOpsDefaultsToHelpWithNoArgs

    public function testGetOpsReturnsEveryOption() {
        $tOpt = BibStyleUpdate::fGetOps(array("bib-style-update.php", "-c"), 2);

        foreach (array("help", "change", "test") as $tKey)
            $this->assertArrayHasKey($tKey, $tOpt);
    } # testGetOpsReturnsEveryOption

    public function testGetOpsTestDefaultsToEmpty() {
        $tOpt = BibStyleUpdate::fGetOps(array("bib-style-update.php", "-c"), 2);

        $this->assertSame("", $tOpt["test"]);
    } # testGetOpsTestDefaultsToEmpty

    # ----------------------------------------
    # The constructor takes conf; nothing reads a global any more.

    public function testConstructorTakesConf() {
        $tConf = uTestConf();
        $tApp = new BibStyleUpdate($tConf);

        $this->assertSame($tConf["cgDirEtc"], $tApp->mDirEtc);
        $this->assertSame($tConf["cgDirTmp"], $tApp->mDirTmp);
        $this->assertSame($tConf["cgDocFile"], $tApp->mDocFile);
    } # testConstructorTakesConf

    public function testConstructorMakesAUtil() {
        $this->assertInstanceOf("Util", $this->tApp->mUtil);
    } # testConstructorMakesAUtil

    public function testConstructorKeepsTheOptions() {
        $tApp = new BibStyleUpdate(uTestConf(), array("test"=>"all"));

        $this->assertSame("all", $tApp->mOpt["test"]);
    } # testConstructorKeepsTheOptions

    # ----------------------------------------
    # Needs a DB

    public function testValidateWantsTheStyleFiles() {
        # This script needs no DB: fValidate only checks that the two
        # etc/ templates are there.
        $tConf = uTestConf();
        $tConf["cgDirEtc"] = $tConf["cgDirTmp"] . "/no-such-etc";
        $tApp = new BibStyleUpdate($tConf);

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/bib-style.xml/');

        $tApp->fValidate();
    } # testValidateWantsTheStyleFiles

    public function testValidatePassesWithTheStyleFiles() {
        $tConf = uTestConf();
        $tConf["cgDirEtc"] = dirname(__DIR__) . "/etc";
        $tApp = new BibStyleUpdate($tConf);

        $tApp->fValidate();

        $this->assertTrue(true, "fValidate did not throw");
    } # testValidatePassesWithTheStyleFiles

    public function testProcessStyleFileNeedsAnUnpackedOdt() {
        $this->markTestIncomplete("fProcessStyleFile needs an unpacked styles.xml");
    } # testProcessStyleFileNeedsAnUnpackedOdt

} # BibStyleUpdateTest
