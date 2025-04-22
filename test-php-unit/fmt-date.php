<?php

# Given this php function

function uDate($pTime, $pStyle = "iso") {
    # iso - 2004-02-12_15-19-21
    # min - 02-12_15-19
    # num - 20040212151921
    # ymd - 2004-02-12

    $tFmt = array(
        "iso"=>"Y-m-d_H-i-s",
        "min"=>"m-d_H-i",
        "n"=>"YmdHis",
        "num"=>"YmdHis",
        "ymd"=>"Y-m-d",
        "human"=>"l F j, Y g:i:s A",
        "long"=>"l F j, Y H:i:s",
        "short"=>"D M j, Y H:i:s",
    );

    $pStyle = strtolower($pStyle);
    if (array_key_exists($pStyle, $tFmt))
        return date($tFmt[$pStyle], $pTime);
    return date($tFmt["iso"], $pTime);
}

# Replace the following with phpunit.phar unit tests

$tStr = uDate(mktime(15,34,18, 2,27,2024), "iso");
#echo $tStr, "\n";
if ($tStr != "2024-02-27_15-34-18") echo "error\n";

$tStr = uDate(mktime(15,34,18, 2,27,2024), "human");
if ($tStr != "Tuesday February 27, 2024 3:34:18 PM") echo "error\n";

$tStr = uDate(mktime(15,34,18, 2,27,2024), "long");
if ($tStr != "Tuesday February 27, 2024 15:34:18") echo "error\n";

$tStr = uDate(mktime(15,34,18, 2,27,2024), "short");
if ($tStr != "Tue Feb 27, 2024 15:34:18") echo "error\n";

$tStr = uDate(mktime(15,34,18, 2,27,2024), "xx");
if ($tStr != "2024-02-27_15-34-18") echo "error\n";

$tStr = uDate(mktime(15,34,18, 2,27,2024), "xx");
if ($tStr != "2024-02-27_15-34-18") echo "error\n";

?>
