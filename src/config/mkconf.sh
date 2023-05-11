
# Input DEF file for: mkver.pl.  All variables must have "export "
# at the beginning.  No spaces around the "=".  And all values
# enclosed with double quotes.  Variables may include other variables
# in their values, if defined after the included variables.

# Set this to latest version of mkver.pl (earlier DEF files should
# still work with newer versions of mkver.pl)
export MkVer="3.1"

export gDBName="biblio_db"
export gHost="127.0.0.1"
export gUserName="bruce"
export gPassHint="b4n"
export gPassCache="$PWD/.pass.tmp"

export gPortLocal="3306"
export gPortRemote="3308"

export gDsn="mysql:dbname=$gDBName;host=$gHost;port=$gPortRemote;charset=UTF8"

export ProdName="PROD-NAME"
# One word [-a-z0-9]
# Required
# %provides ProdName

export ProdAlias="PROD-ALIAS"
# One word [-a-z0-9]

export ProdVer="0.1"
# [0-9]*.[0-9]*{.[0-9]*}
# Requires 2 numbers, 3'rd number is optional
# %version ProdVer

export ProdRC=''
# Release Candidate ver. Can be one or two numbers.
# If set and RELEASE=1
  # %release rc.ProdRC

export ProdBuild='1'
# [0-9.]*
# Required
# If RELEASE=1, and ProdRC=''
  # %release test.ProdBuild

# Generated: ProdBuildTime=YYYY.MM.DD.hh.mm
  # If RELEASE=0, or empty, or unset, then use
  # current time (UTC): %Y.%m.%d.%H.%M
    # %release ProdBuildTime

export ProdSummary="PRODSUMMARY"
# All on one line (< 80 char)
# %product ProdSummary

export ProdDesc="PRODDESC"
# All on one line
# %description ProdDesc

export ProdVendor="COMPANY"
# Required
# %vendor ProdVendor

export ProdPackager="$USER"
# Required
# %packager ProdPackager

export ProdSupport="support\@COMPANY.com"
# Appended to %vendor

export ProdCopyright=""
# Current year if not defined
# %copyright ProdCopyright

export ProdDate=""
# 20[0-9][0-9]-[01][0-9]-[0123][0-9]
# Current date (UTC) if empty

export ProdLicense="./LICENSE"
# Required
# %license ProdLicense

export ProdReadMe="./README"
# Required
# %readme ProdReadMe

# Third Party (if any) If repackaging a product, define these:
export ProdTPVendor=""
# Appended to 
export ProdTPVer=""
# Appended to 
export ProdTPCopyright=""
# Appended to %copyright

export ProdRelServer="rel.DOMAIN.com"
export ProdRelRoot="/release/package"
export ProdRelCategory="software/ThirdParty/$ProdName"
# Generated: ProdRelDir=/released/
# Generated: ProdDevDir=/development/

# Generated: ProdTag=tag-ProdVer-ProdBuild
# (All "." in ProdVer converted to "-")

# Generated: ProdOSDist
# Generated: ProdOSVer
# Generated: ProdOS=mx21.3
# Generated: mx=1
# Generated: mx21.3=1
#       OSDist  OSVer
# linux
#       deb
#       ubuntu  16,18
#       mx      19,20
#       rhes
#       cent
#       fc
# cygwin
#       cygwin
# mswin32
#       win     xp
# solaris
#       sun
# darwin
#       mac

# Generated: ProdArch
# i386
# x86_64

# Output file control variables. (Unused types can be removed.)
# The *File vars can include dir. names
# The *Header and *Footer defaults are more complete than what is
# shown here.

export envFile="conf.env"
export envHeader=""
export envFooter=""

export epmFile="conf.epm"
export epmHeader=""
export epmFooter="# %include epm.list"

export hFile="conf.h"
export hHeader=""
export hFooter=""

export javaPackage="DIR.DIR.DIR"
export javaInterface="ver"
export javaFile="conf.java"
export javaHeader=""
export javaFooter=""

export csNamespace="Supernode"
export csClass="ver"
export csFile="conf.cs"
export csHeader=""
export csFooter=""

export makFile="conf.mak"
export makHeader=""
export makFooter=""

export plFile="conf.pl"
export plHeader=""
export plFooter=""

export phpFile="conf.php"
export phpHeader=""
export phpFooter=""

export xmlFile="conf.xml"
export xmlHeader=""
export xmlFooter=""
