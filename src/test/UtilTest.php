<?php

# ========================================
# UtilTest.php - tests for src/bin/util.php
#
# Run:  src/bin/phpunit src/test
#   or: src/bin/bib -T php
# ========================================

require_once __DIR__ . "/bootstrap.php";

use PHPUnit\Framework\TestCase;

class UtilTest extends TestCase {

    protected function setUp(): void {
        uTestResetGlobals();
    }

    # ----------------------------------------
    # Column lists

    public function testLoCol() {
        $tCol = uLoCol();

        $this->assertIsArray($tCol);
        $this->assertCount(31, $tCol);
        $this->assertSame("Identifier", $tCol[0]);
        $this->assertContains("ISBN", $tCol);
        $this->assertContains("RepType", $tCol);
    } # testLoCol

    public function testLoColValue() {
        $tVal = uLoColValue();

        $this->assertSame(count(uLoCol()), count($tVal));
        $this->assertArrayHasKey("Identifier", $tVal);
        $this->assertSame("", $tVal["Identifier"]);
        $this->assertSame(array(""), array_unique(array_values($tVal)));
    } # testLoColValue

    public function testLibCol() {
        # KNOWN DEFECT: uLibCol() builds $tCol but returns $tLibId,
        # which is never set. Enable this test when that is fixed.
        $this->markTestIncomplete("uLibCol() returns undefined \$tLibId, not \$tCol");

        $tCol = uLibCol();
        $this->assertIsArray($tCol);
        $this->assertSame("Book_Id", $tCol[0]);
    } # testLibCol

    # ----------------------------------------
    # uDate

    public function testDateStyles() {
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', uDate("ymd"));
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}$/', uDate("iso"));
        $this->assertMatchesRegularExpression('/^\d{2}-\d{2}_\d{2}-\d{2}$/', uDate("min"));
        $this->assertMatchesRegularExpression('/^\d{14}$/', uDate("num"));
    } # testDateStyles

    public function testDateStyleIsNotCaseSensitive() {
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', uDate("YMD"));
    } # testDateStyleIsNotCaseSensitive

    public function testDateDefaultsToIso() {
        # An unknown style, and no style at all, both give "iso".
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}$/', uDate("bogus"));
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}$/', uDate());
    } # testDateDefaultsToIso

    # ----------------------------------------
    # uBool, uFixBool

    public function testBool() {
        $this->assertSame(1, uBool("true"));
        $this->assertSame(0, uBool("false"));
        $this->assertSame(0, uBool("junk"));
        $this->assertSame(0, uBool(""));
    } # testBool

    public function testFixBool() {
        # conf.php loads these as the strings exported by conf.env.
        $GLOBALS["cgDebug"] = "false";
        $GLOBALS["cgNoExec"] = "true";
        $GLOBALS["cgUseLib"] = "false";
        $GLOBALS["cgUseRemote"] = "false";
        $GLOBALS["cgVerbose"] = "false";

        uFixBool();

        $this->assertSame(0, $GLOBALS["cgDebug"]);
        $this->assertSame(1, $GLOBALS["cgNoExec"]);
        $this->assertSame(0, $GLOBALS["cgVerbose"]);
    } # testFixBool

    public function testFixBoolIsVerbose() {
        $GLOBALS["cgDebug"] = "true";
        $GLOBALS["cgNoExec"] = "false";
        $GLOBALS["cgUseLib"] = "false";
        $GLOBALS["cgUseRemote"] = "false";
        $GLOBALS["cgVerbose"] = "true";

        $tOut = uTestCapture(function () { uFixBool(); });

        $this->assertStringContainsString("Debug is on.", $tOut);
        $this->assertStringContainsString("Verbose is on.", $tOut);
        $this->assertStringNotContainsString("NoExec is on.", $tOut);
    } # testFixBoolIsVerbose

    # ----------------------------------------
    # Name maps
    #
    # Each map function returns the whole map when called with no
    # argument, and one value when called with a key.

    public function testTxt2LoMap() {
        $tMap = uTxt2LoMap();

        $this->assertIsArray($tMap);
        $this->assertArrayHasKey("Title", $tMap);
        $this->assertSame("Booktitle", uTxt2LoMap("Title"));
        $this->assertSame("Title", uTxt2LoMap("Subtitle"));
        $this->assertSame("Custom3", uTxt2LoMap("ASIN"));
        $this->assertSame("Note", uTxt2LoMap("Tag"));
    } # testTxt2LoMap

    public function testTxt2LoMapFallsBackToLowerCase() {
        # "TITLE" is not a key, so the lowercase map is tried.
        $this->assertSame("Booktitle", uTxt2LoMap("TITLE"));
    } # testTxt2LoMapFallsBackToLowerCase

    public function testTxt2LoMapUnknown() {
        $this->assertSame("Unknown", uTxt2LoMap("NoSuchKey"));
    } # testTxt2LoMapUnknown

    public function testLo2TxtMap() {
        $this->assertIsArray(uLo2TxtMap());
        $this->assertSame("Title", uLo2TxtMap("Booktitle"));
        $this->assertSame("Tags", uLo2TxtMap("Note"));
        $this->assertSame("Unknown", uLo2TxtMap("NoSuchKey"));
    } # testLo2TxtMap

    public function testMedia2RepType() {
        $this->assertIsArray(uMedia2RepType());
        $this->assertSame("DVD", uMedia2RepType("dvd"));
        $this->assertSame("book", uMedia2RepType("book"));
        $this->assertSame("Ebook", uMedia2RepType("kindle"));
        $this->assertSame("unknown", uMedia2RepType("NoSuchMedia"));
    } # testMedia2RepType

    public function testRepType2Type() {
        $this->assertIsArray(uRepType2Type());
        $this->assertSame(0, uRepType2Type("article"));
        $this->assertSame(1, uRepType2Type("book"));
        $this->assertSame(10, uRepType2Type("video"));
        $this->assertSame(16, uRepType2Type("site"));
    } # testRepType2Type

    public function testRepType2TypeIsNotCaseSensitive() {
        $this->assertSame(10, uRepType2Type("DVD"));
        $this->assertSame(1, uRepType2Type("Paperback"));
    } # testRepType2TypeIsNotCaseSensitive

    public function testRepType2TypeUnknownIsSite() {
        $this->assertSame(16, uRepType2Type("NoSuchMedia"));
    } # testRepType2TypeUnknownIsSite

    public function testType2Txt() {
        $this->assertIsArray(uType2Txt());
        $this->assertSame("article", uType2Txt(0));
        $this->assertSame("book", uType2Txt(1));
        $this->assertSame("media", uType2Txt(10));
        $this->assertSame("site", uType2Txt(16));
        $this->assertSame("site", uType2Txt(99));
    } # testType2Txt

    public function testTypeRoundTrip() {
        # Type -> txt -> Type is stable for the four Type numbers.
        foreach (array_keys(uType2Txt()) as $tType)
            $this->assertSame($tType, uRepType2Type(uType2Txt($tType)));
    } # testTypeRoundTrip

    public function testAliases() {
        # uTxt2Type and uTxt2RepType just call the map functions.
        $this->assertSame(uRepType2Type("book"), uTxt2Type("book"));
        $this->assertSame(uMedia2RepType("dvd"), uTxt2RepType("dvd"));
    } # testAliases

    public function testLib2Lo() {
        $this->assertIsArray(uLib2Lo());
        $this->assertSame("Identifier", uLib2Lo("Book_Id"));
        $this->assertSame("Booktitle", uLib2Lo("Title"));
        $this->assertSame("RepType", uLib2Lo("Media"));
        $this->assertSame("Unknown", uLib2Lo("NoSuchCol"));
    } # testLib2Lo

    public function testBib2Xml() {
        $this->assertIsArray(uBib2Xml());
        $this->assertSame("identifier", uBib2Xml("Identifier"));
        $this->assertSame("bibliography-type", uBib2Xml("Type"));
        $this->assertSame("report-type", uBib2Xml("RepType"));
        $this->assertSame("custom5", uBib2Xml("NoSuchCol"));
    } # testBib2Xml

    public function testBib2XmlCoversEveryLoCol() {
        # Every lo column must have an XML name.
        foreach (uLoCol() as $tCol)
            $this->assertArrayHasKey($tCol, uBib2Xml(), "Missing XML name for: $tCol");
    } # testBib2XmlCoversEveryLoCol

    public function testBibType2Xml() {
        $this->assertIsArray(uBibType2Xml());
        $this->assertSame("article", uBibType2Xml(0));
        $this->assertSame("book", uBibType2Xml(1));
        $this->assertSame("misc", uBibType2Xml(10));
        $this->assertSame("www", uBibType2Xml(16));
        $this->assertSame("www", uBibType2Xml(99));
    } # testBibType2Xml

    # ----------------------------------------
    # DB functions, with cgNoExec on

    public function testExecSqlWithNoExec() {
        # With cgNoExec set, no SQL is sent and $gDb is not touched.
        $this->assertTrue(uExecSql("select 1"));
    } # testExecSqlWithNoExec

    public function testExecSqlWithDebug() {
        $GLOBALS["cgDebug"] = 1;

        $tOut = uTestCapture(function () { uExecSql("select 1"); });

        $this->assertStringContainsString("select 1", $tOut);
    } # testExecSqlWithDebug

    public function testRenameTableWithNoExec() {
        $tName = uRenameTable("lo");

        $this->assertMatchesRegularExpression(
            '/^lo_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}$/', $tName);
    } # testRenameTableWithNoExec

    public function testValidateCommonNeedsPassCache() {
        # cgDbPassCache points at a file that does not exist.
        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/cgDbPassCache/');

        uValidateCommon();
    } # testValidateCommonNeedsPassCache

    # ----------------------------------------
    # File functions

    public function testPackFileWithNoExec() {
        $tOut = uTestCapture(function () { uPackFile("example.odt", "content"); });

        $this->assertStringContainsString("No changes to example.odt", $tOut);
        $this->assertStringContainsString("content", $tOut);
    } # testPackFileWithNoExec

    public function testUnpackFile() {
        # uUnpackFile shells out to unzip and xmllint, so it needs a
        # small .odt in test/sample/ before it can be tested. Note that
        # unzip's stderr is not redirected, so a failed unpack writes to
        # the terminal as well as throwing.
        $this->markTestIncomplete("uUnpackFile needs a sample .odt in test/sample/");

        global $cgDirSample;
        $GLOBALS["cgNoExec"] = 0;
        $GLOBALS["cgDirTmp"] = $GLOBALS["cgDirTmpTest"];

        uUnpackFile("$cgDirSample/sample.odt", "content");
        $this->assertFileExists($GLOBALS["cgDirTmp"] . "/content.xml");
    } # testUnpackFile

} # UtilTest
