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
        $tCol = Map::uLoCol();

        $this->assertIsArray($tCol);
        $this->assertCount(31, $tCol);
        $this->assertSame("Identifier", $tCol[0]);
        $this->assertContains("ISBN", $tCol);
        $this->assertContains("RepType", $tCol);
    } # testLoCol

    public function testLoColValue() {
        $tVal = Map::uLoColValue();

        $this->assertSame(count(Map::uLoCol()), count($tVal));
        $this->assertArrayHasKey("Identifier", $tVal);
        $this->assertSame("", $tVal["Identifier"]);
        $this->assertSame(array(""), array_unique(array_values($tVal)));
    } # testLoColValue

    public function testLibCol() {
        $tCol = Map::uLibCol();
        $this->assertIsArray($tCol);
        $this->assertSame("Book_Id", $tCol[0]);
    } # testLibCol

    # ----------------------------------------
    # uDate

    public function testDateStyles() {
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', Util::uDate("ymd"));
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}$/', Util::uDate("iso"));
        $this->assertMatchesRegularExpression('/^\d{2}-\d{2}_\d{2}-\d{2}$/', Util::uDate("min"));
        $this->assertMatchesRegularExpression('/^\d{14}$/', Util::uDate("num"));
    } # testDateStyles

    public function testDateStyleIsNotCaseSensitive() {
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', Util::uDate("YMD"));
    } # testDateStyleIsNotCaseSensitive

    public function testDateDefaultsToIso() {
        # An unknown style, and no style at all, both give "iso".
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}$/', Util::uDate("bogus"));
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}$/', Util::uDate());
    } # testDateDefaultsToIso

    # ----------------------------------------
    # uBool, uFixBool

    public function testBool() {
        $this->assertSame(1, Util::uBool("true"));
        $this->assertSame(0, Util::uBool("false"));
        $this->assertSame(0, Util::uBool("junk"));
        $this->assertSame(0, Util::uBool(""));
    } # testBool

    public function testFixBool() {
        # conf.php loads these as the strings exported by conf.env.
        $GLOBALS["cgDebug"] = "false";
        $GLOBALS["cgNoExec"] = "true";
        $GLOBALS["cgVerbose"] = "false";

        Util::uFixBool();

        $this->assertSame(0, $GLOBALS["cgDebug"]);
        $this->assertSame(1, $GLOBALS["cgNoExec"]);
        $this->assertSame(0, $GLOBALS["cgVerbose"]);
    } # testFixBool

    public function testFixBoolIsVerbose() {
        $GLOBALS["cgDebug"] = "true";
        $GLOBALS["cgNoExec"] = "false";
        $GLOBALS["cgVerbose"] = "true";

        $tOut = uTestCapture(function () { Util::uFixBool(); });

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
        $tMap = Map::uTxt2LoMap();

        $this->assertIsArray($tMap);
        $this->assertArrayHasKey("Title", $tMap);
        $this->assertSame("Booktitle", Map::uTxt2LoMap("Title"));
        $this->assertSame("Title", Map::uTxt2LoMap("Subtitle"));
        $this->assertSame("Custom3", Map::uTxt2LoMap("ASIN"));
        $this->assertSame("Note", Map::uTxt2LoMap("Tag"));
    } # testTxt2LoMap

    public function testTxt2LoMapFallsBackToLowerCase() {
        # "TITLE" is not a key, so the lowercase map is tried.
        $this->assertSame("Booktitle", Map::uTxt2LoMap("TITLE"));
    } # testTxt2LoMapFallsBackToLowerCase

    public function testTxt2LoMapUnknown() {
        $this->assertSame("Unknown", Map::uTxt2LoMap("NoSuchKey"));
    } # testTxt2LoMapUnknown

    public function testLo2TxtMap() {
        $this->assertIsArray(Map::uLo2TxtMap());
        $this->assertSame("Title", Map::uLo2TxtMap("Booktitle"));
        $this->assertSame("Tags", Map::uLo2TxtMap("Note"));
        $this->assertSame("Unknown", Map::uLo2TxtMap("NoSuchKey"));
    } # testLo2TxtMap

    public function testMedia2RepType() {
        $this->assertIsArray(Map::uMedia2RepType());
        $this->assertSame("dvd", Map::uMedia2RepType("dvd"));
        $this->assertSame("book", Map::uMedia2RepType("book"));
        $this->assertSame("ebook", Map::uMedia2RepType("kindle"));
        $this->assertSame("unknown", Map::uMedia2RepType("NoSuchMedia"));
    } # testMedia2RepType

    public function testRepType2Type() {
        $this->assertIsArray(Map::uRepType2Type());
        $this->assertSame(0, Map::uRepType2Type("article"));
        $this->assertSame(1, Map::uRepType2Type("book"));
        $this->assertSame(10, Map::uRepType2Type("video"));
        $this->assertSame(16, Map::uRepType2Type("site"));
    } # testRepType2Type

    public function testRepType2TypeIsNotCaseSensitive() {
        $this->assertSame(10, Map::uRepType2Type("DVD"));
        $this->assertSame(1, Map::uRepType2Type("Paperback"));
    } # testRepType2TypeIsNotCaseSensitive

    public function testRepType2TypeUnknownIsSite() {
        $this->assertSame(16, Map::uRepType2Type("NoSuchMedia"));
    } # testRepType2TypeUnknownIsSite

    public function testType2Txt() {
        $this->assertIsArray(Map::uType2Txt());
        $this->assertSame("article", Map::uType2Txt(0));
        $this->assertSame("book", Map::uType2Txt(1));
        $this->assertSame("media", Map::uType2Txt(10));
        $this->assertSame("site", Map::uType2Txt(16));
        $this->assertSame("site", Map::uType2Txt(99));
    } # testType2Txt

    public function testTypeRoundTrip() {
        # Type -> txt -> Type is stable for the four Type numbers.
        foreach (array_keys(Map::uType2Txt()) as $tType)
            $this->assertSame($tType, Map::uRepType2Type(Map::uType2Txt($tType)));
    } # testTypeRoundTrip

    public function testAliases() {
        # uTxt2Type and uTxt2RepType just call the map functions.
        $this->assertSame(Map::uRepType2Type("book"), Map::uTxt2Type("book"));
        $this->assertSame(Map::uMedia2RepType("dvd"), Map::uTxt2RepType("dvd"));
    } # testAliases

    public function testLib2Lo() {
        $this->assertIsArray(Map::uLib2Lo());
        $this->assertSame("Identifier", Map::uLib2Lo("Book_Id"));
        $this->assertSame("Booktitle", Map::uLib2Lo("Title"));
        $this->assertSame("RepType", Map::uLib2Lo("Media"));
        $this->assertSame("Unknown", Map::uLib2Lo("NoSuchCol"));
    } # testLib2Lo

    public function testBib2Xml() {
        $this->assertIsArray(Map::uBib2Xml());
        $this->assertSame("identifier", Map::uBib2Xml("Identifier"));
        $this->assertSame("bibliography-type", Map::uBib2Xml("Type"));
        $this->assertSame("report-type", Map::uBib2Xml("RepType"));
        $this->assertSame("custom5", Map::uBib2Xml("NoSuchCol"));
    } # testBib2Xml

    public function testBib2XmlCoversEveryLoCol() {
        # Every lo column must have an XML name.
        foreach (Map::uLoCol() as $tCol)
            $this->assertArrayHasKey($tCol, Map::uBib2Xml(), "Missing XML name for: $tCol");
    } # testBib2XmlCoversEveryLoCol

    public function testBibType2Xml() {
        $this->assertIsArray(Map::uBibType2Xml());
        $this->assertSame("article", Map::uBibType2Xml(0));
        $this->assertSame("book", Map::uBibType2Xml(1));
        $this->assertSame("misc", Map::uBibType2Xml(10));
        $this->assertSame("www", Map::uBibType2Xml(16));
        $this->assertSame("www", Map::uBibType2Xml(99));
    } # testBibType2Xml

    # ----------------------------------------
    # DB functions, with cgNoExec on

    public function testExecSqlWithNoExec() {
        # With mNoExec set, no SQL is sent and mPdo is never used.
        $tDb = new Db(null, uTestConf());

        $this->assertTrue($tDb->uExecSql("select 1", __FILE__, __METHOD__, __LINE__));
    } # testExecSqlWithNoExec

    public function testExecSqlWithDebug() {
        $tConf = uTestConf();
        $tConf["cgDebug"] = 1;
        $tDb = new Db(null, $tConf);

        $tOut = uTestCapture(function () use ($tDb) {
            $tDb->uExecSql("select 1", __FILE__, __METHOD__, __LINE__);
        });

        $this->assertStringContainsString("select 1", $tOut);
    } # testExecSqlWithDebug

    public function testAtNamesTheCaller() {
        $tDb = new Db(null, uTestConf());

        $this->assertSame(" [UtilTest.php:MyClass::fMine:42]",
            $tDb->uAt("/some/where/UtilTest.php", "MyClass::fMine", 42));
    } # testAtNamesTheCaller

    public function testErrInfoWithNoHandle() {
        $tDb = new Db(null, uTestConf());

        $this->assertSame("", $tDb->uErrInfo());
    } # testErrInfoWithNoHandle

    public function testRenameTableWithNoExec() {
        $tDb = new Db(null, uTestConf());

        $tName = $tDb->uRenameTable("lo", __FILE__, __METHOD__, __LINE__);

        $this->assertMatchesRegularExpression(
            '/^lo_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}$/', $tName);
    } # testRenameTableWithNoExec

    public function testFromConfNeedsPassCache() {
        # cgDbPassCache points at a file that does not exist.
        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/cgDbPassCache/');

        Db::uFromConf(uTestConf());
    } # testFromConfNeedsPassCache

    # ----------------------------------------
    # File functions

    public function testPackFileWithNoExec() {
        $tUtil = new Util(uTestConf());

        $tOut = uTestCapture(function () use ($tUtil) {
            $tUtil->uPackFile("example.odt", "content");
        });

        $this->assertStringContainsString("No changes to example.odt", $tOut);
        $this->assertStringContainsString("content", $tOut);
    } # testPackFileWithNoExec

    public function testUnpackFile() {
        # uUnpackFile shells out to unzip, so it needs a small .odt in
        # test/sample/ before it can be tested. Note that unzip's stderr
        # is not redirected, so a failed unpack writes to the terminal
        # as well as throwing.
        $this->markTestIncomplete("uUnpackFile needs a sample .odt in test/sample/");

        global $cgDirSample;
        $tConf = uTestConf();
        $tConf["cgNoExec"] = 0;
        $tUtil = new Util($tConf);

        $tUtil->uUnpackFile("$cgDirSample/sample.odt", "content");
        $this->assertFileExists($tConf["cgDirTmp"] . "/content.xml");
    } # testUnpackFile

    # ----------------------------------------
    # XML helpers

    private function tWriteXml($pBody) {
        global $cgDirTmpTest;

        $tPath = "$cgDirTmpTest/util-test.xml";
        file_put_contents($tPath,
            '<?xml version="1.0" encoding="UTF-8"?>' .
            '<office:document-content' .
            ' xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"' .
            ' xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0">' .
            $pBody . '</office:document-content>');
        return $tPath;    # ---------->
    } # tWriteXml

    public function testLoadXml() {
        $tPath = $this->tWriteXml('<office:body><text:p>hi</text:p></office:body>');
        $tDoc = Xml::uLoadXml($tPath);

        $this->assertInstanceOf(DOMDocument::class, $tDoc);
        $this->assertSame("office:document-content", $tDoc->documentElement->nodeName);
    } # testLoadXml

    public function testLoadXmlThrowsOnMissingFile() {
        global $cgDirTmpTest;

        $this->expectException(Exception::class);
        Xml::uLoadXml("$cgDirTmpTest/no-such-file.xml");
    } # testLoadXmlThrowsOnMissingFile

    public function testLoadXmlThrowsOnBadXml() {
        global $cgDirTmpTest;

        # The libxml message goes into the exception, not to stderr.
        $tPath = "$cgDirTmpTest/util-test-bad.xml";
        file_put_contents($tPath, "<a><b></a>");

        $this->expectException(Exception::class);
        Xml::uLoadXml($tPath);
    } # testLoadXmlThrowsOnBadXml

    public function testSaveXmlRoundTrips() {
        global $cgDirTmpTest;

        $tPath = $this->tWriteXml('<office:body><text:p>hi</text:p></office:body>');
        $tOut = "$cgDirTmpTest/util-test-out.xml";
        @unlink($tOut);

        Xml::uSaveXml(Xml::uLoadXml($tPath), $tOut);

        $this->assertFileExists($tOut);
        $this->assertSame(Xml::uLoadXml($tPath)->C14N(), Xml::uLoadXml($tOut)->C14N());
    } # testSaveXmlRoundTrips

    public function testFindElementIgnoresThePrefix() {
        # local-name() is used, so "text:p" is found as "p".
        $tDoc = Xml::uLoadXml($this->tWriteXml(
            '<office:body><text:p text:style-name="P1">hi</text:p></office:body>'));
        $tNode = Xml::uFindElement($tDoc, "p");

        $this->assertNotNull($tNode);
        $this->assertSame("P1", $tNode->getAttribute("text:style-name"));
    } # testFindElementIgnoresThePrefix

    public function testFindElementReturnsNullWhenAbsent() {
        $tDoc = Xml::uLoadXml($this->tWriteXml('<office:body><text:p>hi</text:p></office:body>'));
        $this->assertNull(Xml::uFindElement($tDoc, "bibliography-source"));
    } # testFindElementReturnsNullWhenAbsent

    public function testFindElementReturnsTheFirstOne() {
        $tDoc = Xml::uLoadXml($this->tWriteXml(
            '<office:body><text:p>one</text:p><text:p>two</text:p></office:body>'));
        $this->assertSame("one", Xml::uFindElement($tDoc, "p")->textContent);
    } # testFindElementReturnsTheFirstOne

    public function testFindElementList() {
        $tDoc = Xml::uLoadXml($this->tWriteXml(
            '<office:body><text:p>one</text:p><text:p>two</text:p></office:body>'));
        $tList = Xml::uFindElementList($tDoc, "p");

        # An array, not a DOMNodeList, so nodes can be replaced while walking.
        $this->assertIsArray($tList);
        $this->assertCount(2, $tList);
        $this->assertSame("two", $tList[1]->textContent);
    } # testFindElementList

    public function testFindElementListIsEmptyWhenAbsent() {
        $tDoc = Xml::uLoadXml($this->tWriteXml('<office:body><text:p>hi</text:p></office:body>'));
        $this->assertSame(array(), Xml::uFindElementList($tDoc, "bibliography-mark"));
    } # testFindElementListIsEmptyWhenAbsent

} # UtilTest
