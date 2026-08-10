<?php

# ========================================
# CallsTest.php - every call in bin/ resolves to something that exists
#
# The refactor turned functions into methods. A call that was missed
# still parses, so php only complains when that line is reached at run
# time: uBibType2Xml() in fBibLookup() needed a DB row with a Type
# before it would fail, which is how it got as far as a release.
#
# This walks php's own token stream, so a name in a comment or a string
# cannot give a false hit the way a grep does.
#
# Run:  src/bin/phpunit src/test
# ========================================

require_once __DIR__ . "/bootstrap.php";

use PHPUnit\Framework\TestCase;

class CallsTest extends TestCase {

    # --------------------
    private function tFileList() {
        # Every php file in bin/ and test/, except the third party
        # tidier and this test.

        global $cgDirBin;

        $tList = array_merge(glob("$cgDirBin/*.inc"), glob("$cgDirBin/*.php"),
            glob(__DIR__ . "/*.php"));

        return array_values(array_filter($tList, function ($pFile) {
            return ! in_array(basename($pFile), array("phptidy.php", "CallsTest.php"));
        }));    # ---------->
    } # tFileList

    # --------------------
    private function tMethodMap() {
        # Map every method defined in bin/ to the class it belongs to.
        # util.php holds four classes, so the file is walked line by
        # line to keep track of which class is open.

        global $cgDirBin;

        $tMap = array();
        foreach (array_merge(glob("$cgDirBin/*.inc"), array("$cgDirBin/util.php")) as $tFile) {
            $tClass = "";
            foreach (file($tFile) as $tLine) {
                if (preg_match('/^class (\w+)/', $tLine, $tMatch))
                    $tClass = $tMatch[1];
                if (preg_match('/^\s*public (?:static )?function (\w+)\(/', $tLine, $tMatch))
                    $tMap[$tMatch[1]][$tClass] = $tClass;
            }
        }

        return $tMap;    # ---------->
    } # tMethodMap

    # --------------------
    private function tBareCalls($pFile) {
        # Return every NAME( in pFile that is not ->NAME(, Class::NAME(,
        # new NAME( or a definition, as array("line"=>, "name"=>).

        $tSkip = array(T_WHITESPACE, T_COMMENT, T_DOC_COMMENT);
        $tCalled = array(T_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION, T_NEW,
            T_NULLSAFE_OBJECT_OPERATOR);

        $tFound = array();
        $tToken = token_get_all(file_get_contents($pFile));
        $tCount = count($tToken);

        for ($tI = 0; $tI < $tCount; ++$tI) {
            if ( ! is_array($tToken[$tI]) or $tToken[$tI][0] != T_STRING)
                continue;

            # A call is a name followed by "(" ...
            $tNext = "";
            for ($tJ = $tI + 1; $tJ < $tCount; ++$tJ) {
                if (is_array($tToken[$tJ]) and in_array($tToken[$tJ][0], $tSkip))
                    continue;
                $tNext = $tToken[$tJ];
                break;
            }
            if ($tNext !== "(")
                continue;

            # ... and the token before it says how it was called.
            $tPrev = "";
            for ($tJ = $tI - 1; $tJ >= 0; --$tJ) {
                if (is_array($tToken[$tJ]) and in_array($tToken[$tJ][0], $tSkip))
                    continue;
                $tPrev = $tToken[$tJ];
                break;
            }
            if (is_array($tPrev) and in_array($tPrev[0], $tCalled))
                continue;

            $tFound[] = array("line"=>$tToken[$tI][2], "name"=>$tToken[$tI][1]);
        }

        return $tFound;    # ---------->
    } # tBareCalls

    # ----------------------------------------

    public function testNoUnqualifiedMethodCalls() {
        # A method called as a plain function: the call was missed when
        # its function became a method.

        $tMap = $this->tMethodMap();
        $tBad = array();

        foreach ($this->tFileList() as $tFile)
            foreach ($this->tBareCalls($tFile) as $tCall)
                if (isset($tMap[$tCall["name"]])) {
                    $tClass = implode("|", array_keys($tMap[$tCall["name"]]));
                    $tBad[] = basename($tFile) . ":" . $tCall["line"] . " " .
                        $tCall["name"] . "() should be $tClass::" . $tCall["name"] . "()";
                }

        $this->assertSame(array(), $tBad, "\n" . implode("\n", $tBad) . "\n");
    } # testNoUnqualifiedMethodCalls

    public function testEveryCallResolves() {
        # Catches the rest: a call to a name that is neither a php
        # builtin, nor a method, nor a function defined in the project.

        $tKnown = get_defined_functions()["internal"];
        foreach ($this->tFileList() as $tFile) {
            $tText = file_get_contents($tFile);
            preg_match_all('/^\s*(?:public (?:static )?)?function (\w+)\(/m', $tText, $tMatch);
            $tKnown = array_merge($tKnown, $tMatch[1]);
        }
        $tKnown = array_flip($tKnown);

        $tBad = array();
        foreach ($this->tFileList() as $tFile)
            foreach ($this->tBareCalls($tFile) as $tCall)
                if ( ! isset($tKnown[$tCall["name"]]))
                    $tBad[] = basename($tFile) . ":" . $tCall["line"] . " " .
                        $tCall["name"] . "() is not defined anywhere";

        $this->assertSame(array(), $tBad, "\n" . implode("\n", $tBad) . "\n");
    } # testEveryCallResolves

} # CallsTest
