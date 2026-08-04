#!/bin/bash
# $Source: /repo/per-bruce.cvs/bin/bash-com.inc,v $
# $Revision: 1.31 $ $Date: 2026/08/04 03:23:47 $ GMT

export PWD Tmp cBin cCurDir cName cPID cTmp1 cTmp2 cTmpF cVer
export gErr gpDebug gpFacility gpLog gpVerbose gpTest SHUNIT_COLOR
export gpTopDir gpConfGlobal gpConfLocal gpConfProj
export gpAuto gpYesNo gpMaxLoop gResponse gMenuTitle

# Naming Convention
# gpVar - command line parameters. These can override gcVars with same
#         base name.
# gcVar - config vars that are read from a config file.
# gVar - global variables. Should be few. Use functions to reduce these.
# cVar - These are global config vars, fixed values for the script.
# pVar - a positional variable passed into a function (local).
# tVar - a local variable used in a function.
# fFunction - a function unique to this script
# fComFunction - Most functions in bash-com.inc begin with fCom

# --------------------------------
fBashComInternalDoc() {
    return
    # This should be the first =internal-pod
    cat <<\EOF >/dev/null
=internal-pod

=internal-for text ========================================

=internal-for html <hr/>

=internal-head1 bash-com.inc Internal Documentation

=internal-head2 Template Use

=internal-head3 Configuration

 * Copy template.sh to your script file.
 * Your script, bash-com.inc, and bash-com.test need to be in the same directory.
 * Globally replace SCRIPTNAME with the name of your script file.
 * Update the getopts in the "Get Args Section". Add your script's options.
 * Loop: document (with POD), add tests, add validate functions
 * Loop: add unit test function, add functions, test

=internal-head3 Block Organization

 * Configuration - exit if errors
 * Get Args - exit if errors
 * Verify external progs - exit if errors
 * Run tests - if gpTest is set
 * Validate Args - exit if errors
 * Verify connections work - exit if errors
 * Read-only functional work - exit if errors
 * Write functional work - now you are committed! Try to keep going if errors
 * Output results and/or launch next process

To avoid a lot of rework and manual rollbacks, put-off I<writes> that
cannot undone. Do as much as possible to make sure the script will be able
to complete write operations.

For example, B<do not do this:> collect information, transform it,
write it to a DB, then start the next process on another server.
Whoops, that server cannot be accessed, so the DB update is not valid!
Gee, why didn't you verify all the connections you will need, before
committing to the DB?!  Even if you did check, the connection could
have failed after the check, so maybe write to a tmp DB, then when all
is OK, then update the master DB with the tmp DB changes.

Where ever possible make your scripts "re-entrant". Connections can
fail at anytime and scripts can be killed at anytime; How can any
important work be continued or work rolled-back? Planing for
"failures" is NOT planning to fail; it is what a professional engineer
does to design in quality.

=internal-head3 Variable Naming Convention

Prefix codes are used to show the B<"scope"> of variables:

 gVar - global variable (may even be external to the script)
 pVar - a function parameter I<local>
 gpVar - global parameter, i.e. may be defined external to the script
 cVar - global constant (set once)
 tVar - temporary variable (usually I<local> to a function)
 fFun - function

All UPPERCASE variables are I<only> used when they are required by other
programs or scripts.

If you have exported variables that are shared across scritps, then
this convention can be extended by using prefixes that are related to
where the variables are set.

=internal-head3 Global Variables

For more help, see the Globals section in fUsage.

 gpLog - -l
 gpVerbose - -v, -vv
 gpDebug - -x, -xx, ...
 gpTest - -t
 Tmp - personal tmp directory.  Usually set to: /tmp/$USER
 cTmpF - tmp file prefix.  Includes $$ to make it unique
 cTmp1 - a temp file with a pattern that fCleanUp will remove
 gErr - error code (0 = no error)
 cName - script's name taken from $0
 cCurDir - current directory
 cBin - directory where the script is executing from
 cVer - current version. For example, if using CVS:
        # shellcheck disable=SC2016
        cVer='$Revision: 1.31 $'

 gpTopDir     - top dir for config and project files
               default: ~/.config/$cName
 gpConfGlobal - -g config file
               default: ~/.config/$cName/$cName.conf
 gpConfLocal  - -l config file
               default: ./.$cName.conf
 gpConfProj   - -L config file
               default: $gpTopDir/${cName}-proj.conf}
 gpAuto      - if 1, fComYesNo uses gpYesNo instead of prompting
 gpYesNo     - "y" or "n", used by fComYesNo when gpAuto=1
 gpMaxLoop   - max prompt iterations for fComSelect/fComMenu
 gResponse   - last response captured by fComSelect/fComYesNo/fComGetConfig
 gMenuTitle  - last title used by fComMenu

=internal-head3 Documentation Format

POD is use to format the script's documentation. Sure MarkDown could
have been used, but it didn't exist 20 years ago. POD text can be
output as text, man, html, pdf, texi, just usage, and even MarkDown

Help for POD can be found at:
L<perlpod - the Plain Old Documentation format|https://perldoc.perl.org/perlpod>

The documentation is embedded in the script so that it is more likely
to be updated. Separate doc files seem to I<always> drift from the
code. Feel free to delete any documentation, if the code is clear
enough.  BUT I<clean up your code> so that the code I<really> is
clear.

The internal documentation uses POD commands that begin with "=internal-".
See fComInternalDoc() for how this is used.

Also TDD (Test Driven Development) should make refactoring easy,
because the tests are often embedded in the script. See template.sh
for how.

=internal-head2 Common Script Functions

=internal-cut
EOF
} # fBashComInternalDoc

# ========================================
# Common Functions

# -------------------
fComSetGlobals() {
    # -------------------
    # Set name of this script
    local tName=${0##*/}
    cName=${cName:-$tName}

    # -------------------
    # Set current directory location in PWD and cCurDir, because with cron
    # jobs PWD is not set.
    if [[ -z "$PWD" ]]; then
        PWD=$(pwd)
    fi
    cCurDir=${cCurDir:-$PWD}

    # -------------------
    # Define the location of the com script if not already set
    if [[ -z "$cBin" ]]; then
        cBin=${0%/*}
        if [[ "$cBin" = "." ]]; then
            cBin=$PWD
        fi
        cd $cBin
        cBin=$PWD
        cd $cCurDir
    fi

    # -------------------
    # Setup log variables
    # -x
    gpDebug=${gpDebug:-0}
    # -v
    gpVerbose=${gpVerbose:-0}
    # -l
    gpLog=${gpLog:-0}
    gpFacility=${gpFacility:-user}
    gErr=0

    # -------------------
    # If your script defines a version with the Revision keyword, clean it
    # shellcheck disable=SC2016
    #cVer='$Revision: 1.31 $'

    if [[ "$cVer" != "${cVer#*' '}" ]]; then
        cVer=${cVer#*' '}
        cVer=${cVer%' '*}
    fi

    # -------------------
    # Setup a temporary directory for each user/script.
    Tmp=${Tmp:-"/tmp/$USER/$cName"}
    if [[ ! -d $Tmp ]]; then
        mkdir -p $Tmp 2>/dev/null
        if [[ ! -d $Tmp ]]; then
            fError -m "Could not find directory $Tmp (\$Tmp)." -l $LINENO
        fi
    fi

    # -------------------
    # Define temporary file names used by this script.  The
    # variables for the file names can be any name, but the file
    # name pattern should be:
    # "${cTmpF}-*.tmp"
    cPID=$$
    cTmpF=$Tmp/file-$cPID
    if [[ $gpDebug -ne 0 ]]; then
        cTmpF=$Tmp/file
        rm -f ${cTmpF}*.tmp 2>/dev/null
    fi
    # shellcheck disable=SC2034
    cTmp1=${cTmpF}-1.tmp
    # shellcheck disable=SC2034
    cTmp2=${cTmpF}-2.tmp
    # shellcheck disable=SC2172
    trap fCleanUp 1 2 3 4 5 6 7 8 10 11 12 13 14 15

    # -------------------
    # Config file locations (used by -g, -l, -L options of
    # fComGetConfig / fComSetConfig / fComUnsetConfig)
    gpTopDir=${gpTopDir:-$PWD}
    gpConfGlobal=${gpConfGlobal:-$HOME/.config/$cName.conf}
    gpConfLocal=${gpConfLocal:-./.$cName.conf}
    gpConfProj=${gpConfProj:-$gpTopDir/${cName}-proj.conf}

    # -------------------
    # Defaults for fComSelect, fComYesNo, fComMenu
    gpAuto=${gpAuto:-0}
    gpYesNo=${gpYesNo:-""}
    gpMaxLoop=${gpMaxLoop:-30}

    # -------------------
    # fComGetConfig output globals
    gResponse=""

    # -------------------
    gpTest=${gpTest:-""}
    return 0

    cat <<EOF >/dev/null
=internal-pod

=internal-head3 fComSetGlobals

Set initial values for all of the globals use by this script. The ones
that begin with "gp" can usually be overridden by setting them before
the script is run.

This also sets the default config file locations:

 gpTopDir     - default: ~/.config/$cName
 gpConfGlobal - -g target, default: ~/.config/$cName/$cName.conf
 gpConfLocal  - -l target, default: ./.$cName.conf
 gpConfProj   - -L target, default: $gpTopDir/.proj/$cName.conf

=internal-cut
EOF
} # fComSetGlobals

# --------------------------------
fComCleanUp() {
    # shellcheck disable=SC2172
    trap - 1 2 3 4 5 6 7 8 10 11 12 13 14 15
    if [[ $gpDebug -eq 0 ]]; then
        'rm' -f ${cTmpF}*.tmp 2>/dev/null
    fi
    ##fLog2 notice "Done" $LINENO 9900
    exit $gErr

    cat <<EOF >/dev/null
=internal-pod

=internal-head3 fCleanUp

Called when script ends (see trap) to remove temporary files.
Except if gpDebug != 0, then tmp files are not removed.

=internal-cut
EOF
} # fComCleanUp

# --------------------------------
fComCheckDeps() {
    local pRequired="$1"
    local pOptional="$2"

    local tProg
    local tErr=0
    local tComReq='logger pod2text pod2usage'
    local tComOpt='pod2html pod2man pod2markdown tidy shunit2.1 gawk sed tr fmt'

    for tProg in $pOptional $tComOpt; do
        if ! which $tProg &>/dev/null; then
            echo "Optional: Missing $tProg"
            tErr=1
        fi
    done

    for tProg in $pRequired $tComReq; do
        if ! which $tProg &>/dev/null; then
            echo "Required: Missing $tProg"
            tErr=2
        fi
    done

    if [[ $tErr -eq 2 ]]; then
        fError -m "Missing one or more required programs." -l $LINENO
    fi
    if [[ $tErr -eq 1 ]]; then
        fLog -p warning -m "Missing some some optional programs." -l $LINENO
    fi
    return 0

    cat <<EOF >/dev/null
=internal-pod

=internal-head3 fComCheckDeps "pRequired List" "pOptional List"

Check for required and optional programs or scripts used by this script.
If any required programs are missing, exit the script.

=internal-cut
EOF
} # fComCheckDeps

# --------------------------------
fComInternalDoc() {
    local pAll="$1"

    if [[ ".$pAll" = ".-a" ]]; then
        awk '
                        /^=internal-pod/,/^=internal-cut/ {
                                sub(/^=internal-/,"=");
                        }
                        {
                                print $0;
                        }
                '
    else
        awk '
                        /^=internal-pod/,/^=internal-cut/ {
                                sub(/^=internal-/,"=");
                                print $0;
                        }
                '
    fi
    return 0

    cat <<EOF >/dev/null
=internal-pod

=internal-head3 fComInternalDoc [-a]

This function collects all of the "internal-pod" documentation from
stdin and it outputs to stdout.

If -a option is givein, then ALL pod documentation is output.

=internal-cut
EOF
} # fComInternalDoc

# --------------------------------
fComUsage() {
    local pFileList=""
    local pStyle="long"
    local pInt=0
    local pAll="-n"
    local pTitle="$cName Usage"

    local tProg=""
    local tFileList=""
    local tTidy='tidy -m -q -i -w 78 -asxhtml --break-before-br yes --indent-attributes yes --indent-spaces 2 --tidy-mark no --vertical-space no'

    local OPTIND=1
    local OPTARG
    local tArg

    while getopts :f:s:t:ia tArg; do
        case $tArg in
            f) pFileList="$pFileList $OPTARG" ;;
            s) pStyle="$OPTARG" ;;
            t) pTitle="$OPTARG" ;;
            i) pInt=1 ;;
            a)
                pInt=1
                pAll='-a'
                ;;
            :) fError -i -m "fComUsage: Value required for option: -$OPTARG" -l $LINENO ;;
            \?) fError -i "fComUsage: Unknown option: $OPTARG" -l $LINENO ;;
        esac
    done
    shift $((OPTIND - 1))
    if [[ $# -ne 0 ]]; then
        fError -i -m "fComUsage: Unknown option: $*" -l $LINENO
    fi
    while [[ $# -ne 0 ]]; do
        shift
    done

    case $pStyle in
        short | usage)
            tProg=pod2usage
            if [[ $pInt -ne 0 ]]; then
                # This fails, don't know why
                tProg='pod2text | head -n 24'
                tProg=pod2text
            fi
            ;;
        man)
            tProg=pod2man
            if [[ $pInt -ne 0 ]]; then
                tProg=pod2text
            fi
            ;;
        long | text)
            tProg=pod2text
            ;;
        html)
            tProg=pod2html
            ;;
        md)
            tProg=pod2markdown
            ;;
        *)
            tProg=pod2text
            ;;
    esac

    # Validate
    pFileList=$pFileList
    if [[ -z "$pFileList" ]]; then
        fError -i -m "fComUsage missing pFileList" -l $LINENO
    fi
    for tFile in $pFileList; do
        if [[ -r $tFile ]]; then
            tFileList="$tFileList $tFile"
        else
            fError -i -m "fComUsage cannot read: $tFile" -l $LINENO
        fi
    done
    tFileList=$tFileList
    if [[ -z "$tFileList" ]]; then
        fError -i -m "fComUsage missing a valid pFileList" -l $LINENO
    fi

    if [[ $pInt -eq 0 ]]; then
        if [[ "$pStyle" = "html" ]]; then
            # shellcheck disable=SC2002
            cat $pFileList | $tProg --title="$pTitle" | $tTidy
            rm pod2htmd.tmp >/dev/null 2>&1
        else
            if [[ "$pStyle" = "short" ]]; then
                # shellcheck disable=SC2002
                cat $tFileList | pod2text | head -n 20
            else
                # shellcheck disable=SC2002
                cat $tFileList | $tProg
            fi
        fi
    else
        if [[ "$pStyle" = "html" ]]; then
            # shellcheck disable=SC2002
            cat $pFileList | fComInternalDoc $pAll | $tProg --title="$pTitle" | $tTidy
        else
            # shellcheck disable=SC2002
            cat $pFileList | fComInternalDoc $pAll | $tProg
        fi
    fi

    gErr=1
    fCleanUp
    exit 1

    cat <<EOF >/dev/null
=internal-pod

=internal-head3 fComUsage -f pFileList -s pStyle [-t pTitle] [-i] [-a]

=internal-over 4

=internal-item B<-f pFileList> - list of file names

=internal-item B<-s pStyle> - output style

 short|usage - usage only (does not work with -i or -a)
 man         - all, man format (does not work with -i or -a)
 long|text   - all text format
 html        - all, html format (see -t)
 md          - all, markdown format

=internal-item B<-t> - title for HTML style

=internal-item B<-i> - internal doc only (see fComInternalDoc)

=internal-item B<-a> - all docs: user and internal (see fComInternalDoc)

=internal-back

=internal-cut
EOF
} # fComUsage

# ------------------
fFmtLog() {
    local pLevel=$1
    local pMsg="$2"
    local pLine=$3
    local pErr=${4:-''}
    local pForce=${5:-0}

    local tDebugLevel
    local tLevel
    local tLine
    local tErr

    # Set any missing globals
    gpLog=${gpLog:-0}
    gpFacility=${gpFacility:-user}
    gpVerbose=${gpVerbose:-0}
    gpDebug=${gpDebug:-0}
    tLevel=$pLevel

    # Normalize level names (folded in from gitproj-com.inc fComFmtLog)
    case $pLevel in
        emerg | emergency)
            pLevel=emerg
            tLevel=$pLevel
            ;;
        alert) : ;;
        crit | critical)
            pLevel=crit
            tLevel=$pLevel
            ;;
        err | error)
            pLevel=err
            tLevel=$pLevel
            ;;
        warning | warn)
            pLevel=warning
            tLevel=$pLevel
            ;;
        notice | info | debug | debug-*) : ;;
        *)
            pMsg="Internal: (Unknown level: $pLevel) $pMsg"
            pLevel=err
            tLevel=$pLevel
            ;;
    esac

    # Check debug
    if [[ $gpDebug -eq 0 && "${pLevel%-*}" = "debug" ]]; then
        return
    fi
    if [[ $gpDebug -ne 0 && "${pLevel%%-*}" != "$pLevel" ]]; then
        tDebugLevel=${pLevel##*-}
        if [[ $tDebugLevel -gt $gpDebug ]]; then
            return
        fi
        tLevel=debug
    fi

    # Check verbose (skipped if pForce)
    if [[ $pForce -eq 0 ]]; then
        if [[ $gpVerbose -eq 0 && ("$pLevel" = "notice" || "$pLevel" = "info") ]]; then
            return
        fi
        if [[ $gpVerbose -eq 1 && "$pLevel" = "info" ]]; then
            return
        fi
    fi

    # LineNo format
    tLine=""
    if [[ -n "$pLine" ]]; then
        tLine="[$pLine]"
    fi

    # Err format
    tErr=""
    if [[ -n "$pErr" ]]; then
        tErr="($pErr)"
    fi

    # Output
    if [[ $gpLog -eq 0 ]]; then
        echo "${cName} $pLevel: $pMsg $tLine$tErr" 1>&2
    else
        logger -s -t $cName -p $gpFacility.$tLevel "$pLevel: $pMsg $tLine$tErr"
    fi
    return 0

    cat <<EOF >/dev/null
=internal-pod

=internal-head3 fFmtLog pLevel "pMsg" pLine pErr [pForce]

This function formats and outputs a consistent log message output.
See: fLog2, fLog, fError2, and fError.

If pForce is non-zero the gpVerbose check is skipped (the message is
output regardless of gpVerbose). Debug-level checks still apply.

=internal-cut
EOF
} # fFmtLog

# ------------------
fLog2() {
    local pLevel="alert"
    local pMsg="Missing message"
    local pLine=""
    local pErr=""

    # Get args
    case $# in
        1)
            pLevel=$1
            ;;
        2)
            pLevel=$1
            pMsg=$2
            ;;
        3)
            pLevel=$1
            pMsg=$2
            pLine=$3
            ;;
        4)
            pLevel=$1
            pMsg=$2
            pLine=$3
            pErr=$4
            ;;
        *)
            pLevel=$1
            pMsg=$2
            pLine=$3
            pErr=$4
            shift 4
            pMsg="$pMsg - $*"
            ;;
    esac

    fFmtLog $pLevel "$pMsg" $pLine "$pErr"
    return 0

    cat <<EOF >/dev/null
=internal-pod

=internal-head3 fLog2 pLevel "pMsg" [$LINENO] [pErr]

pLevel - emerg alert crit err warning notice info debug debug-N

See Globals: gpLog, gpFacility, gpVerbose, gpDebug

=internal-head4 fLog2 Examples:

 fLog2 warning "Missing awk" $LINENO 8
 fLog2 notice "Output only if -v" $LINENO 8
 fLog2 info "Output only if -vv" $LINENO 8
 fLog2 debug "Output only if $gpDebug > 0" $LINENO
 fLog2 debug-3 "Output only if $gpDebug > 0 and $gpDebug <= 3" $LINENO

=internal-cut
EOF
} # fLog2

# --------------------------------
fError2() {
    # Usage:
    #     fError2 pMsg [pLine [pErr]]
    # Print the error message.  Then call fCleanUp, and exit

    local pMsg="$1"
    local pLine=$2
    local pErr=$3

    fLog2 crit "$pMsg" ${pLine:-.} ${pErr:-1}
    fUsage short
    return 0

    cat <<EOF >/dev/null
=internal-pod

=internal-head3 fError2 "pMsg" [$LINENO] [pErr]

This will call: fLog2 crit "pMsg" pLine pErr

Then it will call "fUsage short", which will exit after fCleanUp.

=internal-cut
EOF
} # fError2

# ------------------
fLog() {
    # If no options passed, fall back to fLog2 (positional style)
    if [[ $# -eq 0 || "${1:0:1}" != "-" ]]; then
        fLog2 "$@"
        return
    fi

    local pLevel="notice"
    local pMsg=""
    local pLine=""
    local pErr=""
    local pInt=0
    local pForce=0

    local OPTARG=1
    local OPTIND
    local tArg
    while getopts :m:p:l:e:if tArg; do
        case $tArg in
            m) pMsg="${OPTARG}" ;;
            l) pLine="${OPTARG}" ;;
            p) pLevel="${OPTARG}" ;;
            e)
                pErr="${OPTARG}"
                gErr="${OPTARG}"
                ;;
            i) pInt=1 ;;
            f) pForce=1 ;;
            :) echo "Internal: Value required for option: -$OPTARG [$LINENO]" ;;
            \?) echo "Internal: Unknown option: $OPTARG [$LINENO]" ;;
        esac
    done
    shift $((OPTIND - 1))
    while [[ $# -ne 0 ]]; do
        shift
    done

    if [[ $pInt -eq 1 ]]; then
        pMsg="Internal: $pMsg"
    fi

    fFmtLog $pLevel "$pMsg" $pLine "$pErr" $pForce
    return 0

    cat <<EOF >/dev/null
=internal-pod

=internal-head3 fLog -m pMsg [-p pLevel] [-l $LINENO] [-e pErr] [-i] [-f]

This is like fLog2, but the arguments can be in any order.

 -i adds an "Internal: " prefix to pMsg
 -f forces output regardless of gpVerbose level
    (debug-level rules still apply)

See fLog2. See also global gpFacility

=internal-cut
EOF
} # fLog

# --------------------------------
fError() {
    # If no options passed, fall back to fError2 (positional style)
    if [[ $# -eq 0 || "${1:0:1}" != "-" ]]; then
        fError2 "$@"
        return
    fi

    local pContinue=0
    local pErr=1
    local pInt=0
    local pLine="."
    local pMsg="Error"

    local OPTIND=1
    local OPTARG
    local tArg

    while getopts :ce:il:m:n tArg; do
        case $tArg in
            c) pContinue=1 ;;
            e) pErr="${OPTARG}" ;;
            i) pInt=1 ;;
            l) pLine="${OPTARG}" ;;
            m) pMsg="${OPTARG}" ;;
            n) pContinue=1 ;;
            :) echo "Internal: Value required for option: -$OPTARG [$LINENO]" ;;
            \?) echo "Internal: Unknown option: $OPTARG [$LINENO]" ;;
        esac
    done
    shift $((OPTIND - 1))
    if [[ $# -ne 0 ]]; then
        pMsg="$pMsg $*"
    fi
    while [[ $# -ne 0 ]]; do
        shift
    done
    gErr=$pErr

    if [[ $pInt -eq 1 ]]; then
        pMsg="Internal: $pMsg"
    fi

    # If -c or -n: log at "err" so the script can continue, and return
    if [[ $pContinue -eq 1 ]]; then
        fLog -p err -l $pLine -e $pErr -m "$pMsg"
        if [[ $pInt -eq 1 || $gpDebug -ge 2 ]]; then
            fComStackTrace
        fi
        return 1
    fi

    fLog -p crit -l $pLine -e $pErr -m "$pMsg"
    if [[ $pInt -eq 1 || $gpDebug -ge 2 ]]; then
        fComStackTrace
    fi
    if [[ $pInt -eq 0 ]]; then
        fUsage usage
    fi
    exit 1

    cat <<EOF >/dev/null
=internal-pod

=internal-head3 fError -m pMsg [-c|-n] [-l $LINENO] [-e pErr] [-i]

Log pMsg at "crit" level (or "err" with -c/-n) and exit (unless -c/-n).

 -c, -n  do not exit; log at "err" instead of "crit", then return 1
 -i      prepend "Internal: " to pMsg, and call fComStackTrace
 -l      LINENO of the caller
 -e      error code (sets gErr)

If gpDebug >= 2, fComStackTrace is also called.

If neither -c, -n nor -i are given, "fUsage usage" is called before
exiting.

=internal-cut
EOF
} # fError

# --------------------------------
fTimeoutFunction() {
    # Number of seconds
    local -i pTimeout
    # Cmd including args
    local pCmd

    pTimeout=$1
    pCmd="$2"

    declare -x tChild
    (
        eval "$pCmd" &>/dev/null &
        tChild=$!
        trap -- "" SIGTERM
        (
            sleep $pTimeout
            kill $tChild 2>/dev/null
        ) &
        wait $tChild
    )
    return 0

    cat <<EOF >/dev/null
=internal-pod

=internal-head3 fTimeoutFunction pSec "pCmd [args...]"

Algorithm idea came from:

https://stackoverflow.com/questions/9954794/execute-a-shell-function-with-timeout

=internal-cut
EOF
} # fTimeoutFunction

# ------------------
fComStackTrace() {
    local i
    local tSrc
    local tLine
    local tName
    local tOutput

    # Credit: https://gist.github.com/ahendrix/7030300
    if [[ ${#FUNCNAME[@]} -le 2 ]]; then
        return 0
    fi

    if [[ ${gpLog:-0} -ne 0 ]]; then
        tOutput="logger -s -t ${cName:-fComStackTrace} -p ${gpFacility:-user}.debug"
    else
        tOutput="cat"
    fi

    tSrc=${BASH_SOURCE[0]}
    tSrc=${tSrc##*/}
    tLine=${BASH_LINENO[0]}
    tName="${FUNCNAME[1]}()"
    echo "Stack trace at: ${tSrc}:${tLine} ${tName}" | $tOutput
    for ((i = 1; i < ${#FUNCNAME[@]} - 1; i++)); do
        tSrc=${BASH_SOURCE[$i + 1]}
        tSrc=${tSrc##*/}
        tLine=${BASH_LINENO[$i]}
        tName="${FUNCNAME[$i]}()"
        echo " $i: ${tSrc}:${tLine} ${tName}" | $tOutput
    done

    return 0

    cat <<EOF >/dev/null
=internal-pod

=internal-head3 fComStackTrace

Output a stack trace of bash functions, showing source file, line, and
function name. If gpLog is set, the trace is sent to syslog at debug level.

=internal-cut
EOF
} # fComStackTrace

# --------------------
fComSelect() {
    PS3="$1"
    local pList="$2"
    local pHelp="$3"
    local tResponse
    local tStatus=1
    local tMaxLoop=${gpMaxLoop:-30}

    # Loop until a valid selection, or QUIT
    select tResponse in QUIT HELP $pList; do
        ((--tMaxLoop))
        if [[ $tMaxLoop -le 0 ]]; then
            fLog -p warning -m "Too many responses." -l $LINENO
            tResponse="QUIT"
            break
        fi
        echo
        case $tResponse in
            QUIT)
                fLog -p warning -m "Quitting." -l $LINENO
                break
                ;;
            HELP)
                echo 1>&2
                echo "$pHelp" 1>&2
                echo 1>&2
                continue
                ;;
        esac
        if [[ -n "$tResponse" ]]; then
            tStatus=0
            break
        fi
        if [[ -z "$tResponse" ]]; then
            fLog -p warning -m "Invalid selection" -l $LINENO
            continue
        fi
    done

    PS3=""
    gResponse=$tResponse
    return $tStatus

    cat <<EOF >/dev/null
=internal-pod

=internal-head3 fComSelect pPrompt pList pHelp

Use bash "select" to prompt the user from pList. The list is always
prefixed with QUIT and HELP. The selected response is stored in
gResponse.

Returns 0 on a valid selection, 1 on QUIT or too many invalid
responses (gpMaxLoop).

=internal-cut
EOF
} # fComSelect

# ------------------
fComYesNo() {
    local pPrompt="$1"
    local -l tResult

    if [[ ${gpAuto:-0} -ne 0 ]]; then
        if [[ "$gpYesNo" = "y" ]]; then
            gResponse="y"
            return 0
        fi
        if [[ "$gpYesNo" = "n" ]]; then
            gResponse="n"
            return 1
        fi
        gResponse="n"
        return 1
    fi
    echo -n "$pPrompt [y/n]? "
    read -r tResult
    if [[ "${tResult::1}" = "y" ]]; then
        gResponse="y"
        return 0
    fi
    gResponse="n"
    return 1

    cat <<EOF >/dev/null
=internal-pod

=internal-head3 fComYesNo pPrompt

If gpAuto is non-zero, gpYesNo determines the answer (no prompt).

 gpYesNo = "y" - set gResponse to "y", return 0
 gpYesNo = "n" - set gResponse to "n", return 1
 anything else - same as "n"

If gpAuto is 0, output pPrompt and read the response. A response that
starts with "y" returns 0, anything else returns 1. gResponse is set
either way.

=internal-cut
EOF
} # fComYesNo

# ------------------
fComMenu() {
    local pTitle=$1
    local pMenu=$2
    local pActn=$3
    declare -x gMenuTitle
    local -a tMenu
    local tMaxLoop=${gpMaxLoop:-30}
    local tActn
    local tSize
    local tItem
    local tPrompt
    local tSelect
    local tSelected
    local tCmd
    local tFun
    local tIFS

    tSize=$(eval echo "\${#${pMenu}[@]}")
    ((--tSize))
    tPrompt="$(eval echo $(echo \${${pMenu}[0]}))"
    while true; do
        ((--tMaxLoop))
        if [[ $tMaxLoop -le 0 ]]; then
            fLog -p warning -m "Too many responses." -l $LINENO
            break
        fi
        gMenuTitle=$pTitle
        tSelected=$(echo $pTitle | sed 's/{[^}]*} //g')
        echo
        echo "Current selections:"
        tIFS="$IFS"
        IFS=';'
        printf '\t%s\n' $tSelected
        IFS="$tIFS"
        for ((tItem = 1; tItem <= tSize; tItem++)); do
            tMenu[$tItem]="$(eval echo $(echo \${${pMenu}[$tItem]}))"
            echo -e "  $tItem)\n      ${tMenu[$tItem]}" \
                | sed 's/{[^}]*} //g' | fmt -t
        done
        echo -n "$tPrompt "
        read tSelect
        if [[ $gpDebug -ne 0 ]]; then
            echo "$tSelect"
        fi
        if [[ -z "$tSelect" ]]; then
            continue
        fi
        if ! echo "$tSelect" | grep -q '[0-9]'; then
            continue
        fi
        if [[ $tSelect -lt 1 || $tSelect -gt $tSize ]]; then
            continue
        fi
        tActn="$(eval echo $(echo \${${pActn}[$tSelect]}))"
        if [[ -z "$tActn" ]]; then
            fError -c -i -m "Problem with action" -l $LINENO
            continue
        fi
        tCmd=${tActn%% *}
        tFun=${tActn#* }
        case $tCmd in
            help)
                fComMenuHelp $pActn
                tMaxLoop=${gpMaxLoop:-30}
                ;;
            func)
                eval $tFun
                tMaxLoop=${gpMaxLoop:-30}
                ;;
            menu)
                fComMenu "${pTitle};${tMenu[$tSelect]}" ${tFun}
                tMaxLoop=${gpMaxLoop:-30}
                ;;
            back)
                return 0
                ;;
            *)
                echo
                fError -c -i -m "Problem with action" -l $LINENO
                ;;
        esac
        continue
    done
    return 1

    cat <<EOF >/dev/null
=internal-pod

=internal-head3 fComMenu pTitle pMenuArrayName pActnArrayName

A bash-driven hierarchical menu. pMenuArrayName names an array whose
[0] element is the prompt and whose [1..N] elements are the menu
items. pActnArrayName names a parallel array whose [0] element is help
text and whose [1..N] elements are actions.

Each action is a string of the form "command [args]". Supported commands:

 help          - call fComMenuHelp passing pActnArrayName
 func pFn ...  - eval pFn with args; \$gMenuTitle holds the breadcrumb trail
 menu pM pA    - recurse into fComMenu with pM and pA
 back          - return to the calling menu

The breadcrumb trail is built from each selected item's text. Text
matching '{...}' is removed when displayed; the bracketed text can be
parsed by callers from \$gMenuTitle.

=internal-cut
EOF
} # fComMenu

# ------------------
fComMenuHelp() {
    local pHelp=$1
    local tHelp

    tHelp="$(eval echo -e "$(echo -e "\${${pHelp}[0]}")")"
    echo
    echo '--------------------'
    echo -e "$tHelp" | fmt -t | grep .
    echo '--------------------'

    return 0

    cat <<EOF >/dev/null
=internal-pod

=internal-head3 fComMenuHelp pActnArrayName

Output element [0] of the action array (the help text for the menu),
framed by separator lines.

=internal-cut
EOF
} # fComMenuHelp

# --------------------
fComAllMustBeReadable() {
    local pDir=${1:-$PWD}
    local tFound

    tFound=$(find $pDir -type d \! -executable -print 2>/dev/null)
    if [[ -n "$tFound" ]]; then
        fError -c -m "All directories must be executable, under $pDir" -l $LINENO
        return 1
    fi

    tFound=$(find $pDir \! -readable -print 2>/dev/null)
    if [[ -n "$tFound" ]]; then
        fError -c -m "All directories and files must be readable, under $pDir" -l $LINENO
        return 1
    fi

    return 0

    cat <<EOF >/dev/null
=internal-pod

=internal-head3 fComAllMustBeReadable [pDir]

Verify every directory under pDir is executable and every file is
readable. pDir defaults to \$PWD. Returns 0 on success, 1 if any item
is missing the required permission.

=internal-cut
EOF
} # fComAllMustBeReadable

# --------------------------------
fComCheckConfFile() {
    # Internal helper: ensure a config file exists, creating it (and
    # its parent directory) if missing. Returns 0 on success, 1 on
    # failure. Used by fComSetConfig / fComGetConfig / fComUnsetConfig
    # for the -g, -l, -L options. Style mirrors the -f branch in
    # fComSetConfig.
    local pFile=$1

    if [[ -f $pFile ]]; then
        return 0
    fi
    fLog -p warning -m "Could not find $pFile, it will be created" -l $LINENO
    mkdir -p "${pFile%/*}" 2>/dev/null
    touch $pFile 2>/dev/null
    if [[ ! -f $pFile ]]; then
        fError -c -m "Could not create $pFile" -l $LINENO
        return 1
    fi
    return 0
} # fComCheckConfFile

# --------------------------------
fComSetConfig() {
    local pScope=""
    local pType=""
    local -l pKey=""
    local pValue=""
    local pAdd=""

    local OPTIND=1
    local OPTARG
    local tArg
    while getopts :abf:gik:lLv: tArg; do
        # shellcheck disable=SC2213,SC2220
        case $tArg in
            f)
                if ! fComCheckConfFile ${OPTARG}; then
                    return 1
                fi
                ;;
            g)
                if ! fComCheckConfFile $gpConfGlobal; then
                    return 1
                fi
                ;;
            l)
                if ! fComCheckConfFile $gpConfLocal; then
                    return 1
                fi
                ;;
            L)
                if ! fComCheckConfFile $gpConfProj; then
                    return 1
                fi
                ;;
        esac
        case $tArg in
            a) pAdd="--add" ;;
            b) pType="--bool" ;;
            f) pScope="--file ${OPTARG}" ;;
            g) pScope="--file $gpConfGlobal" ;;
            i) pType="--int" ;;
            k) pKey="${OPTARG}" ;;
            l) pScope="--file $gpConfLocal" ;;
            L) pScope="--file $gpConfProj" ;;
            v) pValue="${OPTARG}" ;;
            :)
                fError -c -i -m "fComSetConfig: Value required for option: $OPTARG" -l $LINENO
                return 1
                ;;
            \?)
                fError -c -i -m "fComSetConfig: Unknown option: $OPTARG" -l $LINENO
                return 1
                ;;
        esac
    done
    if [[ -z "$pKey" ]]; then
        fError -c -i -m "fComSetConfig: missing key" -l $LINENO
        return 1
    fi
    if [[ -z "$pValue" ]]; then
        fError -c -i -m "fComSetConfig: missing value for $pKey" -l $LINENO
        return 1
    fi
    if [[ -z "$pScope" ]]; then
        # Default to the local config file
        if ! fComCheckConfFile $gpConfLocal; then
            return 1
        fi
        pScope="--file $gpConfLocal"
    fi

    # shellcheck disable=SC2086
    git config $pScope $pAdd $pType $pKey "$pValue"
    return $?

    cat <<EOF >/dev/null
=internal-pod

=internal-head3 fComSetConfig -k pKey -v pValue [-g|-l|-L|-f pFile] [-b|-i] [-a]

Set a key/value pair in a config file. Uses "git config --file" so the
file format is INI-style. The target file is created (with its parent
directory) if it does not exist.

Only one of -g, -l, -L, -f is used (last wins). If none, -l is the default.

 -k pKey   - required, the key (lowercased)
 -v pValue - required, the value
 -g        - write to gpConfGlobal (default ~/.config/\$cName/\$cName.conf)
 -l        - write to gpConfLocal  (default ./.\$cName.conf)
 -L        - write to gpConfProj   (default \$gpTopDir/.proj/\$cName.conf)
 -f pFile  - write to pFile
 -b        - normalize value as boolean (true/false)
 -i        - normalize value as integer with k/m/g suffix
 -a        - --add (allow multiple values for the same key)

Returns 0 on success, 1 on failure.

=internal-cut
EOF
} # fComSetConfig

# --------------------------------
fComGetConfig() {
    local pScope=""
    local pType=""
    local -l pKey=""
    local pDefault=""
    local pFilter=""
    local pExitOnError=0
    local tExit="-c"

    # Globals set by fComGetConfig
    gResponse=""

    local OPTIND=1
    local OPTARG
    local tArg
    while getopts :bd:ef:gik:lLv: tArg; do
        # shellcheck disable=SC2213,SC2220
        case $tArg in
            f)
                if ! fComCheckConfFile ${OPTARG}; then
                    return 1
                fi
                ;;
            g)
                if ! fComCheckConfFile $gpConfGlobal; then
                    return 1
                fi
                ;;
            l)
                if ! fComCheckConfFile $gpConfLocal; then
                    return 1
                fi
                ;;
            L)
                if ! fComCheckConfFile $gpConfProj; then
                    return 1
                fi
                ;;
        esac
        case $tArg in
            b) pType="--bool" ;;
            d) pDefault="${OPTARG}" ;;
            e)
                pExitOnError=1
                tExit=""
                ;;
            f) pScope="--file ${OPTARG}" ;;
            g) pScope="--file $gpConfGlobal" ;;
            i) pType="--int" ;;
            k) pKey="${OPTARG}" ;;
            l) pScope="--file $gpConfLocal" ;;
            L) pScope="--file $gpConfProj" ;;
            v) pFilter="${OPTARG}" ;;
            :)
                fError $tExit -i -m "fComGetConfig: Value required for option: $OPTARG" -l $LINENO
                return 1
                ;;
            \?)
                fError $tExit -i -m "fComGetConfig: Unknown option: $OPTARG" -l $LINENO
                return 1
                ;;
        esac
    done
    if [[ -z "$pKey" ]]; then
        fError $tExit -i -m "fComGetConfig: missing key" -l $LINENO
        return 1
    fi

    # shellcheck disable=SC2086
    gResponse=$(git config --get --includes $pScope $pType $pKey $pFilter 2>/dev/null)
    if [[ -z "$gResponse" ]]; then
        gResponse="$pDefault"
    fi
    echo $gResponse

    if [[ $pExitOnError -eq 0 ]]; then
        return 0
    fi

    # ----------
    if [[ -z "$gResponse" ]]; then
        fError -m "Unexpected: $pKey is not defined!" -l $LINENO
    fi

    if [[ -n "$pDefault" && "$gResponse" = "$pDefault" ]]; then
        fError -m "Unexpected: $pKey should not be set to: $gResponse" -l $LINENO
    fi

    if [[ "$gResponse" = "TBD" || "$gResponse" = "not-defined" ]]; then
        fError -m "Unexpected: $pKey should not be set to: $gResponse" -l $LINENO
    fi
    return 0

    cat <<EOF >/dev/null
=internal-pod

=internal-head3 fComGetConfig -k pKey [-g|-l|-L|-f pFile] [-b|-i] [-d pDefault] [-e] [-v pFilter]

Get the value of pKey from a config file. The value is echoed and also
stored in gResponse.

Only one of -g, -l, -L, -f is used (last wins). If none, all the config
files git config knows about are searched.

The selected config file (-g/-l/-L) is created if it does not exist
(see fComSetConfig's -f style).

 -k pKey      - required, the key (lowercased)
 -g           - gpConfGlobal
 -l           - gpConfLocal
 -L           - gpConfProj
 -f pFile     - pFile
 -b           - normalize as boolean
 -i           - normalize as integer
 -d pDefault  - if not found, return pDefault and set origin to "default"
 -e           - exit script if not found, equal to pDefault, or "TBD"/"not-defined"
 -v pFilter   - value-pattern filter, used when a key has multiple values

Returns 0 on success (even if not found, when -e is not given).

=internal-cut
EOF
} # fComGetConfig

# --------------------------------
fComUnsetConfig() {
    local pScope=""
    local -l pKey=""
    local pAll="--unset"

    local OPTIND=1
    local OPTARG
    local tArg

    while getopts :af:gk:lL tArg; do
        # shellcheck disable=SC2213,SC2220
        case $tArg in
            f)
                if ! fComCheckConfFile ${OPTARG}; then
                    return 1
                fi
                ;;
            g)
                if ! fComCheckConfFile $gpConfGlobal; then
                    return 1
                fi
                ;;
            l)
                if ! fComCheckConfFile $gpConfLocal; then
                    return 1
                fi
                ;;
            L)
                if ! fComCheckConfFile $gpConfProj; then
                    return 1
                fi
                ;;
        esac
        case $tArg in
            a) pAll="--unset-all" ;;
            f) pScope="--file ${OPTARG}" ;;
            g) pScope="--file $gpConfGlobal" ;;
            k) pKey="${OPTARG}" ;;
            l) pScope="--file $gpConfLocal" ;;
            L) pScope="--file $gpConfProj" ;;
            :)
                fError -c -i -m "fComUnsetConfig: Value required for option: $OPTARG" -l $LINENO
                return 1
                ;;
            \?)
                fError -c -i -m "fComUnsetConfig: Unknown option: $OPTARG" -l $LINENO
                return 1
                ;;
        esac
    done

    if [[ -z "$pKey" ]]; then
        fError -c -i -m "fComUnsetConfig: missing key" -l $LINENO
        return 1
    fi
    if [[ -z "$pScope" ]]; then
        if ! fComCheckConfFile $gpConfLocal; then
            return 1
        fi
        pScope="--file $gpConfLocal"
    fi

    # shellcheck disable=SC2086
    git config $pAll $pScope $pKey
    return $?

    cat <<EOF >/dev/null
=internal-pod

=internal-head3 fComUnsetConfig -k pKey [-g|-l|-L|-f pFile] [-a]

Unset pKey in the selected config file. With -a, unset all values for
pKey (when -a is not given and the key has multiple values, git config
returns an error).

The selected config file (-g/-l/-L) is created if it does not exist
(see fComSetConfig's -f style); -f also auto-creates.

=internal-cut
EOF
} # fComUnsetConfig
