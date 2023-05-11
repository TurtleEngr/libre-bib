#!/usr/bin/php
<?php

echo "TBD, add table options for lib, lo
exit(1)

# -----------------------------
function fusage() {
    global $argc;
    global $argv;
    
    system("pod2text $argv[0]");
    exit(1);

/* ...

=pod

=head1 NAME

update-lib-2-lo.php - Using a join table update lo from db

=head1 SYNOPSIS

 update-lib-2-lo.php [-c Conf.php] [-b] [-n] [-v] [-d] [-h]

=head1 DESCRIPTION

First create the lib and lo tables in the DB.

=head1 OPTIONS

=over 4

=item B<-c Conf.php>

Define these vars:

 $gDBName = "biblio_db";
 $gHost = "127.0.0.1";
 $gPassHint = "b4n";
 $gPassCache = ".pass.tmp";
 $gPortLocal = "3306";
 $gPortRemote = "3308";
 $gUserName = "bruce";
 $gDsn = "mysql:dbname=biblio_db;host=127.0.0.1;port=3308;charset=UTF8";

=item B<-n> - noexecute

Not imp.

If defined, the script will run everything it can, but not execute any
write operations.

=item B<-v> - verbose

Not imp.

Verbose output.

=item B<-d> - debug

Turn debug code on.

=item B<-h> - help

This help.

=back

=for comment =head1 RETURN VALUE
=head1 ERRORS

Does the conf file exist?

Values i the conf file?

Do expected files exist?

Is the ssh tunnel setup?

Does the DB exist?

Does the user have grants needed to access DB and it's tables?

=for comment =head1 EXAMPLES

=head1 ENVIRONMENT

gpConf - base configuration file, for global variables.

=head1 FILES

.pass.tmp, conf.php, mkconf.sh, util.php

=head1 SEE ALSO

Makefile, mkver.pl

=head1 NOTES

 https://www.php.net/manual/en/book.pdo.php
 https://www.w3schools.com/php/

  Input Tables: lo, lib
  Creates: join_lib_lo

  lib.Media       lo.Type     lo,RepType   lo.Note(Tags)
  "Paperback"     1  Book     Paperback    book
  "Paper Book"    1  Book     Paperback    book
  "Hardcover"     1  Book     Hardcover    book
  "Ebook"         1  Book     Ebook        book, kindle, epub
  "Media"         10 Misc     Media        video, youtube, a/v
  "DVD"           10 Misc     DVD          video, DVD
  "Blu-ray"       10 Misc     Blu-ray      video, Blu-ray
  "Laserdisc"     10 Misc     Laserdisc    video, Laserdisk
  "VHS"           10 Misc     VHS          video, VHS
                  0  Article  article      article
                  16 WWW      link         link, site
=for comment =head1 CAVEATS

=for comment =head1 DIAGNOSTICS

=for comment =head1 BUGS

=for comment =head1 RESTRICTIONS

=for comment =head1 AUTHOR

=head1 HISTORY

$Revision: 1.1 $ $Date: 2023/05/11 20:16:16 $ GMT 

=cut

... */
} # fUsage

# -----------------------------
function fCleanUp() {
        echo "\n";
} # fCleanUp

# -----------------------------
function fGetOps() {
    global $argc;
    global $argv;
    global $gpBackup;
    global $gpConf;
    global $gpDebug;
    global $gpHelp;
    global $gpMap;
    global $gpNoExec;
    global $gpVerbose;

    $gpBackup = false;
    $gpConf = "conf.php";
    $gpDebug = false;
    $gpHelp = false;
    $gpNoExec = false;
    $gpVerbose = false;

    $tOpt = getopt("bc:ndvh");
    
    $gpBackup = isset($tOpt['b']);
    
    if (isset($tOpt['c'])) {
        $gpConf = $tOpt['c'];
    }
    
    $gpNoExec = isset($tOpt['n']);
    $gpDebug = isset($tOpt['d']);
    $gpVerbose = isset($tOpt['v']);
    $gpHelp = isset($tOpt['h']);

    if ($gpHelp or $argc < 2) {
        fUsage();
    }
    
    if ($gpDebug) {
        echo "Debug is on. " . __FILE__ . "[" . __LINE__ . "]\n";
    }
} # fGetOps

# -----------------------------
function fValidate() {
    global $gDb;
    global $gDsn;
    global $gHandle;
    global $gPassCache;
    global $gPassword;
    global $gUserName;
    global $gpConf;
    global $gpDebug;

    if ("$gpConf" == "") {
        throw new Exception("Error: Missing -c option. [" . __LINE__ . "]");
    }

    if (! file_exists("$gpConf")) {
        throw new Exception("Error: Bad -c option. [" . __LINE__ . "]");
    }
    require_once($gpConf);

    if (! file_exists("bin/util.php")) {
        throw new Exception("Error: Missing bin/util.php. [" . __LINE__ . "]");
    }
    require("bin/util.php");

    if ("$gDsn" == "") {
        throw new Exception("Error: Missing gDsn. [" . __LINE__ . "]");
    }
    if ("$gPassCache" == "") {
        throw new Exception("Error: Missing gPassCache. [" . __LINE__ . "]");
    }
    if ("$gUserName" == "") {
        throw new Exception("Error: Missing gUserName. [" . __LINE__ . "]");
    }

    if (! file_exists("$gPassCache")) {
        throw new Exception("Missing: gPassCache file: $gPassCache. Run make connect [" . __LINE__ . "]");
    }
    $gPassword = rtrim(shell_exec("/bin/bash -c 'cat $gPassCache'"));
    if ("$gPassword" == "") {
        throw new Exception("Error: password is not in $gPassCache. [" . __LINE__ . "]");
    }

    if ($gpBackup)
        echo "Backup is on. [". __LINE__ . "]\n";
    else
        echo "Backup is off. [". __LINE__ . "]\n";

    # Create database connection
    if ($gpDebug) { echo "$gDsn, $gUserName \n"; }
    $gDb = new PDO($gDsn, $gUserName, $gPassword);

    $tSql = "select Book_Id from lib limit 10";
    ##if ($gpDebug) { echo "$tSql \n"; }
    if ( $gDb->query($tSql) == false) {
        throw new Exception("Error: Missing lib Table. [" . __LINE__ . "]");
    }
    
    $tSql = "select Identifier from lo limit 10";
    ##if ($gpDebug) { echo "$tSql \n"; }
    if ( $gDb->query($tSql) == false) {
        throw new Exception("Error: Missing lo Table. [" . __LINE__ . "]");
    }
} # fValidate

# --------------------
class ManageBiblio {
    protected $gDbH;
    public $gDebug;

    # --------------------
    public function __construct($pDbH, $pDebug = false) {
            $this->gDbH = $pDbH;
            $this->gDebug = $pDebug;
    } # __construct

    # --------------------
    public function mkJoinTable($pDrop = false) {
        try {
            if ($pDrop)
                $this->gDbH->query("drop table join_lib_lo");
            $tSql = "create table IF NOT EXISTS join_lib_lo (
                select
                    lib.Book_Id,
                    lo.Identifier,
                    lo.Booktitle,
                    lib.Media
                from lib, lo
                where left(lib.Title,40) =
                      left(lo.Booktitle,40)
            )";
            if ($this->gDebug) print_r("Sql = $tSql\n");
            $this->gDbH->query($tSql);
            $this->gDbH->query("alter table join_lib_lo
                add primary key (Book_Id,Identifier)");
        } catch(PDOException $e) {
            die("Problem in mkJoinTable: " . $e->getMessage());
        }
    } # mkJoinTable

    # --------------------
    public function backup($pTable) {
        global $gpBackup;

        if ($gpBackup and fTableExists($pTable))
            $gBackupName = fRenameTable($pTable);
    } # backup

    # --------------------
    protected function updateLoBook($pLoResult) {
        global $gpNoExec;
                
        # Author, Custom2, Custom3, ISBN, Publisher, RepType, Type, Year
        $tSql = "update lo set
            Author    = '" . $pLoResult['Author']  . "',
            Custom2   = '" . $pLoResult['Custom2'] . "',
            Custom3   = '" . $pLoResult['Custom3'] . "',
            ISBN      = '" . $pLoResult['ISBN']    . "',
            Publisher = '" . $pLoResult['Publisher'] . "',
            RepType   = '" . $pLoResult['RepType'] . "',
            Year      = '" . $pLoResult['Year']    . "'
            where Identifier = '" . $pLoResult['Identifier'] . "'";
        if ($this->gDebug) print_r("$tSql \n");
        if (! $gpNoExec)
            $this->gDbH->query($tSql);
    } # updateLoBook

    # --------------------
    protected function replaceBlanksInBooks($pLoResult, $pLibResult) {
        $tMedia2RepType = fMedia2RepType();
        
        # Replace empty values in tLoResult
        # Author,         Custom2,          Publisher,   Year, RepType, ISBN, Costom3
        # Primary_Author, Secondary_Author, Publication, Date, Media,   ISBN, Work_id
        if ($pLoResult['Author'] == '') {
            $pLoResult['Author'] = $pLibResult['Primary_Author'];
        }
        if ($pLoResult['Custom2'] == '') {
            $pLoResult['Custom2'] =
                preg_replace('/[|]/', '; ', $pLibResult['Secondary_Author']);
        }
        if ($pLoResult['Publisher'] == '') {
            $pLoResult['Publisher'] = $pLibResult['Publication'];
        }
        if ($pLoResult['Year'] == '') {
            $pLoResult['Year'] = $pLibResult['Date'];
        }
        if ($pLibResult['Media'] != '') {
            $tTmp = $pLibResult['Media'];
            $pLoResult['RepType'] = $tMedia2RepType[strtolower("$tTmp")];
        }
        if ($pLoResult['ISBN'] == '') {
            $pLoResult['ISBN'] = preg_replace('/[\[\]]/', '',
                $pLibResult['ISBN']);
        }
        if ($pLoResult['Custom3'] == '') {
            $pLoResult['Custom3'] = $pLibResult['Work_id'];
        }
        return $pLoResult;
    } # replaceBlanksInBooks

    # --------------------
    protected function getLibRec($pJoinResult) {
        # Get record for Book_Id
        $tSql = "select
            Primary_Author, Secondary_Author, Publication, Date, Media, ISBN, Work_id
            from lib
            where Book_Id = '" . $pJoinResult['Book_Id'] . "'";
        $tLibH = $this->gDbH->prepare($tSql);
        $tLibH->execute();
        return $tLibH->fetch(PDO::FETCH_ASSOC);
    } # getLibRec

    # --------------------
    protected function getBookId($pLoResult, $pMedia = true) {
        # Get Book_Id for the Identifier. Prefer Ebook first.
        $tEBook = "";
        if ($pMedia) {
            $tEBook = "Media = 'Ebook'";
        }
        $tSql = "select Book_Id from join_lib_lo where
            Identifier = '" . $pLoResult['Identifier'] . "' and
            $tEBook limit 1";
        $tJoinH = $this->gDbH->prepare($tSql);
        $tJoinH->execute();
        return $tJoinH->fetch(PDO::FETCH_ASSOC);
    } # getBookId

    # --------------------
    protected function processBooks($pLoH) {
        $tCount = 0;
        while ($tLoResult = $pLoH->fetch(PDO::FETCH_ASSOC)) {
            echo ".";
            ++$tCount;
            if ($this->gDebug) echo "\n--------------------\n";
            if ($this->gDebug) print_r($tLoResult);
            
            $tJoinResult = $this->getBookId($tLoResult);
            if (! $tJoinResult)
                $tJoinResult = $this->getBookId($tLoResult, false);
            if (! $tJoinResult) {
                if ($this->gDebug) echo "Not found in join. No Problem.\n";
                continue;
            }
            
            $tLibResult = $this->getLibRec($tJoinResult);
            if ($this->gDebug) echo "Found:\n";
            if ($this->gDebug) print_r($tLibResult);

            $tLoResult = $this->replaceBlanksInBooks($tLoResult, $tLibResult);
            if ($this->gDebug) echo "Updated:\n";
            if ($this->gDebug) print_r($tLoResult);
            
            $this->updateLoBook($tLoResult);
        } # while
        echo ("\nProcessed: $tCount\n");
    } # processBooks
    
    # --------------------
    public function fixBooksWithBlanks() {
        # Get records that are books, where tColList are blank.
        try {
            $tId = "Identifier";
            $tColList = array('Author', 'Custom2', 'Publisher',
                'Year', 'RepType', 'ISBN', 'Custom3');
            $tColStr = implode(",", $tColList);
            $tColBlank = "(" . implode(" = '' or ", $tColList) . " = '')";
            $tSql = "select $tId, $tColStr from lo
                where Type = '1' and $tColBlank";
            if ($this->gDebug) print_r("Sql = $tSql\n");
            $tLoH = $this->gDbH->prepare($tSql);
            $tLoH->execute();
            $this->processBooks($tLoH);
        } catch(PDOException $e) {
            die("Problem in fixBooksWithBlanks: " . $e->getMessage());
        }
    } # fixBooksWithBlanks

    # --------------------
    protected function updateLoRec($pLoResult) {
        global $gpNoExec;

        # Author, Custom2, Custom3, ISBN, Publisher, RepType, Type, Year
        $tSql = "update lo set
            Author    = '" . $pLoResult['Author']  . "',
            Custom2   = '" . $pLoResult['Custom2'] . "',
            Custom3   = '" . $pLoResult['Custom3'] . "',
            ISBN      = '" . $pLoResult['ISBN']    . "',
            Publisher = '" . $pLoResult['Publisher'] . "',
            RepType   = '" . $pLoResult['RepType'] . "',
            Type      = '" . $pLoResult['Type']    . "',
            Year      = '" . $pLoResult['Year']    . "'
            where Identifier = '" . $pLoResult['Identifier'] . "'";
        if ($this->gDebug) print_r("$tSql \n");
        if (! $gpNoExec)
            $this->gDbH->query($tSql);
    } # updateLoRec

    # --------------------
    protected function processRecs($pRecH) {
        $tMedia2RepType = fMedia2RepType();
        $tMedia2Type = fMedia2Type();
        $tType2Txt = fType2Txt();

        $tCount = 0;
        while ($tRecResult = $pRecH->fetch(PDO::FETCH_ASSOC)) {
            echo ".";
            ++$tCount;
            if ($this->gDebug) echo "\n--------------------\n";
            $tId = $tRecResult["Identifier"];
            if ($this->gDebug) print_r("$tId\n");

            $tSql = "select lo.* from lo, lib, join_lib_lo where 
                lo.Identifier = join_lib_lo.Identifier and
                lib.Book_Id = join_lib_lo.Book_Id and
                lo.Identifier = \"$tId\"";
            $tLoH = $this->gDbH->prepare($tSql);
            $tLoH->execute();
            $tLoResult = $tLoH->fetch(PDO::FETCH_ASSOC);
            
            $tSql = "select lib.* from lo, lib, join_lib_lo where 
                lo.Identifier = join_lib_lo.Identifier and
                lib.Book_Id = join_lib_lo.Book_Id and
                lo.Identifier = \"$tId\"";
            $tLibH = $this->gDbH->prepare($tSql);
            $tLibH->execute();
            $tLibResult = $tLibH->fetch(PDO::FETCH_ASSOC);

            # Update these fields:
            # Author, Custom2, Custom3, ISBN, Publisher, RepType, Type, Year

            $tMedia = $tLibResult["Media"];
            $tLoResult["RepType"] = $tMedia2RepType[strtolower("$tMedia")];
            $tLoResult["Type"] = $tMedia2Type["$tMedia"];
            
            $tNote = $tType2Txt[$tLoResult["Type"]];
            if (preg_match("/$tNote/", $tLoResult["Note"]) == 0)
                $tLoResult["Note"] .= ", " . $tNote;

            if ($tLoResult["Author"] == "" and $tLibResult["Primary_Author"] != "")
                $tLoResult["Author"] = $tLibResult["Primary_Author"];
                
            if ($tLibResult["Secondary_Author"] != "")
                $tLoResult["Custom2"] = preg_replace('/[|]/', '; ',
                    $tLibResult['Secondary_Author']);
                    
            if ($tLibResult["Work_id"] != "")
                $tLoResult["Custom3"] = $tLibResult["Work_id"];
                
            if ($tLibResult["ISBN"] != "")
                $tLoResult["ISBN"] = preg_replace('/[\[\]]/', '', $tLibResult["ISBN"]);
                
            if ($tLibResult["Publication"] != "")
                $tLoResult["Publisher"] = $tLibResult["Publication"];
                
            if ($tLoResult["Year"] == "" and $tLibResult["Date"] != "")
                $tLoResult["Year"] = $tLibResult["Date"];
                
            $this->updateLoRec($tLoResult);
        } # while
        
        echo ("\nProcessed: $tCount\n");
    } # processRecs
    
    public function fixNonBooks() {
        try {
            $tSql = "select distinct Identifier from join_lib_lo";
            $tRecH = $this->gDbH->prepare($tSql);
            $tRecH->execute();
            $this->processRecs($tRecH);
        } catch(PDOException $e) {
            die("Problem in fixNonBooks: " . $e->getMessage());
        }
    } # fixNonBooks
} # ManageBiblio

# ****************************************
# Includes, GetOps, Validate, ReadOnly

try {
    fGetOps();
    fValidate();
} catch(Exception $e) {
    echo "Problem with setup: " . $e->getMessage() . "\n";
    exit;
}

# Write section
try {
    $gDb = new ManageBiblio($gDb, $gpDebug);
    $gDb->mkJoinTable(true);
    $gDb->backup("lo");
    $gDb->fixBooksWithBlanks();
    $gDb->fixNonBooks();
} catch(Exception $e) {
    echo "Problem updating table: " . $e->getMessage() . "\n";
}

?>
