<?php

# ========================================
# BibRefUpdateTest.php - tests for class BibRefUpdate, in src/bin/bib-ref-update.inc
#
# cgNoExec is on throughout, so no SQL reaches a DB and mDb never needs
# a PDO handle. Anything that has to talk to a DB or open a real .odt is
# marked incomplete rather than skipped silently.
#
# Run:  src/bin/phpunit src/test
#   or: src/bin/bib-ref-update.php -T all
# ========================================

require_once __DIR__ . "/bootstrap.php";

use PHPUnit\Framework\TestCase;

class BibRefUpdateTest extends TestCase {

    private $tApp;

    public static function setUpBeforeClass(): void {
        uTestLoadScript("bib-ref-update");
    }

    protected function setUp(): void {
        uTestResetGlobals();
        $this->tApp = new BibRefUpdate(uTestConf());
        $this->tApp->mDb = new Db(null, uTestConf());
    } # setUp

    # ----------------------------------------

    public function testMethodsAreDefined() {
        foreach (array("fUsage", "fCleanUp", "fGetOps", "fValidate", "fRun", "fBibLookup", "fUpdateMark", "fProcessFile") as $tName)
            $this->assertTrue(method_exists("BibRefUpdate", $tName), "Missing method: $tName");
    } # testMethodsAreDefined

    public function testCleanUp() {
        $tApp = $this->tApp;

        $tOut = uTestCapture(function () use ($tApp) { $tApp->fCleanUp(); });

        $this->assertSame("\n", $tOut);
    } # testCleanUp

    # ----------------------------------------
    # fGetOps - no conf and no DB needed, so -h and -T always work

    public function testGetOpsDefaultsToHelpWithNoArgs() {
        $tOpt = BibRefUpdate::fGetOps(array("bib-ref-update.php"), 1);

        $this->assertTrue($tOpt["help"]);
    } # testGetOpsDefaultsToHelpWithNoArgs

    public function testGetOpsReturnsEveryOption() {
        $tOpt = BibRefUpdate::fGetOps(array("bib-ref-update.php", "-c"), 2);

        foreach (array("help", "change", "test") as $tKey)
            $this->assertArrayHasKey($tKey, $tOpt);
    } # testGetOpsReturnsEveryOption

    public function testGetOpsTestDefaultsToEmpty() {
        $tOpt = BibRefUpdate::fGetOps(array("bib-ref-update.php", "-c"), 2);

        $this->assertSame("", $tOpt["test"]);
    } # testGetOpsTestDefaultsToEmpty

    # ----------------------------------------
    # The constructor takes conf; nothing reads a global any more.

    public function testConstructorTakesConf() {
        $tConf = uTestConf();
        $tApp = new BibRefUpdate($tConf);

        $this->assertSame($tConf["cgDbTblBib"], $tApp->mDbTblBib);
        $this->assertSame($tConf["cgDirTmp"], $tApp->mDirTmp);
        $this->assertSame($tConf["cgDocFile"], $tApp->mDocFile);
    } # testConstructorTakesConf

    public function testConstructorMakesAUtil() {
        $this->assertInstanceOf("Util", $this->tApp->mUtil);
    } # testConstructorMakesAUtil

    public function testConstructorKeepsTheOptions() {
        $tApp = new BibRefUpdate(uTestConf(), array("test"=>"all"));

        $this->assertSame("all", $tApp->mOpt["test"]);
    } # testConstructorKeepsTheOptions

    # ----------------------------------------
    # fBibLookup - the DB path, with a canned row

    public function testBibLookupBuildsARefFromARow() {
        # Same missed call as bib-ref-new: Map::uBibType2Xml() is only
        # reached once the lookup finds a row.
        $this->tApp->mDb = new uTestDb(uTestConf(), array(
            "Identifier"=>"artymiak-11",
            "Type"=>1,
            "Author"=>"Artymiak, Jacek"
        ));

        $tRef = $this->tApp->fBibLookup("artymiak-11");

        $this->assertSame("artymiak-11", $tRef["id"]);
        $this->assertSame(Map::uBibType2Xml(1), $tRef["type"]);
        $this->assertIsArray($tRef["data"]);
        $this->assertContains("Artymiak, Jacek", $tRef["data"]);
    } # testBibLookupBuildsARefFromARow

    # ----------------------------------------
    # Needs a DB

    public function testValidateNeedsSetup() {
        # fValidate connects first, which needs the password cache made
        # by: bib connect
        $this->expectException(Exception::class);

        $this->tApp->fValidate();
    } # testValidateNeedsSetup

    public function testProcessFileNeedsAnUnpackedOdt() {
        $this->markTestIncomplete("fProcessFile needs an unpacked content.xml");
    } # testProcessFileNeedsAnUnpackedOdt

} # BibRefUpdateTest
