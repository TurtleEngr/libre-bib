<?php

# ========================================
# ImportTxt2LoTest.php - tests for class ImportTxt2Lo, in
# src/bin/import-txt-2-lo.inc
#
# cgNoExec is on throughout, so no SQL reaches a DB and mDb never needs
# a PDO handle.
#
# Run:  src/bin/phpunit src/test
#   or: src/bin/bib -T php
# ========================================

require_once __DIR__ . "/bootstrap.php";

use PHPUnit\Framework\TestCase;

class ImportTxt2LoTest extends TestCase {

    private $tApp;

    public static function setUpBeforeClass(): void {
        uTestLoadScript("import-txt-2-lo");
    }

    protected function setUp(): void {
        uTestResetGlobals();
        $this->tApp = new ImportTxt2Lo(uTestConf());
        $this->tApp->mDb = new Db(null, uTestConf());
    }

    # ----------------------------------------

    public function testMethodsAreDefined() {
        foreach (array("fUsage", "fCleanUp", "fGetOps", "fValidate", "fRun",
                "fCreateTable", "fInsertRec", "fParseLine", "fAddRec",
                "fImportTxt") as $tName)
            $this->assertTrue(method_exists("ImportTxt2Lo", $tName),
                "Missing method: $tName");
    } # testMethodsAreDefined

    # ----------------------------------------
    # fGetOps

    public function testGetOpsDefaultsToHelpWithNoArgs() {
        $tOpt = ImportTxt2Lo::fGetOps(array("import-txt-2-lo.php"), 1);

        $this->assertTrue($tOpt["help"]);
        $this->assertSame("", $tOpt["test"]);
    } # testGetOpsDefaultsToHelpWithNoArgs

    public function testConstructorTakesConf() {
        $tConf = uTestConf();
        $tApp = new ImportTxt2Lo($tConf);

        $this->assertSame($tConf["cgDbTblLo"], $tApp->mDbTblLo);
        $this->assertSame($tConf["cgLoFile"], $tApp->mLoFile);
    } # testConstructorTakesConf

    public function testCleanUp() {
        $tApp = $this->tApp;
        $tOut = uTestCapture(function () use ($tApp) { $tApp->fCleanUp(); });

        $this->assertSame("\n", $tOut);
    } # testCleanUp

    # ----------------------------------------
    # fParseLine

    public function testParseLine() {
        $tData = $this->tApp->fParseLine("Id: achbar-01");

        $this->assertSame("Id", $tData["key"]);
        $this->assertSame("achbar-01", $tData["val"]);
    } # testParseLine

    public function testParseLineKeepsRestOfValue() {
        # Only the first ": " splits the line.
        $tData = $this->tApp->fParseLine("Link: https://www.tamera.org/");

        $this->assertSame("Link", $tData["key"]);
        $this->assertSame("https://www.tamera.org/", $tData["val"]);
    } # testParseLineKeepsRestOfValue

    public function testParseLineTrimsValue() {
        $tData = $this->tApp->fParseLine("Title:   The Corporation   ");

        $this->assertSame("The Corporation", $tData["val"]);
    } # testParseLineTrimsValue

    public function testParseLineNeedsSpaceAfterColon() {
        # "Id:" with nothing after it is not a key/value pair.
        $tData = $this->tApp->fParseLine("Id:");

        $this->assertSame("", $tData["key"]);
        $this->assertSame("", $tData["val"]);
    } # testParseLineNeedsSpaceAfterColon

    public function testParseLineWithNoColon() {
        $tData = $this->tApp->fParseLine("just some text");

        $this->assertSame("", $tData["key"]);
        $this->assertSame("", $tData["val"]);
    } # testParseLineWithNoColon

    public function testParseLineTruncatesLongValue() {
        # Values go into VARCHAR(255) columns.
        $tData = $this->tApp->fParseLine("Annote: " . str_repeat("x", 300));

        $this->assertSame(254, strlen($tData["val"]));
    } # testParseLineTruncatesLongValue

    # ----------------------------------------
    # fInsertRec, fAddRec - cgNoExec is on, so no SQL is sent

    public function testInsertRec() {
        $tRec = Map::uLoColValue();
        $tRec["Identifier"] = "test-01";
        $tRec["Booktitle"] = "A Title";

        $this->tApp->fInsertRec($tRec);

        $this->assertTrue(true, "fInsertRec did not throw");
    } # testInsertRec

    public function testInsertRecBuildsSql() {
        # The SQL is echoed by Db, so it is Db's own debug flag now.
        $this->tApp->mDb->mDebug = 1;
        $tRec = Map::uLoColValue();
        $tRec["Identifier"] = "test-01";

        $tApp = $this->tApp;

        $tOut = uTestCapture(function () use ($tApp, $tRec) { $tApp->fInsertRec($tRec); });

        $this->assertStringContainsString("INSERT INTO lo", $tOut);
        $this->assertStringContainsString("`Identifier`", $tOut);
        $this->assertStringContainsString('"test-01"', $tOut);
    } # testInsertRecBuildsSql

    public function testAddRecCountsRecords() {
        $tRec = Map::uLoColValue();
        $tRec["Identifier"] = "test-01";
        $tRec["RepType"] = "book";

        $this->tApp->fAddRec($tRec);

        $this->assertSame(1, $this->tApp->mNumRec);
    } # testAddRecCountsRecords

    public function testAddRecSetsTypeFromRepType() {
        # The SQL is echoed by Db, so it is Db's own debug flag now.
        $this->tApp->mDb->mDebug = 1;
        $tRec = Map::uLoColValue();
        $tRec["Identifier"] = "test-01";
        $tRec["RepType"] = "Paperback";

        $tApp = $this->tApp;

        $tOut = uTestCapture(function () use ($tApp, $tRec) { $tApp->fAddRec($tRec); });

        # Map::uRepType2Type("Paperback") is 1
        $this->assertStringContainsString('"1"', $tOut);
    } # testAddRecSetsTypeFromRepType

    public function testAddRecSetsRepTypeFromType() {
        # No Media in the record, so RepType comes from Type.
        # The SQL is echoed by Db, so it is Db's own debug flag now.
        $this->tApp->mDb->mDebug = 1;
        $tRec = Map::uLoColValue();
        $tRec["Identifier"] = "test-01";
        $tRec["Type"] = 1;

        $tApp = $this->tApp;

        $tOut = uTestCapture(function () use ($tApp, $tRec) { $tApp->fAddRec($tRec); });

        $this->assertStringContainsString('"book"', $tOut);
    } # testAddRecSetsRepTypeFromType

    public function testAddRecWarnsWhenTypeIsMissing() {
        $tRec = Map::uLoColValue();
        $tRec["Identifier"] = "test-01";

        $tApp = $this->tApp;

        $tOut = uTestCapture(function () use ($tApp, $tRec) { $tApp->fAddRec($tRec); });

        $this->assertStringContainsString("Warning: Missing Media and Type", $tOut);
    } # testAddRecWarnsWhenTypeIsMissing

    # ----------------------------------------
    # fImportTxt - reads doc/example/biblio.txt

    public function testImportTxtReadsSampleFile() {
        global $cgDirExample;

        $tFile = "$cgDirExample/biblio.txt";
        $tExpectRec = count(preg_grep('/^Id: /', file($tFile)));
        $tExpectLine = count(file($tFile));

        $this->tApp->mFileH = fopen($tFile, "r");
        $tApp = $this->tApp;
        $tOut = uTestCapture(function () use ($tApp) { $tApp->fImportTxt(); });

        $this->assertSame($tExpectRec, $this->tApp->mNumRec);
        $this->assertSame($tExpectLine, $this->tApp->mNumLine);
        $this->assertStringContainsString("Inserted $tExpectRec records.", $tOut);
    } # testImportTxtReadsSampleFile

    public function testImportTxtWarnsOnUnknownKey() {
        global $cgDirTmpTest;

        $tFile = "$cgDirTmpTest/unknown-key.txt";
        file_put_contents($tFile,
            "Id: test-01\n" .
            "Media: book\n" .
            "NoSuchKey: some value\n");

        $this->tApp->mFileH = fopen($tFile, "r");
        $tApp = $this->tApp;
        $tOut = uTestCapture(function () use ($tApp) { $tApp->fImportTxt(); });

        $this->assertStringContainsString("NoSuchKey not found in KeyMap", $tOut);
        $this->assertSame(1, $this->tApp->mNumRec);
    } # testImportTxtWarnsOnUnknownKey

    public function testImportTxtSkipsCommentsAndBlankLines() {
        global $cgDirTmpTest;

        $tFile = "$cgDirTmpTest/comments.txt";
        file_put_contents($tFile,
            "# a comment\n" .
            "\n" .
            "Id: test-01\n" .
            "Media: book\n");

        $this->tApp->mFileH = fopen($tFile, "r");
        $tApp = $this->tApp;
        uTestCapture(function () use ($tApp) { $tApp->fImportTxt(); });

        $this->assertSame(4, $this->tApp->mNumLine);
        $this->assertSame(1, $this->tApp->mNumRec);
    } # testImportTxtSkipsCommentsAndBlankLines

    # ----------------------------------------
    # Needs a DB

    public function testCreateTable() {
        $this->markTestIncomplete("fCreateTable needs a live DB connection");
    } # testCreateTable

    public function testValidateNeedsSetup() {
        # fValidate connects first, which needs the password cache made
        # by: bib connect
        $this->expectException(Exception::class);

        $this->tApp->fValidate();
    } # testValidateNeedsSetup

} # ImportTxt2LoTest
