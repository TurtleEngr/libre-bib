#!/usr/bin/env bash
# $Source: /repo/libre-bib/src/bin/db-setup.inc,v $
# $Revision: 1.1 $ $Date: 2026/08/18 00:00:00 $ GMT

# Functions for db-setup. See db-setup for the Main section.

# ========================================
# Script Functions

# --------------------------------
fUsage() {
    # Quick help, run this:
    # db-setup -h

    local pStyle="$1"

    case $pStyle in
        short | usage | man | long | text | md)
            fComUsage -f $cBin/$cName.inc -s $pStyle | more
            ;;
        html)
            fComUsage -f $cBin/$cName.inc -s $pStyle -t "$cName Usage"
            ;;
        int)
            fComUsage -i -f $cBin/$cName -f $cBin/$cName.inc -f $cBin/$cName.test -f $cBin/bash-com.inc -s long | more
            ;;
        int-html)
            fComUsage -i -f $cBin/$cName -f $cBin/$cName.inc -f $cBin/$cName.test -f $cBin/bash-com.inc -s html -t "$cName Internal Doc"
            ;;
        int-md)
            fComUsage -i -f $cBin/$cName -f $cBin/$cName.inc -f $cBin/$cName.test -f $cBin/bash-com.inc -s md
            ;;
        *)
            fComUsage -f $cBin/$cName.inc -s short | more
            ;;
    esac
    fCleanUp
    exit 1

    # POD Syntax: https://perldoc.perl.org/perlpod
    cat <<\EOF >/dev/null
=pod

=for text ========================================

=for html <hr/>

=head1 NAME db-setup

Wizard to configure conf.env and the libre-bib database.

=head1 SYNOPSIS

        db-setup [-n] [-h] [-H pStyle] [-l] [-v] [-x] [-T pTest]

=head1 DESCRIPTION

db-setup is an interactive wizard. It is run from the directory that
holds (or will hold) your conf.env file. It walks through the setup
that is needed before "bib" can be used:

=over 4

=item * Check that the required programs and the DB server are installed
and running.

=item * Create ./conf.env from the example, and change the settings that
are usually different for each user (DB name, DB user, file names).

=item * Create the working directories and copy in the example bib and
document files.

=item * Save the DB passwords (user, admin, and if needed root) in
$cgDirCache.

=item * Create the database, the DB users, and the grants.

=item * Verify the result by connecting as the DB user.

=back

The wizard can be run as many times as needed. Nothing is created
twice, and every step reports what it finds before it changes
anything. If you edited conf.env by hand, run the wizard again to
validate it and to fix what is missing.

Values that are already correct are left alone, so the usual way to
use this is to work down the main menu from top to bottom the first
time, and after that to run only the menu item you need.

=head1 OPTIONS

=over 4

=item B<-n>

No execute. Report the commands and the SQL that would be run, but do
not change the DB, install packages, or start services. Changes to
conf.env and the directories are still made. This sets cgNoExec.

=item B<-h>

Output this "long" usage help. See "-H long"

=item B<-H pStyle>

pStyle is used to select the type of help and how it is formatted.

Styles:

    short|usage - Output short usage help as text.
    long|text   - Output long usage help as text.
    man         - Output long usage help as a man page.
    html        - Output long usage help as html.
    md          - Output long usage help as markdown.
    int         - Also output internal documentation as text.
    int-html    - Also output internal documentation as html.
    int-md      - Also output internal documentation as markdown.

=item B<-l>

Send log messages to syslog. Default is to just send output to stderr.

=item B<-v>

Verbose output. Default is is only output (or log) messages with
level "warning" and higher.

-v - output "notice" and higher.

-vv - output "info" and higher.

=item B<-x>

Set the gpDebug level. Add 1 for each -x.
Or you can set gpDebug before running the script.

See: fLog (Internal documentation)

=item B<-T "pTest">

Run the unit test functions in this script.

"B<-T all>" will run all of the functions that begin with "test".

"B<-T list>" will list all of the test functions.

"B<-T com>" will run all the tests for bash-com.inc

Otherwise, "B<pTest>" should match the test function names separated
with spaces (between quotes).

For more help, use the "-H int" option.

For more details about shunit2 (or shunit2.1), see
shunit2/shunit2-manual.html L<Source|https://github.com/kward/shunit2>

=back

=head2 Globals

=over 4

=item B<cgDirApp>

Where libre-bib is installed. Default: the parent of the directory
that holds db-setup. For an installed copy that is /opt/libre-bib.

=item B<cgNoExec>

If "true", DB, package, and service commands are only reported. See -n.

=item B<gpAuto, gpYesNo>

If gpAuto is non-zero, fComYesNo answers from gpYesNo without
prompting. Used by the unit tests. Note that the menus still read
from stdin.

=back

See also the conf.env manual section, and: bib, sanity-check.sh

=head1 RETURN VALUE

0 - the wizard exited normally

Non-zero - a required program, file, or directory was missing

=head1 ERRORS

Fatal Error:

 cgDirApp is not set correctly
 conf.env is required
 Missing one or more required programs

Warning:

 No DB server found
 Could not get DB root access
 Empty password, not saved

Many error messages may describe where the error is located, with the
following log message format:

 Program: PID NNNN: Message [LINE](ErrNo)

=head1 EXAMPLES

First time setup, in the directory where you keep your document:

 mkdir ~/mydoc
 cd ~/mydoc
 /opt/libre-bib/bin/db-setup

See what the DB steps would do, without touching the DB:

 db-setup -n -v

=head1 ENVIRONMENT

See Globals section for details.

HOME, USER, PWD, cgDirApp, cgNoExec, gpLog, gpFacility, gpVerbose,
gpDebug, gpAuto, gpYesNo, gpMaxLoop

=head1 FILES

 ./conf.env                 - your settings (created from the example)
 ./conf.env-DATE            - backup, made before the first change
 $cgDirApp/etc/conf.env     - the app defaults
 $cgDirApp/doc/example/     - the example conf.env, bib, and doc files
 $cgDirCache/.root.pass     - obfuscated DB root password
 $cgDirCache/.admin.pass    - obfuscated DB admin password
 $cgDbPassCache             - obfuscated DB user password

=head1 SEE ALSO

bib
bib-cmd.mak
sanity-check.sh
valid-conf.sh
bash-com.inc
shunit2.1

=head1 NOTES

=head2 DB root access

The DB root user is always reached with "sudo mysql". The saved root
password, if one has been set, is added with --password=. There is
only one path, because MariaDB on Debian and Ubuntu makes "sudo mysql"
work by default (root@localhost has no password to start with), and
once a password is set, mysql still accepts it while running as root
through sudo.

The root password is never asked for as part of the normal setup.
There is no menu item to change it on a server that already has one.
The only place it is set is right after the erase step, on a server
that was just reinitialized and so has no password yet.

=head2 Erasing all the databases

If root cannot be reached at all, the DB root password is unknown and
there is no way to recover it. The only remaining option is to
reinitialize the server, which erases B<every> database on the host,
not only the libre-bib one. That menu item is refused while root
access works, and it asks for a typed confirmation.

=head1 CAVEATS

The saved passwords are obfuscated with "rot13.sh -e", which is
not encryption. Anyone who can read $cgDirCache can recover them. The
files are created mode 600. This matches how bib-cmd.mak and
db-create.sh already store them.

The root password, when one is set, is passed to mysql on the command
line, where it is briefly visible in "ps". This also matches
db-create.sh.

=head1 DIAGNOSTICS

To verify the script is internally OK, run: db-setup -T all

=head1 BUGS

The menus read from stdin directly, so they cannot be driven by
gpAuto. Only the worker functions have unit tests.

=head1 RESTRICTIONS

Package install and service control assume a Debian or Ubuntu host
with mariadb-server, apt-get, and systemd.

=head1 HISTORY

GPLv2 (c) Copyright

=cut
EOF
    cat <<EOF >/dev/null
=internal-pod

=internal-head1 db-setup Internal Documentation

=internal-head2 Script Functions

=internal-head3 fUsage pStyle

Select the type of help output. See -h and -H options. The POD lives
in db-setup.inc, so that is the file passed to fComUsage.

=internal-cut
EOF
} # fUsage

# --------------------------------
fCleanUp() {
    fComCleanUp
    exit

    cat <<EOF >/dev/null
=internal-pod

=internal-head3 fCleanUp

Calls fComCleanUp.

=internal-cut
EOF
} # fCleanUp

# --------------------------------
fSetGlobals() {
    fComSetGlobals

    cgBin=${cgBin:-$cBin}
    cgDirApp=${cgDirApp:-${cgBin%/bin}}
    cgNoExec=${cgNoExec:-false}
    gpNoExec=${gpNoExec:-0}

    gConfBackup=0
    gDbRootArgs=()

    # libreoffice, php, and systemctl are installed along with the
    # libre-bib package itself, so db-setup expects them to already be
    # there and exits if they are not. mysql is the exception: getting
    # it installed and running is this wizard's job, so it stays
    # optional; see fDbCheckInstalled.
    # gawk is required because valid-conf.sh's awk uses \s, which mawk
    # does not support. Under mawk it rejects a valid conf.env.
    fComCheckDeps "rot13.sh sed gawk libreoffice php systemctl mysql" "cat"
    return 0

    cat <<EOF >/dev/null
=internal-pod

=internal-head3 fSetGlobals

Calls fComSetGlobals, then sets cgBin and cgDirApp from the location
of this script, so that the wizard works from a git working copy as
well as from /opt/libre-bib. Both can be set before the script runs.

gConfBackup is 1 after conf.env has been backed up in this session.

=internal-cut
EOF
} # fSetGlobals

# --------------------------------
fValidateApp() {
    local tFile

    for tFile in \
        $cgDirApp/VERSION \
        $cgDirApp/etc/conf.env \
        $cgDirApp/doc/example/conf.env \
        $cgBin/valid-conf.sh; do
        if [[ ! -r $tFile ]]; then
            fError -m "Could not read $tFile. Set cgDirApp to where libre-bib is installed." -l $LINENO
        fi
    done

    if [[ "$cgBin" != "$cgDirApp/bin" ]]; then
        fError -m "cgBin $cgBin is not $cgDirApp/bin" -l $LINENO
    fi
    return 0

    cat <<EOF >/dev/null
=internal-pod

=internal-head3 fValidateApp

Exit if cgDirApp and cgBin do not point at a libre-bib install.

=internal-cut
EOF
} # fValidateApp

# ========================================
# conf.env Functions

# --------------------------------
fConfInit() {
    if [[ -f ./conf.env ]]; then
        return 0
    fi

    echo
    echo "There is no conf.env in the current directory:"
    echo "    $PWD"
    echo "conf.env holds your settings. The example will be copied here."
    if ! fComYesNo "Copy $cgDirApp/doc/example/conf.env to $PWD"; then
        fError -m "conf.env is required. cd to your document dir, then run $cName again." -l $LINENO
    fi

    cp $cgDirApp/doc/example/conf.env ./conf.env
    chmod a+rx ./conf.env
    fLog -f -p notice -m "Created ./conf.env" -l $LINENO
    return 0

    cat <<EOF >/dev/null
=internal-pod

=internal-head3 fConfInit

If ./conf.env is missing, ask before copying the example here. The
copy is made executable, because sanity-check.sh requires that.

=internal-cut
EOF
} # fConfInit

# --------------------------------
fConfLoad() {
    local tErr=0

    . $cgDirApp/etc/conf.env

    if [[ -f ./conf.env ]]; then
        if $cgBin/valid-conf.sh <./conf.env; then
            . /tmp/conf.env
        else
            fError -c -m "Fix the above errors in ./conf.env, then select this item again." -l $LINENO
            tErr=1
        fi
    fi

    # -n on the command line wins over cgNoExec in the config files
    if [[ $gpNoExec -ne 0 ]]; then
        cgNoExec=true
    fi
    return $tErr

    cat <<EOF >/dev/null
=internal-pod

=internal-head3 fConfLoad

Source the app defaults, then the user's ./conf.env on top of them, so
that the cg* variables hold the values that are currently in effect.
./conf.env is passed through valid-conf.sh first, the same check that
"bib" makes.

Returns 1 if ./conf.env did not validate. Call this after every change
to conf.env.

=internal-cut
EOF
} # fConfLoad

# --------------------------------
fConfBackup() {
    if [[ $gConfBackup -ne 0 ]]; then
        return 0
    fi
    cp -p ./conf.env ./conf.env-$(date +%F_%H%M%S)
    gConfBackup=1
    return 0

    cat <<EOF >/dev/null
=internal-pod

=internal-head3 fConfBackup

Copy ./conf.env to ./conf.env-DATE, once per run, before the first
change is made to it.

=internal-cut
EOF
} # fConfBackup

# --------------------------------
fConfSet() {
    local pName="$1"
    local pValue="$2"

    if [[ ! "$pValue" =~ ^[A-Za-z0-9_.@:/-]+$ ]]; then
        fError -c -m "Value for $pName may only have: A-Z a-z 0-9 _ . @ : / -" -l $LINENO
        return 1
    fi

    fConfBackup
    if grep -qE "^[[:space:]]*#?[[:space:]]*(export[[:space:]]+)?$pName=" ./conf.env; then
        sed -i -E "s|^[[:space:]]*#?[[:space:]]*(export[[:space:]]+)?$pName=.*|$pName=\"$pValue\"|" ./conf.env
    else
        echo "$pName=\"$pValue\"" >>./conf.env
    fi
    fLog -f -p notice -m "Set $pName=\"$pValue\"" -l $LINENO
    return 0

    cat <<EOF >/dev/null
=internal-pod

=internal-head3 fConfSet pName pValue

Set one variable in ./conf.env. In the example conf.env every variable
is commented out and indented, which means "use the app default". A
variable that is set here is uncommented and unindented, so that it
overrides the default. A variable that is not in the file at all is
appended.

The value is restricted to the characters that valid-conf.sh accepts,
and that are safe as a sed replacement. Anything else is refused.

=internal-cut
EOF
} # fConfSet

# --------------------------------
fConfAsk() {
    local pName="$1"

    local tCur
    local tNew

    eval tCur="\${$pName:-}"
    echo
    read -r -p "$pName (current: $tCur), Enter to keep? " tNew
    if [[ -z "$tNew" ]]; then
        return 0
    fi
    if ! fConfSet "$pName" "$tNew"; then
        return 1
    fi
    fConfLoad
    return 0

    cat <<EOF >/dev/null
=internal-pod

=internal-head3 fConfAsk pName

Show the value of pName that is in effect now, and set a new one if
the user types one. An empty answer keeps the current value, so this
is safe to walk through on a re-run.

=internal-cut
EOF
} # fConfAsk

# --------------------------------
fConfShow() {
    local tVar

    echo
    echo "Settings in effect (values not in ./conf.env come from"
    echo "$cgDirApp/etc/conf.env):"
    echo
    for tVar in \
        cgDbName cgDbUser cgDbHost cgDbPortLocal cgDbPassHint \
        cgDbTblBib cgDbTblLo cgDbPassCache \
        cgLoFile cgDocFile \
        cgDirEtc cgDirStatus cgDirTmp cgDirCache cgDirBackup; do
        eval echo "\"    $tVar=\$$tVar\""
    done
    echo
    return 0

    cat <<EOF >/dev/null
=internal-pod

=internal-head3 fConfShow

List the settings the wizard cares about, with the values that are in
effect after the app defaults and ./conf.env have both been read.

=internal-cut
EOF
} # fConfShow

# ========================================
# Directory and File Functions

# --------------------------------
fMakeDirs() {
    local tDir

    for tDir in $cgDirEtc $cgDirStatus $cgDirTmp $cgDirCache $cgDirBackup; do
        if [[ -d $tDir ]]; then
            fLog -p info -m "Found dir: $tDir" -l $LINENO
            continue
        fi
        mkdir -p $tDir
        fLog -f -p notice -m "Created dir: $tDir" -l $LINENO
    done

    cp -n $cgDirApp/etc/* $cgDirEtc 2>/dev/null
    return 0

    cat <<EOF >/dev/null
=internal-pod

=internal-head3 fMakeDirs

Create the five working dirs named in conf.env, and copy the app etc
files into cgDirEtc. Existing dirs and files are left alone, so this
can be run again after conf.env is changed.

=internal-cut
EOF
} # fMakeDirs

# --------------------------------
fMakeFiles() {
    local tNote

    tNote="${cgLoFile%.*}-note.txt"

    fCopyIfMissing $cgDirApp/doc/example/biblio.txt "$cgLoFile"
    fCopyIfMissing $cgDirApp/doc/example/biblio-note.txt "$tNote"
    fCopyIfMissing $cgDirApp/doc/example/key.txt key.txt
    fCopyIfMissing $cgDirApp/doc/example/example.odt "$cgDocFile"
    return 0

    cat <<EOF >/dev/null
=internal-pod

=internal-head3 fMakeFiles

Copy the example bib file, its note file, key.txt, and the example
document, using the names set in conf.env. Files that already exist
are not touched.

=internal-cut
EOF
} # fMakeFiles

# --------------------------------
fCopyIfMissing() {
    local pFrom="$1"
    local pTo="$2"

    if [[ -e "$pTo" ]]; then
        fLog -p info -m "Found file: $pTo" -l $LINENO
        return 0
    fi
    if [[ ! -r $pFrom ]]; then
        fLog -f -p warning -m "Could not read $pFrom" -l $LINENO
        return 1
    fi
    cp "$pFrom" "$pTo"
    fLog -f -p notice -m "Created file: $pTo" -l $LINENO
    return 0

    cat <<EOF >/dev/null
=internal-pod

=internal-head3 fCopyIfMissing pFrom pTo

Copy pFrom to pTo, only if pTo does not exist.

=internal-cut
EOF
} # fCopyIfMissing

# ========================================
# Password Functions

# --------------------------------
fPassFile() {
    local pWho="$1"

    case $pWho in
        user) echo "$cgDbPassCache" ;;
        root) echo "$cgDirCache/.root.pass" ;;
        admin) echo "$cgDirCache/.admin.pass" ;;
        *)
            fError -c -i -m "Unknown password: $pWho" -l $LINENO
            return 1
            ;;
    esac
    return 0

    cat <<EOF >/dev/null
=internal-pod

=internal-head3 fPassFile pWho

Output the cache file name for pWho: user, root, or admin. The names
are the ones bib-cmd.mak and db-create.sh already use.

=internal-cut
EOF
} # fPassFile

# --------------------------------
fPassExists() {
    local pWho="$1"

    [[ -s $(fPassFile $pWho) ]]
    return $?

    cat <<EOF >/dev/null
=internal-pod

=internal-head3 fPassExists pWho

Return 0 if a non-empty password is cached for pWho.

=internal-cut
EOF
} # fPassExists

# --------------------------------
fPassSet() {
    local pWho="$1"

    local tFile
    local tHint=""
    local tPass

    tFile=$(fPassFile $pWho) || return 1
    if [[ ! -d $cgDirCache ]]; then
        mkdir -p $cgDirCache
    fi
    if [[ "$pWho" = "user" ]]; then
        tHint=" (hint: $cgDbPassHint)"
    fi

    echo
    read -rsp "DB $pWho password$tHint? " tPass
    echo
    if [[ -z "$tPass" ]]; then
        fLog -f -p warning -m "Empty password. $tFile was not changed." -l $LINENO
        return 1
    fi

    echo "$tPass" | rot13.sh -e >$tFile
    chmod 600 $tFile
    fLog -f -p notice -m "Saved $tFile" -l $LINENO
    return 0

    cat <<EOF >/dev/null
=internal-pod

=internal-head3 fPassSet pWho

Prompt for a password without echoing it, and save it obfuscated with
"rot13.sh -e" in the cache file for pWho, mode 600. An empty answer
leaves any existing file alone.

=internal-cut
EOF
} # fPassSet

# --------------------------------
fPassGet() {
    local pWho="$1"

    local tFile

    tFile=$(fPassFile $pWho) || return 1
    if [[ ! -s $tFile ]]; then
        fLog -f -p warning -m "No password saved in $tFile" -l $LINENO
        return 1
    fi
    rot13.sh -d <$tFile
    return 0

    cat <<EOF >/dev/null
=internal-pod

=internal-head3 fPassGet pWho

Output the decoded password for pWho. Returns 1 if it has not been set.

=internal-cut
EOF
} # fPassGet

# ========================================
# DB Functions

# --------------------------------
fRun() {
    if [[ "$cgNoExec" = "true" ]]; then
        fLog -f -p notice -m "NoExec: $*" -l $LINENO
        return 0
    fi
    "$@"
    return $?

    cat <<EOF >/dev/null
=internal-pod

=internal-head3 fRun pCmd ...

Run a command that changes the system (sudo, apt-get, systemctl). If
cgNoExec is "true" the command is only reported. See -n.

=internal-cut
EOF
} # fRun

# --------------------------------
fDbCheckRunning() {
    local tService

    tService=mariadb
    if systemctl is-active --quiet $tService; then
        fLog -p info -m "$tService is running." -l $LINENO
        return 0
    fi

    echo
    echo "The $tService service is not running."
    if ! fComYesNo "Start $tService now"; then
        return 1
    fi
    fRun sudo systemctl start $tService
    return $?

    cat <<EOF >/dev/null
=internal-pod

=internal-head3 fDbCheckRunning

Check that the DB service is active, and offer to start it.

=internal-cut
EOF
} # fDbCheckRunning

# --------------------------------
fDbRootWorks() {
    sudo -n mysql -h $cgDbHost -e 'select 1' &>/dev/null
    return $?

    cat <<EOF >/dev/null
=internal-pod

=internal-head3 fDbRootWorks

Test whether the DB root user can be reached right now: always
"sudo mysql", with the saved root password added if one has been set.

"sudo -n" is used here (only here) so that a host where sudo itself
needs a password fails this test at once, instead of sitting at a
password prompt that the redirected output has hidden.

=internal-cut
EOF
} # fDbRootWorks

# --------------------------------
fDbRootAccess() {
    if fDbRootWorks; then
        fLog -f -p notice -m "DB root access is working." -l $LINENO
        return 0
    fi

    echo
    echo "Could not reach the DB as root with: sudo mysql"
    if fComYesNo "Enter the DB root password and try again"; then
        fPassSet root
        if fDbRootWorks; then
            fLog -f -p notice -m "DB root access is working." -l $LINENO
            return 0
        fi
    fi

    echo
    echo "Still no DB root access. You can try the password again, or, if the"
    echo "root password cannot be recovered, use the 'Erase' menu item."
    return 1

    cat <<EOF >/dev/null
=internal-pod

=internal-head3 fDbRootAccess

Get DB root access, asking for the root password if sudo alone is not
enough. Returns 1 if root still cannot be reached, which is the only
case where the erase menu item is allowed.

=internal-cut
EOF
} # fDbRootAccess

# --------------------------------
fDbSql() {
    local pSql="$1"

    local tErr

    if [[ "$cgNoExec" = "true" ]]; then
        echo "NoExec, the SQL that would be run:"
        echo "$pSql"
        return 0
    fi

    umask 077
    echo "$pSql" >$cTmp1
    sudo mysql -f -h $cgDbHost <$cTmp1
    tErr=$?
    'rm' -f $cTmp1
    return $tErr

    cat <<EOF >/dev/null
=internal-pod

=internal-head3 fDbSql pSql

Run pSql as the DB root user: always "sudo mysql", with the saved root
password added if one has been set. Unlike fDbRootWorks, sudo here is
not "-n", so if the host needs a sudo password this is where the
person is asked for it, in the open, not behind a hidden check.

The SQL is written to a mode 600 temp file and redirected in, so that
the DB user and admin passwords do not appear in "ps".

If cgNoExec is "true" the SQL is printed and nothing is run.

=internal-cut
EOF
} # fDbSql

# --------------------------------
fDbCreate() {
    local tAdminPass
    local tUserPass

    if ! fDbRootAccess; then
        return 1
    fi
    if ! fPassExists user || ! fPassExists admin; then
        fError -c -m "Set the DB user and admin passwords first." -l $LINENO
        return 1
    fi

    tUserPass=$(fPassGet user)
    tAdminPass=$(fPassGet admin)

    fDbSql "
create database if not exists $cgDbName;

create user if not exists 'admin'@'$cgDbHost' identified by '$tAdminPass';
alter user 'admin'@'$cgDbHost' identified by '$tAdminPass';
grant all privileges on *.* to 'admin'@'$cgDbHost';

create user if not exists '$cgDbUser'@'$cgDbHost' identified by '$tUserPass';
alter user '$cgDbUser'@'$cgDbHost' identified by '$tUserPass';
grant all privileges on $cgDbName.* to '$cgDbUser'@'$cgDbHost';

flush privileges;
"
    return $?

    cat <<EOF >/dev/null
=internal-pod

=internal-head3 fDbCreate

Create the database, the admin user, and the DB user, and grant them.
"if not exists" and "alter user" are both used, so that running this
again on an existing DB updates the passwords instead of failing.

=internal-cut
EOF
} # fDbCreate

# --------------------------------
fDbTestUser() {
    local tPass

    if ! fPassExists user; then
        fLog -f -p warning -m "The DB user password has not been set." -l $LINENO
        return 1
    fi
    tPass=$(fPassGet user)

    if ! mysql -P $cgDbPortLocal -u $cgDbUser --password="$tPass" -h $cgDbHost \
        -e "use $cgDbName; show tables;" &>/dev/null; then
        fLog -f -p warning -m "$cgDbUser could not connect to $cgDbName." -l $LINENO
        return 1
    fi
    fLog -f -p notice -m "$cgDbUser connected to $cgDbName." -l $LINENO
    return 0

    cat <<EOF >/dev/null
=internal-pod

=internal-head3 fDbTestUser

Connect to cgDbName as cgDbUser, which is what "bib connect" does.
This is the check that says the DB half of the setup is done.

=internal-cut
EOF
} # fDbTestUser

# --------------------------------
fDbAskRootPass() {
    echo
    if ! fComYesNo "Set a DB root password now"; then
        return 1
    fi
    if ! fPassSet root; then
        return 1
    fi

    fDbSql "alter user 'root'@'$cgDbHost' identified by '$(fPassGet root)'; flush privileges;"
    return 0

    cat <<EOF >/dev/null
=internal-pod

=internal-head3 fDbAskRootPass

Ask whether to set a DB root password, and if the answer is yes, save
it and set it on the server. Only called right after fDbErase
reinitializes the server, which is the one moment a root password can
be set without already knowing the old one. No access check is made
before or after: mysql_install_db always leaves root reachable with
"sudo mysql", and fDbSql always uses sudo, so there is nothing to
check.

=internal-cut
EOF
} # fDbAskRootPass

# --------------------------------
fDbErase() {
    local tReply
    local tService

    if fDbRootWorks; then
        fError -c -m "Root access works, so erasing is not needed." -l $LINENO
        return 1
    fi

    echo
    echo "This ERASES EVERY DATABASE on this host, not only $cgDbName."
    echo "/var/lib/mysql is emptied and the server is reinitialized."
    echo "Any other application using this DB server loses its data."
    echo
    if ! fComYesNo "Do you have backups, and do you want to erase all databases"; then
        return 1
    fi
    read -r -p "Type ERASE to confirm: " tReply
    if [[ "$tReply" != "ERASE" ]]; then
        fLog -f -p notice -m "Erase cancelled." -l $LINENO
        return 1
    fi

    tService=mariadb
    fRun sudo systemctl stop $tService
    fRun sudo sh -c 'rm -rf /var/lib/mysql/*'
    fRun sudo -u mysql mysql_install_db
    fRun sudo systemctl start $tService
    fLog -f -p notice -m "The DB server was reinitialized." -l $LINENO

    fDbAskRootPass
    return 0

    cat <<EOF >/dev/null
=internal-pod

=internal-head3 fDbErase

Reinitialize the DB server, erasing all databases. This is refused
while root access works, and needs both a yes/no answer and the word
ERASE typed in. The steps are the ones in db-create.sh's usage text.

Right after the erase, fDbAskRootPass offers to set a root password on
the fresh server.

=internal-cut
EOF
} # fDbErase

# ========================================
# Menu Functions

# --------------------------------
fDoCheck() {
    fDbCheckRunning
    fDbRootAccess
    return 0

    cat <<EOF >/dev/null
=internal-pod

=internal-head3 fDoCheck

Menu item: check that the DB server is installed, running, and that
root can be reached.

=internal-cut
EOF
} # fDoCheck

# --------------------------------
fDoDirs() {
    fMakeDirs
    fMakeFiles
    return 0

    cat <<EOF >/dev/null
=internal-pod

=internal-head3 fDoDirs

Menu item: create the working dirs and the example files.

=internal-cut
EOF
} # fDoDirs

# --------------------------------
fDoPass() {
    local tWho

    for tWho in user admin; do
        if fPassExists $tWho; then
            echo
            if ! fComYesNo "A $tWho password is already saved. Change it"; then
                continue
            fi
        fi
        fPassSet $tWho
    done
    return 0

    cat <<EOF >/dev/null
=internal-pod

=internal-head3 fDoPass

Menu item: set the DB user and admin passwords, asking before
replacing ones that are already saved. The root password is only asked
for when it is needed, by fDbRootAccess.

=internal-cut
EOF
} # fDoPass

# --------------------------------
fDoVerify() {
    local tErr=0
    local tDir
    local tWho

    echo
    if ! fConfLoad; then
        ((++tErr))
    fi
    for tDir in $cgDirEtc $cgDirStatus $cgDirTmp $cgDirCache $cgDirBackup; do
        if [[ ! -d $tDir ]]; then
            echo "Missing dir: $tDir"
            ((++tErr))
        fi
    done
    if [[ ! -r "$cgLoFile" ]]; then
        echo "Missing file: $cgLoFile"
        ((++tErr))
    fi
    if [[ ! -r "$cgDocFile" ]]; then
        echo "Missing file: $cgDocFile"
        ((++tErr))
    fi
    for tWho in user admin; do
        if ! fPassExists $tWho; then
            echo "Missing password: $tWho"
            ((++tErr))
        fi
    done
    if ! fDbTestUser; then
        ((++tErr))
    fi

    if [[ -x $cgBin/sanity-check.sh ]]; then
        $cgBin/sanity-check.sh || ((++tErr))
    fi

    echo
    if [[ $tErr -ne 0 ]]; then
        echo "$tErr problem(s) found. Use the menu items above to fix them."
        return 1
    fi
    echo "Setup looks complete. Next: bib import-lo"
    return 0

    cat <<EOF >/dev/null
=internal-pod

=internal-head3 fDoVerify

Menu item: check conf.env, the dirs, the files, the passwords, and the
DB connection, then run sanity-check.sh. Reports everything it finds
rather than stopping at the first problem.

=internal-cut
EOF
} # fDoVerify

# --------------------------------
fMenu() {
    cMenuMain=(
        'Select a number:'
        'Check the DB server'
        'Change the conf.env settings'
        'Create the dirs and example files'
        'Set the DB user and admin passwords'
        'Create the DB, the DB users, and the grants'
        'Verify the setup'
        'Erase ALL the databases (this also offers to set a root password)'
        'Help'
        'Quit'
    )
    cActnMain=(
        'Work down this menu the first time. Every item can be run
again later, so if you edited conf.env by hand, use item 6 to see
what is still missing. Item 7 erases every database on this host, and
is refused while DB root access works; it is also the only place a DB
root password is set, right after the erase.'
        'func fDoCheck'
        'menu cMenuConf cActnConf'
        'func fDoDirs'
        'func fDoPass'
        'func fDbCreate'
        'func fDoVerify'
        'func fDbErase'
        'help'
        'back'
    )

    cMenuConf=(
        'Select a number:'
        'Show the current settings'
        'DB name'
        'DB user'
        'DB host'
        'DB port'
        'DB password hint'
        'LibreOffice bib file'
        'Document file'
        'Back'
    )
    cActnConf=(
        'These are written to ./conf.env in the current directory.
A backup, conf.env-DATE, is made before the first change. Each prompt
shows the value that is in effect now. Press Enter to keep it.'
        'func fConfShow'
        'func fConfAsk cgDbName'
        'func fConfAsk cgDbUser'
        'func fConfAsk cgDbHost'
        'func fConfAsk cgDbPortLocal'
        'func fConfAsk cgDbPassHint'
        'func fConfAsk cgLoFile'
        'func fConfAsk cgDocFile'
        'back'
    )

    fComMenu "$cName" cMenuMain cActnMain
    return 0

    cat <<EOF >/dev/null
=internal-pod

=internal-head3 fMenu

Define the menu and action arrays, then call fComMenu.

Note that fComMenu expands each item once, so a \$cg variable in an
item's text is B<not> replaced by its value. Current values are shown
by fConfShow and by the fConfAsk prompts instead of in the menu.

The "func" actions are also expanded once, and quoting is lost, so
their arguments cannot contain spaces. That is why fConfAsk takes only
a variable name and builds its own prompt.

=internal-cut
EOF
} # fMenu

# --------------------------------
# This should be the last defined function
fRunTests() {
    if [[ "$gpTest" = "list" ]]; then
        grep 'test.*()' $cBin/$cName.test | grep -v grep | sed 's/()//g'
        exit 1
    fi
    SHUNIT_COLOR=auto
    if [[ "$gpTest" = "com" ]]; then
        gpTest=""
        $cBin/bash-com.test
        exit $?
    fi
    # shellcheck disable=SC1091
    source $cBin/$cName.test
    if [[ "$gpTest" = "all" ]]; then
        gpTest=""
        # shellcheck disable=SC1091
        source /usr/local/bin/shunit2.1
        exit $?
    fi
    # shellcheck disable=SC1091
    source /usr/local/bin/shunit2.1 -- $gpTest
    exit $?

    cat <<EOF >/dev/null
=internal-pod

=internal-head3 fRunTests

Run the unit tests. The test functions are in db-setup.test, so that
file is sourced before shunit2.1 is, because shunit2 only sees the
functions that are defined in the running shell.

=internal-cut
EOF
} # fRunTests
