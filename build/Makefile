# build Makefile

mRoot = dist/opt/libre-bib
mDirList = $(mRoot) dist/usr/local/bin
mCoreDir = ../src
mPhpUnit = phpunit-9.6.13.phar

# ----------------------------------------
all : mkCore

mkCore :
	mkdir -p $(mDirList)
	'rsync' -a $mCoreDir/* $(mRoot)/
	'rsync' -a ../LICENSE $(mRoot)/doc
	'rsync' -a ../README.md $(mRoot)/doc
	find dist -type d -exec chmod a+rx {} \;
	find dist -type f -exec chmod a+r {} \;
	find dist -type f -executable -exec chmod a+rx {} \;

# ln -s /opt/libre-bib/bin/bib /usr/local/bin/bib

# ========================================
build-setup : ../.git/hooks/pre-commit bin/incver.sh bin/phptidy.php bin/rm-trailing-sp bin/shfmt bin/sort-para.sh

$(mRoot)/bin/phptidy.php : bin/phptidy.php
	'rsync' -a $? $@

$(mRoot)/bin : bin/sort-para.sh
	'rsync' -a $? $@

check :
	build/bin/check.sh

install-phpunit :
	if [[ "$$USER" != "root" ]]; then exit 1; fi
	wget https://phar.phpunit.de/$(PhpUnit)
	cp $(PhpUnit) /usr/local/bin
	rm $(PhpUnit)
	chmod a+rx /usr/local/bin/$(PhpUnit)
	cd /usr/local/bin; ln -s $(PhpUnit) phpunit.phar
