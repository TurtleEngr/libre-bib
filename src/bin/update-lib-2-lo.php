#!/usr/bin/env php
<?php
# TBD convert class back to functions, the structure doesn't add much.
# See the function backup for there is duplicate code. See util.php.

# -----------------------------
function fusage() {
    global $argc;
    global $argv;

    system("pod2text $argv[0]");
    exit(1);

    /* ...

=pod

=head1 NAME

update-lib-2-lo.php - Using a join table update cgDbLo from cgDbLib

=head1 SYNOPSIS

 update-lib-2-lo.php [-h]

=head1 DESCRIPTION

First create the cgDbLib and cgDbLo tables in the DB. Then run this to
create a join table for Titles that macth in the first 40 char. The
join table will then be used to fill in missing cgDbLo table fields from
the lib table fields. (There is a mapping of names between the fields,
and there are some transformations begin done.)

=head1 OPTIONS

See also ENVIRONMENT section.

=over 4

=item B<-h> - help

This help.

=back

=for comment =head1 RETURN VALUE

=for comment =head1 ERRORS

=for comment =head1 EXAMPLES

=head1 ENVIRONMENT

Set these in conf.env

    cgDbLo
    cgDbLib

=for comment =head1 FILES

=for comment =head1 SEE ALSO

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

$Revision: 1.1 $ $Date: 2023/05/17 01:13:24 $ GMT

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
    global $cgDebug;
    global $gpHelp;

    $gpHelp = false;
    $tOpt = getopt("ch");
    $gpHelp = isset($tOpt['h']);
    if ($gpHelp or $argc < 2) {
        fUsage();
    }

    $tConf = $_ENV['cgDirApp'] . "/etc/conf.php";
    require_once "$tConf";
    require_once "$cgBin/util.php";
    fFixBool();

} # fGetOps

# -----------------------------
function fValidate() {
    global $cgBin;
    global $cgDbLo;
    global $cgDbLib;

    fValidateCommon();

    if ( ! fTableExists($cgDbLo))
        throw new Exception("Error: Missing $cgDbLo Table. [" . __LINE__ . "]");

    if ( ! fTableExists($cgDbLib))
        throw new Exception("Error: Missing $cgDbLib Table. [" . __LINE__ . "]");
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
        global $cgDbLib;
        global $cgDbLo;

        try {
            if ($pDrop)
                $this->gDbH->query("drop table join_lib_lo");
            $tSql = "create table IF NOT EXISTS join_lib_lo (
                select
                    $cgDbLib.Book_Id,
                    $cgDbLo.Identifier,
                    $cgDbLo.Booktitle,
                    $cgDbLib.Media
                from $cgDbLib, $cgDbLo
                where left($cgDbLib.Title,40) =
                      left($cgDbLo.Booktitle,40)
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
        global $cgBackup;

        $tCount = $this->gDbH->query("SHOW TABLES LIKE '" . $pTable . "'")->rowCount();
        if ($cgBackup and $tCount > 0) {
            # Append: "_MM-DD_HH-MM"
            $tNewName = "$pTable" . "_" . fDate("iso");
            $tSql = "CREATE TABLE `$tNewName` SELECT * FROM `$pTable`";
            $this->gDbH->query("$tSql");
        }
    } # backup

    # --------------------
    protected function updateLoBook($pLoResult) {
        global $cgNoExec;
        global $cgDbLo;

        # Author, Custom2, Custom3, ISBN, Publisher, RepType, Type, Year
        $tSql = "update $cgDbLo set
            Author    = '" . $pLoResult['Author']  . "',
            Custom2   = '" . $pLoResult['Custom2'] . "',
            Custom3   = '" . $pLoResult['Custom3'] . "',
            ISBN      = '" . $pLoResult['ISBN']    . "',
            Publisher = '" . $pLoResult['Publisher'] . "',
            RepType   = '" . $pLoResult['RepType'] . "',
            Year      = '" . $pLoResult['Year']    . "'
            where Identifier = '" . $pLoResult['Identifier'] . "'";
        if ($this->gDebug) print_r("$tSql \n");
        if ( ! $cgNoExec)
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
            if ( ! $tJoinResult)
                $tJoinResult = $this->getBookId($tLoResult, false);
            if ( ! $tJoinResult) {
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
        echo "\nProcessed: $tCount\n";
    } # processBooks

    # --------------------
    public function fixBooksWithBlanks() {
        global $cgDbLo;
        
        # Get records that are books, where tColList are blank.
        try {
            $tId = "Identifier";
            $tColList = array('Author', 'Custom2', 'Publisher',
                'Year', 'RepType', 'ISBN', 'Custom3');
            $tColStr = implode(",", $tColList);
            $tColBlank = "(" . implode(" = '' or ", $tColList) . " = '')";
            $tSql = "select $tId, $tColStr from $cgDbLo
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
        global $cgNoExec;
        global $cgDbLo;

        # Author, Custom2, Custom3, ISBN, Publisher, RepType, Type, Year
        $tSql = "update $cgDbLo set
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
        if ( ! $cgNoExec)
            $this->gDbH->query($tSql);
    } # updateLoRec

    # --------------------
    protected function processRecs($pRecH) {
        global $cgDbLo;
        global $cgDbLib;

        $tMedia2RepType = fMedia2RepType();
        $tType2Txt = fType2Txt();

        $tCount = 0;
        while ($tRecResult = $pRecH->fetch(PDO::FETCH_ASSOC)) {
            echo ".";
            ++$tCount;
            if ($this->gDebug) echo "\n--------------------\n";
            $tId = $tRecResult["Identifier"];
            if ($this->gDebug) print_r("$tId\n");

            $tSql = "select $cgDbLo.* from $cgDbLo, $cgDbLib, join_lib_lo where
                $cgDbLo.Identifier = join_lib_lo.Identifier and
                $cgDbLib.Book_Id = join_lib_lo.Book_Id and
                $cgDbLo.Identifier = \"$tId\"";
            $tLoH = $this->gDbH->prepare($tSql);
            $tLoH->execute();
            $tLoResult = $tLoH->fetch(PDO::FETCH_ASSOC);

            $tSql = "select lib.* from $cgDbLo, $cgDbLib, join_lib_lo where
                $cgDbLo.Identifier = join_lib_lo.Identifier and
                $cgDbLib.Book_Id = join_lib_lo.Book_Id and
                $cgDbLo.Identifier = \"$tId\"";
            $tLibH = $this->gDbH->prepare($tSql);
            $tLibH->execute();
            $tLibResult = $tLibH->fetch(PDO::FETCH_ASSOC);

            # Update these fields:
            # Author, Custom2, Custom3, ISBN, Publisher, RepType, Type, Year

            $tMedia = $tLibResult["Media"];
            $tLoResult["RepType"] = $tMedia2RepType[strtolower("$tMedia")];
            $tLoResult["Type"] = fRepType2Type("$tMedia");

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

        echo "\nProcessed: $tCount\n";
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
    $gDb = new ManageBiblio($gDb, $cgDebug);
    $gDb->mkJoinTable(true);
    $gDb->backup($cgDbLo);
    $gDb->fixBooksWithBlanks();
    $gDb->fixNonBooks();
} catch(Exception $e) {
    echo "Problem updating table: " . $e->getMessage() . "\n";
}


?>
