# Input DEF file for: mkver.pl.  All variables must have "export "
# at the beginning.  No spaces around the "=".  And all values
# enclosed with double quotes.  Variables may include other variables
# in their values.

# Set this to latest version of mkver.pl (earlier DEF files should
# still work with newer versions of mkver.pl)
export MkVer="2.2"

export ProdName="libre-bib"
# One word [-a-z0-9]
# Required
# %provides ProdName

export ProdAlias="libre-bib"
# One word [-a-z0-9]

export ProdVer="0.19.1"
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

export ProdSummary="Bibliography tool for LibreOffice"
# All on one line (< 80 char)
# %product ProdSummary

export ProdDesc="Bibliography tool for LibreOffice"
# All on one line
# %description ProdDesc

export ProdVendor="TurtleEngr"
# Required
# %vendor ProdVendor

export ProdPackager="TurtleEngr"
# Required
# %packager ProdPackager

export ProdSupport="turtle.engr@gmail.com"
# Appended to %vendor

export ProdCopyright=""
# Current year if not defined
# %copyright ProdCopyright

export ProdDate=""
# 20[0-9][0-9]-[01][0-9]-[0123][0-9]
# Current date (UTC) if empty

export ProdLicense="../LICENSE"
# Required
# %license ProdLicense

export ProdReadMe="../src/doc/manual/libre-bib.html"
# Required
# %readme ProdReadMe

# Third Party (if any) If repackaging a product, define these:
export ProdTPVendor=""
# Appended to
export ProdTPVer=""
# Appended to
export ProdTPCopyright=""
# Appended to %copyright

export ProdRelServer="moria.whyayh.com"
export ProdRelRoot="https://moria.whyayh.com/rel"
export ProdRelCategory="software/own/$ProdName"
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

export envFile="ver.env"
export envHeader=""
export envFooter=""

export epmFile="ver.epm"
export epmHeader="%include $ProdOSDist.require"
export epmFooter="%include epm.list"

export hFile="ver.h"
export hHeader=""
export hFooter=""

export javaPackage="DIR.DIR.DIR"
export javaInterface="ver"
export javaFile="ver.java"
export javaHeader=""
export javaFooter=""

export csNamespace="Supernode"
export csClass="ver"
export csFile="ver.cs"
export csHeader=""
export csFooter=""

export makFile="ver.mak"
export makHeader=""
export makFooter=""

export plFile="ver.pl"
export plHeader=""
export plFooter=""

export phpFile="ver.php"
export phpHeader=""
export phpFooter=""

export xmlFile="ver.xml"
export xmlHeader=""
export xmlFooter=""
