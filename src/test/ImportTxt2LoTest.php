<?php

# ========================================
# ImportTxt2LoTest.php - tests for src/bin/import-txt-2-lo.php
#
# The script is still one file: its main section runs on include.
# uTestLoadScript() (see bootstrap.php) pulls in only the functions,
# so nothing in bin/ has to change. Once the script is split into
# import-txt-2-lo (wrapper) + import-txt-2-lo.inc, the same call
# picks up the .inc instead, and these tests keep working.
#
# Run:  src/bin/phpunit src/test
#   or: src/bin/bib -T php
# ========================================

require_once __DIR__ . "/bootstrap.php";

use PHPUnit\Framework\TestCase;

class ImportTxt2LoTest extends TestCase {

    public static function setUpBeforeClass(): void {
        uTestLoadScript("import-txt-2-lo");
    }

    protected function setUp(): void {
        uTestResetGlobals();
    }

    # ----------------------------------------

    public function testFunctionsAreDefined() {
        foreach (array("fUsage", "fCleanUp", "fGetOps", "fValidate",
                "fCreateTable", "fInsertRec", "fParseLine", "fAddRec",
                "fImportTxt") as $tName)
            $this->assertTrue(function_exists($tName), "Missing function: $tName");
    } # testFunctionsAreDefined

    public function testCleanUp() {
        $tOut = uTestCapture(function () { fCleanUp(); });

        $this->assertSame("\n", $tOut);
    } # testCleanUp

    # ----------------------------------------
    # fParseLine

    public function testParseLine() {
        $tData = fParseLine("Id: achbar-01");

        $this->assertSame("Id", $tData["key"]);
        $this->assertSame("achbar-01", $tData["val"]);
    } # testParseLine

    public function testParseLineKeepsRestOfValue() {
        # Only the first ": " splits the line.
        $tData = fParseLine("Link: https://www.tamera.org/");

        $this->assertSame("Link", $tData["key"]);
        $this->assertSame("https://www.tamera.org/", $tData["val"]);
    } # testParseLineKeepsRestOfValue

    public function testParseLineTrimsValue() {
        $tData = fParseLine("Title:   The Corporation   ");

        $this->assertSame("The Corporation", $tData["val"]);
    } # testParseLineTrimsValue

    public function testParseLineNeedsSpaceAfterColon() {
        # "Id:" with nothing after it is not a key/value pair.
        $tData = fParseLine("Id:");

        $this->assertSame("", $tData["key"]);
        $this->assertSame("", $tData["val"]);
    } # testParseLineNeedsSpaceAfterColon

    public function testParseLineWithNoColon() {
        $tData = fParseLine("just some text");

        $this->assertSame("", $tData["key"]);
        $this->assertSame("", $tData["val"]);
    } # testParseLineWithNoColon

    public function testParseLineTruncatesLongValue() {
        # Values go into VARCHAR(255) columns.
        $tData = fParseLine("Annote: " . str_repeat("x", 300));

        $this->assertSame(254, strlen($tData["val"]));
    } # testParseLineTruncatesLongValue

    # ----------------------------------------
    # fInsertRec, fAddRec - cgNoExec is on, so no SQL is sent

    public function testInsertRec() {
        $tRec = uLoColValue();
        $tRec["Identifier"] = "test-01";
        $tRec["Booktitle"] = "A Title";

        fInsertRec($tRec);

        $this->assertTrue(true, "fInsertRec did not throw");
    } # testInsertRec

    public function testInsertRecBuildsSql() {
        $GLOBALS["cgDebug"] = 1;
        $tRec = uLoColValue();
        $tRec["Identifier"] = "test-01";

        $tOut = uTestCapture(function () use ($tRec) { fInsertRec($tRec); });

        $this->assertStringContainsString("INSERT INTO lo", $tOut);
        $this->assertStringContainsString("`Identifier`", $tOut);
        $this->assertStringContainsString('"test-01"', $tOut);
    } # testInsertRecBuildsSql

    public function testAddRecCountsRecords() {
        $tRec = uLoColValue();
        $tRec["Identifier"] = "test-01";
        $tRec["RepType"] = "book";

        fAddRec($tRec);

        $this->assertSame(1, $GLOBALS["gNumRec"]);
    } # testAddRecCountsRecords

    public function testAddRecSetsTypeFromRepType() {
        $GLOBALS["cgDebug"] = 1;
        $tRec = uLoColValue();
        $tRec["Identifier"] = "test-01";
        $tRec["RepType"] = "Paperback";

        $tOut = uTestCapture(function () use ($tRec) { fAddRec($tRec); });

        # uRepType2Type("Paperback") is 1
        $this->assertStringContainsString('"1"', $tOut);
    } # testAddRecSetsTypeFromRepType

    public function testAddRecSetsRepTypeFromType() {
        # No Media in the record, so RepType comes from Type.
        $GLOBALS["cgDebug"] = 1;
        $tRec = uLoColValue();
        $tRec["Identifier"] = "test-01";
        $tRec["Type"] = 1;

        $tOut = uTestCapture(function () use ($tRec) { fAddRec($tRec); });

        $this->assertStringContainsString('"book"', $tOut);
    } # testAddRecSetsRepTypeFromType

    public function testAddRecWarnsWhenTypeIsMissing() {
        $tRec = uLoColValue();
        $tRec["Identifier"] = "test-01";

        $tOut = uTestCapture(function () use ($tRec) { fAddRec($tRec); });

        $this->assertStringContainsString("Warning: Missing Media and Type", $tOut);
    } # testAddRecWarnsWhenTypeIsMissing

    # ----------------------------------------
    # fImportTxt - reads test/sample/biblio.txt

    public function testImportTxtReadsSampleFile() {
        global $cgDirSample;

        $tFile = "$cgDirSample/biblio.txt";
        $tExpectRec = count(preg_grep('/^Id: /', file($tFile)));
        $tExpectLine = count(file($tFile));

        $GLOBALS["gFileH"] = fopen($tFile, "r");
        $tOut = uTestCapture(function () { fImportTxt(); });

        $this->assertSame($tExpectRec, $GLOBALS["gNumRec"]);
        $this->assertSame($tExpectLine, $GLOBALS["gNumLine"]);
        $this->assertStringContainsString("Inserted $tExpectRec records.", $tOut);
    } # testImportTxtReadsSampleFile

    public function testImportTxtWarnsOnUnknownKey() {
        global $cgDirTmpTest;

        $tFile = "$cgDirTmpTest/unknown-key.txt";
        file_put_contents($tFile,
            "Id: test-01\n" .
            "Media: book\n" .
            "NoSuchKey: some value\n");

        $GLOBALS["gFileH"] = fopen($tFile, "r");
        $tOut = uTestCapture(function () { fImportTxt(); });

        $this->assertStringContainsString("NoSuchKey not found in KeyMap", $tOut);
        $this->assertSame(1, $GLOBALS["gNumRec"]);
    } # testImportTxtWarnsOnUnknownKey

    public function testImportTxtSkipsCommentsAndBlankLines() {
        global $cgDirTmpTest;

        $tFile = "$cgDirTmpTest/comments.txt";
        file_put_contents($tFile,
            "# a comment\n" .
            "\n" .
            "Id: test-01\n" .
            "Media: book\n");

        $GLOBALS["gFileH"] = fopen($tFile, "r");
        uTestCapture(function () { fImportTxt(); });

        $this->assertSame(4, $GLOBALS["gNumLine"]);
        $this->assertSame(1, $GLOBALS["gNumRec"]);
    } # testImportTxtSkipsCommentsAndBlankLines

    # ----------------------------------------
    # Needs a DB

    public function testCreateTable() {
        $this->markTestIncomplete("fCreateTable needs a live DB connection");
    } # testCreateTable

    public function testValidateNeedsSetup() {
        # fValidate calls uValidateCommon first, which needs the
        # password cache made by: bib connect
        $this->expectException(Exception::class);

        fValidate();
    } # testValidateNeedsSetup

} # ImportTxt2LoTest
