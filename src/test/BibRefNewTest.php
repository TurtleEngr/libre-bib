<?php

# ========================================
# BibRefNewTest.php - tests for class BibRefNew, in src/bin/bib-ref-new.inc
#
# No DB is needed: $this->tApp->fBibLookup() caches its lookups in mBibCache, so
# seeding that cache stops it from ever reaching for mDb.
#
# Run:  src/bin/phpunit src/test
#   or: src/bin/bib -T php
# ========================================

require_once __DIR__ . "/bootstrap.php";

use PHPUnit\Framework\TestCase;

class BibRefNewTest extends TestCase {

    # The script under test. Now that every script is a class, two of
    # them can share one php process, so no process isolation is needed.
    private $tApp;

    protected function setUp(): void {
        uTestLoadScript("bib-ref-new");
        uTestResetGlobals();

        $tConf = uTestConf();
        $tConf["cgDirEtc"] = dirname(__DIR__) . "/etc";
        $this->tApp = new BibRefNew($tConf);
    } # setUp

    # ----------------------------------------
    # Helpers

    private function tMakeDoc($pBody) {
        # Smallest content.xml that declares the namespaces the template
        # uses. $pBody goes inside <office:text>.

        $tXml = '<?xml version="1.0" encoding="UTF-8"?>' .
            '<office:document-content' .
            ' xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"' .
            ' xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0">' .
            '<office:body><office:text>' . $pBody .
            '</office:text></office:body></office:document-content>';

        $tDoc = new DOMDocument();
        $tDoc->preserveWhiteSpace = true;
        $tDoc->formatOutput = false;
        $this->assertTrue($tDoc->loadXML($tXml), "Test fixture did not parse");
        return $tDoc;    # ---------->
    } # tMakeDoc

    private function tRunCites($pDoc) {
        # Run fProcessBlock() over every paragraph/heading, the way
        # $this->tApp->fProcessFile() does, and return the resulting XML.

        $tXpath = new DOMXPath($pDoc);
        $tBlockList = array();
        foreach ($tXpath->query("//*[local-name()='p' or local-name()='h']") as $tBlock)
            $tBlockList[] = $tBlock;

        $tApp = $this->tApp;
        uTestCapture(function () use ($tApp, $pDoc, $tBlockList) {
            foreach ($tBlockList as $tBlock)
                $tApp->fProcessBlock($pDoc, $tBlock);
        });

        return $pDoc->saveXML();    # ---------->
    } # tRunCites

    private function tSeedCite($pId, $pData = ' text:author="Someone"') {
        $this->tApp->mBibCache[$pId] = array(
            'id'=>$pId,
            'type'=>"book",
            'data'=>$pData,
            'loc'=>""
        );
    } # tSeedCite

    # ----------------------------------------

    public function testMethodsAreDefined() {
        foreach (array("fUsage", "fCleanUp", "fGetOps", "fValidate", "fRun",
                "fXmlValue", "fUseTemplate", "fBibLookup", "fFindCiteList",
                "fCollectTextNodeList", "fOuterNode", "fReplaceCite",
                "fProcessBlock", "fProcessFile") as $tName)
            $this->assertTrue(method_exists("BibRefNew", $tName), "Missing method: $tName");
    } # testMethodsAreDefined

    # ----------------------------------------
    # fGetOps

    public function testGetOpsDefaultsToHelpWithNoArgs() {
        $tOpt = BibRefNew::fGetOps(array("bib-ref-new.php"), 1);

        $this->assertTrue($tOpt["help"]);
        $this->assertSame("", $tOpt["test"]);
    } # testGetOpsDefaultsToHelpWithNoArgs

    public function testGetOpsReturnsEveryOption() {
        $tOpt = BibRefNew::fGetOps(array("bib-ref-new.php", "-c"), 2);

        $this->assertArrayHasKey("help", $tOpt);
        $this->assertArrayHasKey("change", $tOpt);
        $this->assertArrayHasKey("test", $tOpt);
    } # testGetOpsReturnsEveryOption

    public function testCleanUp() {
        $tApp = $this->tApp;
        $tOut = uTestCapture(function () use ($tApp) { $tApp->fCleanUp(); });
        $this->assertSame("\n", $tOut);
    } # testCleanUp

    # ----------------------------------------
    # fXmlValue

    public function testXmlValueEscapes() {
        $this->assertSame("Smith &amp; Sons", BibRefNew::fXmlValue("Smith & Sons"));
        $this->assertSame("say &quot;hi&quot;", BibRefNew::fXmlValue('say "hi"'));
        $this->assertSame("a &lt;b&gt; c", BibRefNew::fXmlValue("a <b> c"));
    } # testXmlValueEscapes

    public function testXmlValueLeavesPlainTextAlone() {
        $this->assertSame("Artymiak, Jacek", BibRefNew::fXmlValue("Artymiak, Jacek"));
    } # testXmlValueLeavesPlainTextAlone

    # ----------------------------------------
    # fUseTemplate

    public function testUseTemplateFillsEveryField() {
        $tXml = $this->tApp->fUseTemplate(array(
            'id'=>"GAS00",
            'type'=>"book",
            'data'=>' text:author="Gaskell, Philip"',
            'loc'=>":p22"
        ));

        $this->assertStringContainsString('text:identifier="GAS00"', $tXml);
        $this->assertStringContainsString('text:bibliography-type="book"', $tXml);
        $this->assertStringContainsString('text:author="Gaskell, Philip"', $tXml);
        $this->assertStringContainsString(">GAS00</text:bibliography-mark>", $tXml);
        $this->assertStringContainsString(":p22}", $tXml);

        # No {Bib...} name is left behind.
        $this->assertDoesNotMatchRegularExpression('/\{Bib[A-Za-z]+\}/', $tXml);
    } # testUseTemplateFillsEveryField

    public function testUseTemplateHasNoTrailingNewline() {
        # A trailing newline in etc/cite-new.xml would land in the
        # document as text.
        $tXml = $this->tApp->fUseTemplate(array('id'=>"A", 'type'=>"book", 'data'=>"", 'loc'=>""));
        $this->assertSame(rtrim($tXml), $tXml);
    } # testUseTemplateHasNoTrailingNewline

    public function testUseTemplateEscapesTheId() {
        $tXml = $this->tApp->fUseTemplate(array(
            'id'=>"A&B", 'type'=>"book", 'data'=>"", 'loc'=>""));
        $this->assertStringContainsString('text:identifier="A&amp;B"', $tXml);
        $this->assertStringNotContainsString('"A&B"', $tXml);
    } # testUseTemplateEscapesTheId

    # ----------------------------------------
    # fBibLookup

    public function testBibLookupSkipsDocumentationIds() {
        # These never reach the DB, so $gDb stays null.
        foreach (array("REF", "example-01", "example-02", "example-youtube-95") as $tId)
            $this->assertFalse($this->tApp->fBibLookup($tId), "$tId should be skipped");
    } # testBibLookupSkipsDocumentationIds

    public function testBibLookupUsesTheCache() {
        $this->tSeedCite("GAS00");
        $tCite = $this->tApp->fBibLookup("GAS00");

        $this->assertIsArray($tCite);
        $this->assertSame("GAS00", $tCite['id']);
        $this->assertSame("book", $tCite['type']);
    } # testBibLookupUsesTheCache

    # ----------------------------------------
    # fProcessCite

    public function testProcessCiteInlineInAParagraph() {
        $this->tSeedCite("eisenstein-12");
        $tDoc = $this->tMakeDoc('<text:p>Some text.{eisenstein-12}</text:p>');
        $tXml = $this->tRunCites($tDoc);

        $this->assertStringContainsString("Some text.", $tXml);
        $this->assertStringContainsString('text:identifier="eisenstein-12"', $tXml);
        $this->assertStringNotContainsString("{eisenstein-12}", $tXml);
        $this->assertSame(1, $this->tApp->mNumCite);
    } # testProcessCiteInlineInAParagraph

    public function testProcessCiteKeepsTheLocation() {
        $this->tSeedCite("GAS00");
        $tDoc = $this->tMakeDoc('<text:p>Quote{GAS00:p22}</text:p>');
        $tXml = $this->tRunCites($tDoc);

        $this->assertStringContainsString('text:identifier="GAS00"', $tXml);
        $this->assertStringContainsString(":p22}", $tXml);
    } # testProcessCiteKeepsTheLocation

    public function testProcessCiteReplacesTheWholeSpan() {
        # The cite fills the span, so the T2 character style goes away.
        $this->tSeedCite("GAS00");
        $tDoc = $this->tMakeDoc(
            '<text:p>Quote<text:span text:style-name="T2">{GAS00:p22}</text:span></text:p>');
        $tXml = $this->tRunCites($tDoc);

        $this->assertStringNotContainsString('"T2"', $tXml);
        $this->assertStringContainsString('text:identifier="GAS00"', $tXml);
        $this->assertStringContainsString("Quote", $tXml);
    } # testProcessCiteReplacesTheWholeSpan

    public function testProcessCiteKeepsASpanItDoesNotFill() {
        # Here the span holds more than the cite, so the span stays.
        $this->tSeedCite("GAS00");
        $tDoc = $this->tMakeDoc(
            '<text:p><text:span text:style-name="T2">see {GAS00}</text:span></text:p>');
        $tXml = $this->tRunCites($tDoc);

        $this->assertStringContainsString('"T2"', $tXml);
        $this->assertStringContainsString("see ", $tXml);
        $this->assertStringContainsString('text:identifier="GAS00"', $tXml);
    } # testProcessCiteKeepsASpanItDoesNotFill

    public function testProcessCiteHandlesTwoCitesInOneNode() {
        $this->tSeedCite("aaa-01");
        $this->tSeedCite("bbb-02");
        $tDoc = $this->tMakeDoc('<text:p>a{aaa-01} and b{bbb-02}.</text:p>');
        $tXml = $this->tRunCites($tDoc);

        $this->assertStringContainsString('text:identifier="aaa-01"', $tXml);
        $this->assertStringContainsString('text:identifier="bbb-02"', $tXml);
        $this->assertStringContainsString(" and b", $tXml);
        $this->assertStringContainsString(".", $tXml);
        $this->assertSame(2, $this->tApp->mNumCite);
    } # testProcessCiteHandlesTwoCitesInOneNode

    public function testProcessCiteLeavesUnknownIdAlone() {
        $this->tApp->mBibCache["no-such-99"] = false;
        $tDoc = $this->tMakeDoc('<text:p>text{no-such-99}</text:p>');
        $tXml = $this->tRunCites($tDoc);

        $this->assertStringContainsString("{no-such-99}", $tXml);
        $this->assertSame(0, $this->tApp->mNumCite);
    } # testProcessCiteLeavesUnknownIdAlone

    public function testProcessCiteLeavesDocumentationRefAlone() {
        # example.odt says "adding {REF} tags as desired" in its prose.
        $tDoc = $this->tMakeDoc('<text:p>adding {REF} tags as desired</text:p>');
        $tXml = $this->tRunCites($tDoc);

        $this->assertStringContainsString("{REF}", $tXml);
        $this->assertSame(0, $this->tApp->mNumCite);
    } # testProcessCiteLeavesDocumentationRefAlone

    public function testProcessCiteIgnoresAnAlreadyConvertedCite() {
        # A converted cite holds its braces in separate one-char spans,
        # and its Id is plain text inside the mark. Nothing should match.
        $this->tSeedCite("ARJ00");
        $tBody = '<text:p><text:span text:style-name="Endnote_20_Symbol">{</text:span>' .
            '<text:span text:style-name="Endnote_20_Symbol">' .
            '<text:bibliography-mark text:identifier="ARJ00"' .
            ' text:bibliography-type="book">ARJ00</text:bibliography-mark>' .
            '</text:span>' .
            '<text:span text:style-name="Endnote_20_Symbol">}</text:span></text:p>';
        $tDoc = $this->tMakeDoc($tBody);
        $tXml = $this->tRunCites($tDoc);

        $this->assertSame(0, $this->tApp->mNumCite);
        $this->assertSame(1, substr_count($tXml, "<text:bibliography-mark"));
    } # testProcessCiteIgnoresAnAlreadyConvertedCite

    public function testProcessCiteIgnoresEmptyBraces() {
        $tDoc = $this->tMakeDoc('<text:p>a {} b {:p22} c</text:p>');
        $tXml = $this->tRunCites($tDoc);

        $this->assertStringContainsString("{}", $tXml);
        $this->assertStringContainsString("{:p22}", $tXml);
        $this->assertSame(0, $this->tApp->mNumCite);
    } # testProcessCiteIgnoresEmptyBraces

    public function testProcessCiteOutputStaysValidXmlWithOddDbValues() {
        # A title with '&' or '"' used to build a broken attribute.
        $this->tSeedCite("odd-01",
            ' text:title="Tom &amp; Jerry: the &quot;best&quot; bits"');
        $tDoc = $this->tMakeDoc('<text:p>x{odd-01}</text:p>');
        $tXml = $this->tRunCites($tDoc);

        $tCheck = new DOMDocument();
        $this->assertTrue($tCheck->loadXML($tXml), "Result is not valid XML");
        $this->assertStringContainsString("Tom &amp; Jerry", $tXml);
    } # testProcessCiteOutputStaysValidXmlWithOddDbValues

    # ----------------------------------------
    # fProcessFile

    public function testProcessFileWritesContentNewXml() {
        $tDirTmp = $this->tApp->mDirTmp;

        $this->tSeedCite("eisenstein-10");
        $tIn = $this->tMakeDoc(
            '<text:p>Citations can also be used inline.{eisenstein-10}</text:p>');
        file_put_contents("$tDirTmp/content.xml", $tIn->saveXML());
        @unlink("$tDirTmp/content.new.xml");

        $tApp = $this->tApp;
        $tOut = uTestCapture(function () use ($tApp) { $tApp->fProcessFile(); });

        $this->assertFileExists("$tDirTmp/content.new.xml");
        $tXml = file_get_contents("$tDirTmp/content.new.xml");
        $this->assertStringContainsString('text:identifier="eisenstein-10"', $tXml);
        $this->assertStringNotContainsString("{eisenstein-10}", $tXml);
        $this->assertStringContainsString("Replaced 1 citations.", $tOut);

        $tCheck = new DOMDocument();
        $this->assertTrue($tCheck->loadXML($tXml), "content.new.xml is not valid XML");
    } # testProcessFileWritesContentNewXml

    public function testProcessFileThrowsOnBadXml() {
        $tDirTmp = $this->tApp->mDirTmp;

        file_put_contents("$tDirTmp/content.xml", "<a><b></a>");

        $this->expectException(Exception::class);
        $tApp = $this->tApp;
        uTestCapture(function () use ($tApp) { $tApp->fProcessFile(); });
    } # testProcessFileThrowsOnBadXml

} # BibRefNewTest
