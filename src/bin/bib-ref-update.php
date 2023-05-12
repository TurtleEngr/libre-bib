#!/usr/bin/php
<?php

# -----------------------------
function fusage() {
    global $argc;
    global $argv;
    
    system("pod2text $argv[0]");
    exit(1);    # ---------->

/* ...

=pod

=head1 NAME

bib-ref-update.php - update bib refs into Libreoffice odt document

=head1 SYNOPSIS

 ./bib-ref-update.php [-c Conf.php] -f File.odt [-t Table]
                      [-n] [-v] [-d] [-V] [-h]

=head1 DESCRIPTION

First run bib-ref-new.php.

bib-ref-update.php will update biblio entries that may have changed in
the bib DB.

The existing biblio entries will be replace with new entries from the
bib DB. There is no comparison to see if things are different. It just
replaces, even things are the same.

=head1 OPTIONS

=over 4

=item B<-c Conf.php>

Default: config/conf.php

This is the connection information and DB that the Table is in.

Define these vars:

 $gDBName = "biblio_db";
 $gHost = "127.0.0.1";
 $gPassHint = "b4n";
 $gPassCache = ".pass.tmp";
 $gPortLocal = "3306";
 $gPortRemote = "3308";
 $gUserName = "bruce";
 $gDsn = "mysql:dbname=biblio_db;host=127.0.0.1;port=3308;charset=UTF8";

=item B<-f File.odt>

Source lo schema table to be copied. Required.

=item B<-t Table>

Bibliography table. Default: bib

Non-empty columns will be added to File.odt, where a tag {REF} matches
the Id in the DB.

=item B<-n> - noexecute

If defined, the script will run everything it can, but not execute any
write operations. For example File.odt will not be changed.

=item B<-v> - verbose

Verbose output.

=item B<-d> - debug

Turn debug code on.

=item B<-V> - version

Output the version for this script.

=item B<-h> - help

This help.

=back

=head1 RETURN VALUE

  0 - OK
 !0 - Errors

=head1 ERRORS

If you see "Error:" messages, the script failed. Fix the errors
and maybe restore from backed up files or DB, then try again. 

Does the conf file exist?

Values in the conf file?

Is the ssh tunnel setup?

Does the DB exist?

Does the user have grants needed to access DB and it's tables?

Do expected files exist?

=for comment =head1 EXAMPLES

=for comment =head1 ENVIRONMENT

=head1 FILES

.pass.tmp, config/conf.php, config/mkconf.sh bin/util.php

=head1 SEE ALSO

Makefile, /usr/local/bin/mkver.pl

=for comment =head1 NOTES

=for comment =head1 CAVEATS

=for comment =head1 DIAGNOSTICS

=for comment =head1 BUGS

=for comment =head1 RESTRICTIONS

=for comment =head1 AUTHOR

=head1 HISTORY

 Version:
 $Revision: 1.2 $ $Date: 2023/05/12 02:46:39 $ GMT 

=cut

... */
} # fUsage

# -----------------------------
function fCleanUp() {
        echo "\n";
        return;    # ---------->
} # fCleanUp

# -----------------------------
function fGetOps() {
    global $argc;
    global $argv;
    global $gpConf;
    global $gpDebug;
    global $gpFile;
    global $gpHelp;
    global $gpNoExec;
    global $gpTable;
    global $gpVerbose;

    $gpConf = "config/conf.php";
    $gpDebug = false;
    $gpFile = "";
    $gpHelp = false;
    $gpNoExec = false;
    $gpTable = "bib";
    $gpVerbose = false;
    $gpVersion = 0;

    $tOpt = getopt("c:f:t:ndvVh");
    
    if (isset($tOpt['c']))
        $gpConf = $tOpt['c'];

    if (isset($tOpt['f']))
        $gpFile = $tOpt['f'];
        
    if (isset($tOpt['t']))
        $gpTable = $tOpt['t'];
        
    $gpNoExec = isset($tOpt['n']);
    $gpDebug = isset($tOpt['d']);
    $gpVerbose = isset($tOpt['v']);
    $gpVersion = isset($tOpt['V']);
    $gpHelp = isset($tOpt['h']);

    if ($gpHelp or $argc < 2)
        fUsage();
        
    if ($gpVersion) {
        echo '$Revision: 1.2 $ $Date: 2023/05/12 02:46:39 $ GMT'
            . " [" . __LINE__ . "]\n";
        exit(2);    # ---------->
    }

    if ($gpDebug)
        echo "Debug is on. [" . __LINE__ . "]\n";
    if ($gpNoExec)
        echo "NoExec is on. [" . __LINE__ . "]\n";

    return; # ---------->
} # fGetOps

# -----------------------------
function fValidate() {
    global $gDb;
    global $gDsn;
    global $gPassCache;
    global $gPassword;
    global $gUserName;
    global $gpConf;
    global $gpDebug;
    global $gpFile;
    global $gpTable;
    global $gpVerbose;

    if ("$gpConf" == "")
        throw new Exception("Error: Missing -c option. [" . __LINE__ . "]");

    if (! file_exists("$gpConf"))
        throw new Exception("Error: Bad -c option. [" . __LINE__ . "]");
    require_once($gpConf);

    if (! file_exists("bin/util.php"))
        throw new Exception("Error: Missing bin/util.php [" . __LINE__ . "]");
    require_once("bin/util.php");

    if ("$gpFile" == "")
        throw new Exception("Error: Missing -f option [" . __LINE__ . "]");
    
    if (! file_exists("$gpFile"))
        throw new Exception("Error: Missing -f file: $gpFile [" . __LINE__ . "]");

    if ($gpTable == "")
        throw new Exception("Error: Missing -t option . [" . __LINE__ . "]");

    if ("$gDsn" == "")
        throw new Exception("Error: Missing gDsn. [" . __LINE__ . "]");

    if ("$gPassCache" == "")
        throw new Exception("Error: Missing gPassCache. Run make connect [" . __LINE__ . "]");

    if ("$gUserName" == "")
        throw new Exception("Error: Missing gUserName. [" . __LINE__ . "]");

    if (! file_exists("$gPassCache"))
        throw new Exception("Missing: gPassCache file: $gPassCache. Run make connect [" . __LINE__ . "]");

    $gPassword = rtrim(shell_exec("/bin/bash -c 'cat $gPassCache'"));
    if ("$gPassword" == "")
        throw new Exception("Error: password is not in $gPassCache. [" . __LINE__ . "]");

    # Create database connection
    if ($gpDebug) { echo "$gDsn, $gUserName [" . __LINE__ . "]\n"; }
    $gDb = new PDO($gDsn, $gUserName, $gPassword);

    if (! fTableExists($gpTable))
        throw new Exception("Error: -t Table $gpFromTable does not exist. [" . __LINE__ . "]");
        
    return;    # ---------->
} # fValidate

# -----------------------------
function fUseTemplate($pRef) {
    global $gpDebug;
    
    $tTemplate = '
      text:bibliography-type="{BibType}"
      {BibData}>
    ';

    $tTemplate = preg_replace("/{BibType}/", $pRef['type'], $tTemplate);
    $tTemplate = preg_replace("/{BibData}/", $pRef['data'], $tTemplate);

    return $tTemplate;    # ---------->
} # fUseTemplate

# -----------------------------
function fBibLookup($pRef) {
    global $gDb;
    global $gpTable;
    global $gpDebug;

    $cBib2Xml = fBib2Xml();

    $pRef['tag'] = "";
    $pRef['data'] = "";

    $tSql = "select * from $gpTable where Identifier = '" . $pRef['id'] . "'";
    $tRecH = $gDb->prepare($tSql);
    $tRecH->execute();
    $tRow = $tRecH->fetch(PDO::FETCH_ASSOC);

    if (! $tRow) {
        # TBD, make these a "skip" option?
        if (! in_array($pRef['id'],
          array("REF", "example-01", "example-02", "example-youtube-95")))
            echo "\nWarning: " . $pRefList[$pRef]['id'] . " is not in DB. [" .
                __LINE__ . "]\n";
        return;    # ---------->
    }

    $pRef['type'] = fBibType2Xml($tRow['Type']);
    $pRef['data'] = "";

    foreach (array_keys($tRow) as $tCol) {
        if ($tRow[$tCol] == "")
            continue;
        # TBD, Make some of these a "no-include" option
        switch ($tCol) {
            case "Identifier":
            case "Type":
            case "Annote":
            case "Note":
            case "Custom1":
            case "Custom2":
            case "Custom3":
            case "Custom5":
                continue 2;
        }
        $pRef['data'] .=
            " text:" . $cBib2Xml[$tCol] . '="' . $tRow[$tCol] . '"';
    }

    return $pRef;    # ---------->
} # fBibLookup

# -----------------------------
function fGetRefId($pLine) {
    global $gpDebug;

    $tRef = array();
    $tRef['id'] = "";
    $tRef['type'] = "";
    $tRef['data'] = "";

    # Pick apart pLine to get at the Id. Just return if no match/replace.
    # <text:bibliography-mark text:identifier="{BibId}"
    
    $tId = preg_replace('/.*\:identifier="/', '', $pLine, -1, $tCount);
    if ($tCount != 1)
        return $tRef;    # ---------->

    $tId = preg_replace('/"/', '', $tId, -1, $tCount);
    if ($tCount != 1)
        return $tRef;    # ---------->

    $tRef['id'] = trim($tId);

    return $tRef;    # ---------->
} # fGetRefId

# -----------------------------
function fProcessLine($pLine) {
    global $gNumRef;
    global $gInH;
    global $gOutH;
    global $gpDebug;

    # <text:bibliography-mark text:identifier="{BibId}"
    $cPatStart = "/<text:bibliography-mark text:identifier=/";
    
    # {BibId}</text:bibliography-mark>
    $cPatEnd = "/<\/text:bibliography-mark>/";

    $cMaxCache = 28;

    $tFoundStart = preg_match($cPatStart, $pLine);

    # Always output pLine, whether found or not
    fputs($gOutH, $pLine);

    if (! $tFoundStart)
        return $pLine;    # ---------->

    if ($gpDebug) echo "Processing: $pLine\n";

    # Parse pLine to get Id. If not found, just continue.
    $tRef = fGetRefId($pLine);
    if ($tRef['id'] == "") {
        echo "Warning: No Id found for biblio entry. [" . __LINE__ . "]\n";
        return;    # ---------->
    }

    # See if Id is bib DB.
    # If found, set the tRef assoc values "type", "data"
    # If not found, set tRef['type'] = ""
    $tRef = fBibLookup($tRef);

    # If not found, in/out loop will continue to output the entry, unchanged.
    if ($tRef['type'] == "")
        return;    # ---------->

    ++$gNumRef;
    if ($gNumRef % 10 == 0)
        echo '.';
    ##if ($gpDebug and $gNumRef == 4) exit(222);    # ---------->

    # Read lines until bibliography-mark end tag is found.
    # Cache the lines, for an error state, if end tag is not found.
    $tFoundEnd = 0;
    $tCacheIn = array();
    $tCacheSize = 0;
    while (! $tFoundEnd and $tCacheSize < $cMaxCache) {
        $tLine = fgets($gInH);
        $tCacheSize = array_push($tCacheIn, $tLine);
        $tFoundEnd = preg_match($cPatEnd, $tLine);
    }
    if ($tCacheSize >= $cMaxCache) {
        echo "\nWarning: Problem parsing " . $tRef['id'] .
             "bibliography-mark end tag was not found. [" . __LINE__ . "]\n";
        # Output the saved lines. (reversed, is faster and loop is easier)
        $tCacheOut = array_reverse($tCacheIn);
        while($tLine = array_pop($tCacheOut))
            fputs($gOutH, $tLine);
        return;    # ---------->
    }

    $tLineNew = fUseTemplate($tRef);
    if ($gpDebug) echo "Updated line: $tLineNew\n";

    fputs($gOutH, $tLineNew);
    fputs($gOutH, $tLine);
    return;    # ---------->
} #fProcessLine

# -----------------------------
function fProcessFile() {
    global $gInH;
    global $gOutH;
    global $gNumRef;
    global $gpDebug;

    echo "Start processing [" . __LINE__ . "]\n";

    $gInH = fopen('content.xml', 'r');
    $gOutH = fopen('content-new.xml', 'w');

    $gNumRef = 0;
    $tNumLine = 0;
    while ($tLine = fgets($gInH)) {
        ++$tNumLine;
        fProcessLine($tLine);
    }
    echo "\nProcessed $tNumLine lines. [" . __LINE__ . "]\n";
    echo "Found $gNumRef references. [" . __LINE__ . "]\n";
    
    fclose($gInH);
    fclose($gOutH);

    return;    # ---------->
} # fProcessFile

# -----------------------------
function fUnpackFile() {
    global $gpFile;
    global $gpDebug;

    $cTidyOpt = "-m -q --tidy-mark no --break-before-br yes --indent-attributes yes --indent-spaces 2 --indent auto --input-xml yes --output-xml yes --vertical-space no --wrap 78 -xml";
    
    echo "Unpack $gpFile [" . __LINE__ . "]\n";
    shell_exec("/bin/bash -c 'unzip -o $gpFile content.xml'");
    if (! file_exists("content.xml"))
        throw new Exception("Error: Could not extract content.xml [" . __LINE__ . "]");

    # tidy content.xml
    shell_exec("/bin/bash -c 'tidy $cTidyOpt content.xml &>/dev/null'");
    
    return;    # ---------->
} # fUnpackFile

# -----------------------------
function fPackFile() {
    global $gpFile;
    global $gpDebug;
    global $gpNoExec;

    $cTidyOpt = "-m -q --tidy-mark no --break-before-br no --indent-attributes no --indent no --input-xml yes --output-xml yes --vertical-space no --wrap 0 -xml";
    
    if (! $gpNoExec) {
        echo "Backup $gpFile [" . __LINE__ . "]\n";
        shell_exec("/bin/bash -c 'cp --backup=t $gpFile $gpFile.bak'");
    }
    
    echo "Final clean-up with tidy [" . __LINE__ . "]\n";
    shell_exec("/bin/bash -c 'tidy $cTidyOpt content-new.xml'");

    # Remove newlines between tags, to remove any spaces in the text
    shell_exec("/bin/bash -c \"tr -d '\n' <content-new.xml >content.xml\"");

    if ($gpNoExec) {
        echo "Nothing done to $gpFile [" . __LINE__ . "]\n";
        echo "content-new.xml can be inspected for the changes. [" .
            __LINE__ . "]\n";
    } else {
        echo "Repack $gpFile [" . __LINE__ . "]\n";
        shell_exec("/bin/bash -c 'zip $gpFile content.xml'");
        unlink("content.xml");
        unlink("content-new.xml");
    }
    
    return;    # ---------->
} # fPackFile

# ========================================
# Includes, GetOps, Validate, ReadOnly

try {
    fGetOps();
    fValidate();
} catch(Exception $e) {
    echo "Problem with setup: " . $e->getMessage() . "\n";
    exit(3);    # ---------->
}

# ========================================
# Write section
try {
    fUnpackFile();
    fProcessFile();
    fPackFile();
} catch(Exception $e) {
    echo "Problem creating table: " . $e->getMessage() . "\n";
    exit(4);    # ---------->
}

echo "Done. [" . __LINE__ . "]\n";
exit(0);    # ---------->
?>
