# $Header$

# ========================================
SHELL = /bin/bash

include package/ver.mak

# ========================================

mCheck = \
	/sbin/mariadbd \
	/usr/bin/libreoffice \
	/usr/bin/make \
	/usr/bin/mysql \
	/usr/bin/pandoc \
	/usr/bin/perl \
	/usr/bin/php \
	/usr/bin/pod2markdown \
	/usr/bin/pod2pdf \
	/usr/bin/sed \
	/usr/bin/shellcheck \
	/usr/bin/tidy \
	/usr/bin/wget \
	bin/$(mPhpUnit) \
	bin/.phptidy-config.php \
	bin/bash-com.inc \
	bin/bash-com.test \
	bin/bash-fmt \
	bin/incver.sh \
	bin/org2html.sh \
	bin/phptidy.php \
	bin/pre-commit \
	bin/rm-trailing-sp \
	bin/shfmt \
	bin/shunit2.1 \
	bin/sort-para.sh \
	$(mDirLoConf)/biblio.dbf

mCheckProd = \
	src/bin/bash-com.inc \
	src/bin/bash-com.test \
	src/bin/bib \
	src/bin/bib-cmd.mak \
	src/bin/bib-ref-update.php \
	src/bin/bib-status.php \
	src/bin/bib-style-update.php \
	src/bin/convert-lo-2-bib.php \
	src/bin/db-create.sh \
	src/bin/export-lo-2-tcsv.php \
	src/bin/export-lo-2-txt.php \
	src/bin/fixup.sed \
	src/bin/gen-conf-php.sh \
	src/bin/import-tcsv-2-lo-db.php \
	src/bin/import-txt-2-lo.php \
	src/bin/phpunit \
	src/bin/phpunit-10.5.64.phar \
	src/bin/phpunit-11.5.56.phar \
	src/bin/phpunit-12.5.33.phar \
	src/bin/phpunit-13.2.6.phar \
	src/bin/phpunit-8.5.53.phar \
	src/bin/phpunit-9.6.35.phar \
	src/bin/rm-old-files.sh \
	src/bin/rm-old-tables.sh \
	src/bin/sanity-check.sh \
	src/bin/shunit2.1 \
	src/bin/sort-para.sh \
	src/bin/util.php \
	src/bin/valid-conf.sh \
	src/doc/example/biblio-note.txt \
	src/doc/example/biblio.txt \
	src/doc/example/conf.env \
	src/doc/example/conf.env \
	src/doc/example/example-outline.css \
	src/doc/example/example-outline.html \
	src/doc/example/example-outline.odt \
	src/doc/example/example-outline.org \
	src/doc/example/example.odt \
	src/doc/example/key.txt \
	src/doc/manual/doc.css \
	src/doc/manual/libre-bib.html \
	src/doc/manual/libre-bib.md \
	src/doc/manual/libre-bib.org \
	src/etc/bib-style.xml \
	src/etc/bib-template.xml \
	src/etc/biblio.csv \
	src/etc/cite-new.xml \
	src/etc/cite-update.xml \
	src/etc/conf.env \
	src/etc/conf.php \
	src/etc/lo-schema.csv \
	src/test/bootstrap.php \
	src/test/sample/bib-cache/.admin.pass \
	src/test/sample/bib-cache/.pass.tmp \
	src/test/sample/bib-cache/.root.pass \
	src/test/sample/bootstrap.sh \
	src/test/sample/setup-sample-dir.sh \
	src/test/sample/test-doc.odt \
	src/test/sample/test-doc.odt.orig

# libreoffice
mDirLoConf = ~/.config/libreoffice/4/user/database/biblio

# libreoffice-flatpak
# mDirLoConf = ~/.var/app/org.libreoffice.LibreOffice/config/libreoffice/4/user/database/biblio"

# ========================================
usage :
	@echo 'clean - remove tmp files'
	@echo 'dist-clean - cleanup the build area'
	@echo 'create-build-env - set up the build env'
	@echo 'create-prod-env - generate files'
	@echo 'test - run all tests'
	@echo 'build - creates dist/'
	@echo 'install - optional, copy dist/ to /opt/libre-bib2/'
	@echo 'package'
	@echo 'release'

# ========================================
clean :
	-find . -type f -name '*~' -exec rm {} \;
	-find . -type f -name pod2htmd.tmp -exec rm {} \;

dist-clean : clean
	-rm -rf dist

# ========================================
create-build-env : clean \
    package/ver.mak \
    get-dep-pkg \
    get-util-prog \
    check-php-ini \
    package/ver.mak \
    .git/hooks/pre-commit \
    $(mCheck)

mBldPkg = \
	pod2pdf \
	tidy \
	wget

get-dep-pkg :
	sudo apt-get update
	tPkgList=$$(awk '/%requires/ {print $$2}' package/$(ProdOSDist).require); \
	for tPkg in $$tPkgList $(mBldPkg); do \
		if ! dpkg -l $$tPkg >/dev/null 2>&1; then \
			sudo apt-get install -y $$tPkg; \
		fi; \
		if ! dpkg -l $$tPkg >/dev/null 2>&1; then \
			echo "Error: could not install $$tPkg"; \
			exit 1; \
		fi; \
	done

mUtilProgDir = ~/ver/github/app/my-utility-scripts
mUtilProg = \
	bin/.phptidy-config.php \
	bin/bash-fmt \
	bin/incver.sh \
	bin/phptidy.php \
	bin/pre-commit \
	bin/rm-trailing-sp \
	bin/shfmt

get-util-prog : $(mUtilProgDir) $(mUtilProg)
	cd $(mUtilProgDir); \
	git checkout develop; \
	git pull origin develop

$(mUtilProgDir) :
	mkdir -p ~/ver/github/app
	cd ~/ver/github/app; \
	git clone git@github.com:TurtleEngr/my-utility-scripts.git

mPhpIniFile = /etc/php/8.2/cli/php.ini
            # /etc/php/7.4/cli/php.ini
mPhpIni = \
	'memory_limit = -1' \
	'error_reporting = E_ALL' \
	'log_errors_max_len = 0' \
	'zend.assertions = 1' \
	'assert.exception = 1' \
	'xdebug.show_exception_trace = 0' \
	'variables_order = "EGPCS"'

check-php-ini : $(mPhpIniFile)
	@for tLine in $(mPhpIni); do \
		if ! grep -q "^$$tLine" $(mPhpIniFile); then \
			echo "Error: $$tLine not found in $(mPhpIniFile)"; \
			exit 1; \
		fi; \
	done

# ========================================

create-prod-env : create-build-env \
    mk-doc \
    src/etc/lo-schema.csv \
    src/etc/biblio.csv \
    src/etc/conf.php \
    src/doc/example/conf.env \
    $(mCheckProd)
	chmod -R a+r src
	chmod -R a+rx src/bin
	chmod a+rx src/test/*.php
	find src -type d -exec chmod a+rx {} \;

# Use the rules
mk-doc : \
    src/doc/manual/libre-bib.html \
    src/doc/manual/libre-bib.md \
    src/doc/example/example-outline.html

src/etc/lo-schema.csv : src/etc/biblio.csv
	head -n 1 $? | sed 's/,C,254//g; s/,M//g' >$@

src/etc/biblio.csv : $(mDirLoConf)/biblio.dbf
	libreoffice --headless --convert-to csv $?
	mv biblio.csv $@

$(mDirLoConf)/biblio.dbf :
	@echo "Error: It looks like Libreoffice is not installed"
	exit 1

src/etc/conf.php : src/etc/conf.env
	src/bin/gen-conf-php.sh <$? >$@
	chmod a+rx $@

src/doc/example/conf.env : src/etc/conf.env
	sed 's/^export /    #/' <$? >$@
	chmod a+rx $@

# ========================================
test : create-prod-env
	src/bin/bib -T all
	src/bin/bib -T com
	src/bin/phpunit src/test

# ========================================
build : clean clean-test create-prod-env
	-find dist -type l -exec rm {} \; &>/dev/null
	rm -rf dist
	mkdir -p dist/opt/libre-bib2
	rsync -aP LICENSE src/* dist/opt/libre-bib2/
	find dist -name .gitignore -exec rm {} \;
	find dist -type d -exec chmod a+rx {} \;
	find dist -type f -exec chmod a+r {} \;
	find dist -type f -executable -exec chmod a+rx {} \;

clean-test :
	-cd src/test/sample; \
	rm -rf backup etc status tmp; \
	rm bib-cache/db-create.cmd; \
	rm biblio-note.txt conf.env key.txt

# ========================================
install : build
	sudo mkdir -s /opt/libre-bib2
	sudo cp dist/* /opt/libre-bib2/
	find /opt/libre-bib2 -type d -exec chmod a+rx {} \;
	find /opt/libre-bib2 -type f -exec chmod a+r {} \;
	find /opt/libre-bib2 -type f -executable -exec chmod a+rx {} \;

# ========================================
# Package

package : clean build mk-pkg

mk-pkg : package/ver.sh package/epm.list
	-mkdir -p pkg
	cd package; . ./ver.env; epm -v -f native -m $$ProdOSDist-$$ProdArch --output-dir ../pkg $(ProdName) ver.epm
	cd package; . ./ver.env; epm -v -f portable -m $$ProdOSDist-$$ProdArch --output-dir ../pkg $(ProdName) ver.epm

package/epm.list : dist/opt/libre-bib2
	cd package; mkepmlist -u root -g root --prefix / ../dist | patch-epm-list -f ./epm.patch >epm.list

package/ver.mak : package/ver.sh
	cd package; \
	./ver.sh -e 'mak env epm'

package/ver.sh : src/VERSION
	touch $@

# ========================================
# Complex Targets

# --------------------
# EPM

mEpmMx=mx19/epm-5.0.2-1-mx19-x86_64.deb
mEpmUbuntu=ubuntu18/epm-5.0.1-2-linux-5.3-x86_64.deb

/usr/local/bin/epm :
	if [[ "$(ProdOSDist)" = "mx" ]]; then \
		cd tmp; wget $(ProdRelRoot)/released/software/ThirdParty/epm/$(mEpmMx); \
		sudo apt-get install -y tmp/$(notdir $(mEpmMx)); \
	fi
	if [[ "$(ProdOSDist)" = "ubuntu" ]]; then \
		cd tmp; wget $(ProdRelRoot)/released/software/ThirdParty/epm/$(mEpmUbuntu); \
		sudo apt-get install -y tmp/$(notdir $(mEpmUbuntu)); \
	fi

# --------------------
# EPM Helper

mEpmHelper=epm-helper-1.6.1-3-linux-noarch.deb

/usr/local/bin/mkver.pl :
	cd tmp; wget $(ProdRelRoot)/released/software/ThirdParty/epm/$(mEpmHelper)
	sudo apt-get install -y tmp/$(mEpmHelper)

# --------------------
# Beekeeper

mBeekeeperVer=3.9.17
mBeekeeper=Beekeeper-Studio-$(mBeekeeperVer).AppImage

/usr/local/bin/beekeeper : /usr/local/bin/$(mBeekeeper)
	cd /usr/local/bin; sudo ln -sf $(mBeekeeper) beekeeper

/usr/local/bin/$(mBeekeeper) :
	cd tmp; wget https://github.com/beekeeper-studio/beekeeper-studio/releases/download/v$(mBeekeeperVer)/$(mBeekeeper)
	sudo mv -f tmp/$(mBeekeeper) $@
	sudo chmod a+rx $@

# --------------------
# phpunit

mPhpUnit = phpunit-9.6.35.phar
    # Version  8.x needs php 7.2.0
    # Version  9.x needs php 7.3.0
    # Version 10.x needs php 8.1.0
    # Version 11.x needs php 8.2.0
    # Version 12.x needs php 8.3.0

src/bin/$(mPhpUnit) :
	rsync -P moria.whyayh.com:/rel/archive/software/ThirdParty/phpunit/*.phar src/bin/

src/bin/phpunit : src/bin/$(mPhpUnit)
	cd src/bin; \
	ln -sf $(mPhpUnit) phpunit

# ========================================
# Single Targets $(mUtilProg)

# --------------------
# Build Env only

bin/bash-fmt : $(mUtilProgDir)/bin/bash-fmt
	cp $? $@

bin/incver.sh : $(mUtilProgDir)/bin/incver.sh
	cp $? $@

bin/phptidy.php : $(mUtilProgDir)/bin/phptidy.php
	cp $? $@

bin/.phptidy-config.php : $(mUtilProgDir)/bin/.phptidy-config.php
	cp $? $@

bin/pre-commit : $(mUtilProgDir)/bin/pre-commit
	cp $? $@

.git/hooks/pre-commit : $(mUtilProgDir)/bin/pre-commit
	cp $? $@

bin/rm-trailing-sp : $(mUtilProgDir)/bin/rm-trailing-sp
	cp $? $@

bin/shfmt : $(mUtilProgDir)/bin/shfmt
	cp $? $@

# --------------------
# Build Env and Product

src/bin/bash-com.inc bin/bash-com.inc : $(mUtilProgDir)/bin/bash-com.inc 
	cp $? $@

src/bin/bash-com.test bin/bash-com.test : $(mUtilProgDir)/bin/bash-com.test
	cp $? $@

src/bin/org2html.sh bin/org2html.sh : $(mUtilProgDir)/bin/org2html.sh
	cp $? $@

src/bin/shunit2.1 bin/shunit2.1 : $(mUtilProgDir)/bin/shunit2.1
	cp $? $@

src/bin/sort-para.sh bin/sort-para.sh : $(mUtilProgDir)/bin/sort-para.sh
	cp $? $@

# ========================================
# Rules

%.md : %.html
	pandoc -f html -t markdown < $<  > $@

%.odt : %.html
	libreoffice --headless --convert-to odt $<

%.html : %.org
	bin/org2html.sh -s 2 $< $@
