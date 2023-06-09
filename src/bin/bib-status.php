#!/usr/bin/env php
<?php
# converted

function fusage() {
    global $argc;
    global $argv;

    system("pod2text $argv[0]");
    exit(1);    # ---------->

    /* ...

=pod

=head1 NAME

bib-ref-new.php - insert new bib refs into Libreoffice odt document

=head1 SYNOPSIS

 ./bib-ref-new.php [-h]

=head1 DESCRIPTION

bib-ref-new.php will look for {REF} tags in cgDocFile, and replace
them with bibliography-mark tags. The cgDbib Table will be used to
look up the values for the bibliography-marks.

If a {REF} tag is not found in the DB, then it will be left in the
file and a warning will be output.

=head1 OPTIONS

See also ENVIRONMENT section.

=over 4

=item B<-h> - help

This help.

=back

=for comment =head1 RETURN VALUE

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

=head1 ENVIRONMENT

cgDirApp is required

Most of variables in the conf.env are used by this script.

=for comment =head1 FILES

=for comment =head1 SEE ALSO

=for comment =head1 NOTES

=for comment =head1 CAVEATS

=for comment =head1 DIAGNOSTICS

=for comment =head1 BUGS

=for comment =head1 RESTRICTIONS

=for comment =head1 AUTHOR

=for comment =head1 HISTORY

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
    global $gpHelp;

    $gpHelp = false;
    $tOpt = getopt("ch");
    $gpHelp = isset($tOpt['h']);
    if ($gpHelp or $argc < 2)
        fUsage();

    $tConf = $_ENV['cgDirApp'] . "/etc/conf.php";
    require_once "$tConf";
    require_once "$cgBin/util.php";
    $cgVerbose = "true";
    uFixBool();

    return;    # ---------->
} # fGetOps

# -----------------------------
function fValidate() {
    global $cgDocFile;
    global $cgDbBib;
    global $cgBin;

    uValidateCommon();

    return;    # ---------->
} # fValidate

function fStatus() {
    global $cgDirStatus;
    global $cgDocFile;
    global $cgLoFile;
    global $cgUseLib;
    global $cgLibFileIn;

    if ( ! file_exists("$cgDirStatus/import-lo.date")) {
        echo "Time to run: bib import-lo\n";
    } else {
        if (filemtime($cgLoFile) > filemtime("$cgDirStatus/import-lo.date")) {
            echo "$cgLoFile is newer, run: bib import-lo\n";
            if ($cgUseLib)
                echo "If OK, run: bib update-lo\n";
        }
    }

    if ($cgUseLib) {
        if ( ! file_exists("$cgDirStatus/import-lib.date")) {
            echo "Time to run: bib import-lib\n";
            echo "If OK,  run: bib update-lo\n";
        } else {
            if (filemtime($cgLibFileIn) >
                filemtime("$cgDirStatus/import-lib.date")) {
                echo "$gLibFileIn is newer, run: bib import-lib\n";
                echo "If OK, run: bib update-lo\n";
            }
        }

        if (file_exists("$cgDirStatus/import-lib.date") and
            file_exists("$cgDirStatus/update-lo.date")) {
            if (filemtime("$cgDirStatus/import-lib.date") >
                filemtime("$cgDirStatus/update-lo.date")) {
                echo "Lib table has been updated, run: bib update-lo\n";
            }
        }
    }

    if (file_exists("$cgDirStatus/import-lo.date") and
        file_exists("$cgDirStatus)/backup-lo.date")) {
        if (filemtime("$cgDirStatus/import-lo.date") >
            filemime("$cgDirStatus)/backup-lo.date")) {
            echo "lo table is newer, maybe backup? Run: bib backup-lo\n";
        }
    }

    if (file_exists("$cgDirStatus)/bib-update.date")) {
        if (filemtime($cgDocFile) > filemtime("$cgDirStatus)/bib-update.date")) {
            echo "$cgDocFile is new, maybe run: bib bib-new and bib-update\n";
        }
    }

    if (file_exists("$cgDirStatus/import-lo.date")) {
        if (filemtime("$cgDirStatus/import-lo.date") > filemtime($cgDocFile)) {
            echo "lo table is newer than $cgDocFile, maybe run: bib bib-new and bib-update\n";
        }
    }

    return;    # ---------->
} # fStatus

function fDbStatus() {
    global $cgDbBib;
    global $cgDbHostRemote;
    global $cgDbLib;
    global $cgDbLo;
    global $cgDbName;
    global $cgUseLib;
    global $cgUseRemote;
    global $cgVerbose;

    if ( ! $cgVerbose)
        return 0;

    echo "\nVerbose is on so listing DB information.\n";

    if ($cgUseRemote)
        echo "Connected to remote DB at: $cgDbHostRemote\n";

    $tStmt = uExecSql("show databases");
    $tResult = $tStmt->fetchAll(PDO::FETCH_COLUMN);
    echo "show databases\n\t", implode("\n\t", $tResult) . "\n";
    echo "Your DB is: $cgDbName\n";

    $tStmt = uExecSql("show tables");
    $tResult = $tStmt->fetchAll(PDO::FETCH_COLUMN);
    echo "show tables\n\t" . implode("\n\t", $tResult) . "\n";

    if ( ! uTableExists($cgDbLo)) {
        echo "$cgDbLo table is not defined. Create with: bib import-lo";
    } else {
        $tStmt = uExecSql("select column_name from information_schema.columns where table_name = '" . $cgDbLo . "'");
        $tResult = $tStmt->fetchAll(PDO::FETCH_COLUMN);
        echo "\nfields for table $cgDbLo\n\t" . implode(", ", $tResult) . "\n";

        $tStmt = uExecSql("select count(*) from $cgDbLo");
        $tResult = $tStmt->fetchAll(PDO::FETCH_COLUMN);
        echo "\t" . $tResult[0] . " rows\n";
    }

    if ( ! uTableExists($cgDbBib)) {
        echo "$cgDbBib table is not defined. Create with: bib import-lo";
    } else {
        $tStmt = uExecSql("select column_name from information_schema.columns where table_name = '" . $cgDbBib . "'");
        $tResult = $tStmt->fetchAll(PDO::FETCH_COLUMN);
        echo "\nfields for table $cgDbBib\n\t" . implode(", ", $tResult) . "\n";

        $tStmt = uExecSql("select count(*) from $cgDbBib");
        $tResult = $tStmt->fetchAll(PDO::FETCH_COLUMN);
        echo "\t" . $tResult[0] . " rows\n";
    }

    if ($cgUseLib) {
        if ( ! uTableExists($cgDbLib)) {
            echo "$cgDbLib table is not defined. Create with: bib import-lib";
        } else {
            $tStmt = uExecSql("select column_name from information_schema.columns where table_name = '" . $cgDbLib . "'");
            $tResult = $tStmt->fetchAll(PDO::FETCH_COLUMN);
            echo "\nfields for table $cgDbLib\n\t" . implode(", ", $tResult) . "\n";
            $tStmt = uExecSql("select count(*) from $cgDbLib");
            $tResult = $tStmt->fetchAll(PDO::FETCH_COLUMN);
            echo "\t" . $tResult[0] . " rows\n";
        }
    }

} # fDbStatus

# ========================================
# Includes, GetOps, Validate, ReadOnly

try {
    fGetOps();
    fValidate();
    fStatus();
    fDbStatus();
} catch(Exception $e) {
    echo "Problem with setup: " . $e->getMessage() . " [bib-status.php:" . __LINE__ . "]\n";
    exit(3);    # ---------->
}

exit(0);    # ---------->
?>
