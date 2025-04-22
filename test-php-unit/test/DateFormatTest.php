<?php
use PHPUnit\Framework\TestCase;
#use PHPUnit/Framework/TestCase;

require_once __DIR__ . '/../fmt-date.php';

class DateFormatTest extends TestCase {

    private $sampleTime = 0;

    protected function setUp(): void {
        // Fixed datetime: Feb 27, 2024 15:34:18
        $this->sampleTime = mktime(15, 34, 18, 2, 27, 2024);
    }

    public function testIsoFormat() {
        $this->assertEquals("2024-02-27_15-34-18", uDate($this->sampleTime, "iso"));
    }

    public function testHumanFormat() {
        $this->assertEquals("Tuesday February 27, 2024 3:34:18 PM", uDate($this->sampleTime, "human"));
    }

    public function testLongFormat() {
        $this->assertEquals("Tuesday February 27, 2024 15:34:18", uDate($this->sampleTime, "long"));
    }

    public function testShortFormat() {
        $this->assertEquals("Tue Feb 27, 2024 15:34:18", uDate($this->sampleTime, "short"));
    }

    public function testFallbackFormatForUnknownStyle() {
        $this->assertEquals("2024-02-27_15-34-18", uDate($this->sampleTime, "xx"));
    }

    public function testCaseInsensitiveStyle() {
        $this->assertEquals("2024-02-27_15-34-18", uDate($this->sampleTime, "ISO")); // Case insensitivity
    }
}
?>
