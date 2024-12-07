# Product Makefile

# ========================================
SHELL = /bin/bash
cgDirApp = /opt/libre-bib
cgBin = $(cgDirApp)/bin
mRoot = dist/opt/libre-bib
mDirList = $(mRoot) dist/usr/local/bin
mCoreDir = ../src
#?? cgBuild=true

include package/ver.mak

export mAppMake = . src/etc/conf.env; cgDirApp=$(PWD)/src; cgBin=$(PWD)/src/bin; make -f src/bin/bib-cmd.mak

# ========================================
clean :
	-find . -type f -name '*~' -exec rm {} \; &>/dev/null
	-find . -type f -name '.phptidy-cache' -exec rm {} \; &>/dev/null
	-find . -type f -name '*.tmp' -exec rm {} \; &>/dev/null
	-find . -type f -name '*.bak' -exec rm {} \; &>/dev/null

dist-clean : clean
	. $(cgDirApp)/etc/conf.env; \
	    . tmp-test/conf.env; \
	    echo "drop database $$cgDbName;" >cmd.tmp
	-sudo mysql -u root <cmd.tmp
	-rm cmd.tmp
	-rm -rf tmp-test dist pkg tmp

# ========================================
# Cleanup and make dist/ area
build : build-setup package/ver.mak

# ========================================
release :
	bin/incver.sh -m src/VERSION
	git commit -am "Inc Ver"
	git push origin develop
	git checkout main
	git pull origin main
	git merge develop
	git tag -f -F src/VERSION "v$$(cat src/VERSION)"
	git push --tags origin main
	git checkout develop
	bin/incver.sh -p src/VERSION

# ========================================
# Make deb package
package : package/ver.epm

# ========================================
# Push packages to release repositories
pkg-release:

# ========================================
# Manual install - only for testing
install : $(cgDirApp) check mk-doc clean
	-find src -name '*~' -exec rm {} \; &>/dev/null
	-mkdir $(cgDirApp)/etc/old &>/dev/null
	-cp --backup=t $$(find $(cgDirApp)/etc/* -prune -type f) $(cgDirApp)/etc/old/
	rsync -aC src/* $(cgDirApp)/
	find $(cgDirApp) -type d -exec chmod a+rx {} \;
	find $(cgDirApp) -type f -exec chmod a+r {} \;
	find $(cgDirApp) -type f -executable -exec chmod a+rx {} \;
	bin/incver.sh -p src/VERSION
	@echo "Installed OK"

#sudo ln -fs /opt/libre-bib/bin/bib /usr/local/bin/bib

# ========================================
incver :
	bin/incver.sh -m src/VERSION

# ========================================
# So far these are just crude "happy-path" tests.
test : db-setup check # install
	echo -e "show databases;\n quit" | mysql -u example
	. $(cgDirApp)/etc/conf.env; \
	    . tmp-test/conf.env; \
	    echo "" >tmp-test/$$cgDbPassCache
	@echo -e "\n==========\nTest import-lo"
	cd tmp-test; bib import-lo
	test -f tmp-test/status/import-lo.date
	echo 'show tables;' | mysql -u example biblio_example | grep lo
	@echo -e "\n==========\nTest export-lo"
	cd tmp-test; bib export-lo
	test -f tmp-test/tmp/biblio.txt
	if diff tmp-test/biblio.txt tmp-test/tmp/biblio.txt | grep 'Id: '; then exit 1; fi
	@echo -e "\n==========\nTest backup-lo"
	cd tmp-test; bib backup-lo
	test -f tmp-test/status/backup-lo.date
	test -f tmp-test/backup/backup-lo.csv
	@echo -e "\n==========\nTest import-lib"
	cd tmp-test; bib import-lib
	test -f tmp-test/status/import-lib.date
	echo 'show tables;' | mysql -u example biblio_example | grep lib
	@echo -e "\n==========\nTest ref-new"
	cd tmp-test; bib ref-new
	test -f tmp-test/status/ref-new.date
	test -f tmp-test/backup/example.odt
	if diff -q tmp-test/backup/example.odt tmp-test/example.odt; then exit 1; fi
	@echo "It is not clear why there is always a diff here. Timestamp?"
	cmp tmp-test/example.odt test-ref/example.odt | grep 'byte 16166'
	@echo -e "\n==========\nTest ref-update"
	cd tmp-test; bib ref-update
	test -f tmp-test/status/ref-update.date
	test -f tmp-test/backup/example.odt
	cmp tmp-test/example.odt test-ref/example.odt | grep 'byte 16166'
	@echo -e "\n==========\nTest status-bib"
	cd tmp-test; bib status-bib
	@echo -e "\n==========\nTest reset example.odt"
	@echo "Reset, so test can be repeated"
	mv -f tmp-test/example.odt tmp-test/tmp
	cp -f src/doc/example/example.odt tmp-test/
	@echo -e "\n==========\nPassed"

# --------------------
db-setup : tmp-test/conf.env tmp-test/status-pkg.txt tmp-test/status-db.txt
	-rm tmp-test/status/*

tmp-test/conf.env :
	-rm -rf tmp-test
	mkdir tmp-test
	-cd tmp-test; bib setup-bib
	-cd tmp-test; bib setup-bib
	echo 'cgDbUser="example"' >>$@
	echo 'cgUseRemote=false' >>$@
	echo 'cgUseLib=true' >>$@
	echo 'cgVerbose=true' >>$@
#	exit 1

tmp-test/status-pkg.txt :
	sudo apt-get update
	-sudo apt-get -y install $(mPackgeList)
	date >$@

tmp-test/status-db.txt :
	-echo 'show databases' | mysql -u example | grep biblio_example; \
	if [ $$? -ne 0 ]; then \
		. $(cgDirApp)/etc/conf.env; \
		. tmp-test/conf.env; \
		echo "create database $$cgDbName;" >cmd.tmp; \
		echo "create user '$$cgDbUser'@'localhost';" >>cmd.tmp; \
		echo "grant all privileges on $$cgDbName.* to '$$cgDbUser'@localhost;" >>cmd.tmp; \
		echo "flush privileges;" >>cmd.tmp; \
		echo "show databases;" >>cmd.tmp; \
		echo "show grants for '$$cgDbUser'@localhost;" >>cmd.tmp; \
		sudo mysql -u root <cmd.tmp; \
	fi
	date >$@

# -D $(cgDbName)

# remove later:
#  galera-4 libdbi-perl mariadb-server mariadb-server-10.5
#  mariadb-server-core-10.5

# ----------------------------------------
mk-app-dir $(cgDirApp) :
	sudo mkdir -p $(cgDirApp)
	sudo chown -R $$SUDO_USER:$$SUDO_USER $(cgDirApp)
	sudo find $(cgDirApp) -type d -exec chmod a+rx {} \;
	sudo find $(cgDirApp) -type f -exec chmod a+r {} \;

# Use the rules
mk-doc : \
		src/doc/manual/libre-bib.html \
		src/doc/manual/libre-bib.md \
		src/doc/example/example-outline.html
	-$(mAppMake) rebuild

# ----------------------------------------
package/ver.sh :  src/VERSION
	sed -i "s/ProdVer=.*/ProdVer=\"$$(cat src/VERSION)\"/" $@

package/ver.mak package/ver.env package/ver.epm : package/ver.sh
	cd package; mkver.pl -e 'epm env mak'

# ----------------------------------------
mEpmMx=mx19/epm-5.0.2-1-mx19-x86_64.deb
mEpmUbuntu=ubuntu18/epm-5.0.1-2-linux-5.3-x86_64.deb
mEpmHelper=epm-helper-1.6.1-3-linux-noarch.deb

build-packages : tmp product-packages \
		/usr/local/bin/epm \
		/usr/local/bin/mkver.pl \
		/usr/local/bin/beekeeper \
		/usr/bin/pod2pdf \
		/usr/bin/pod2markdown
	chown -R $$SUDO_USER:$$SUDO_USER tmp

/usr/local/bin/epm :
	if [[ "$$USER" != "root" ]]; then exit 1; fi
	if [[ "$(ProdOSDist)" = "mx" ]]; then \
		cd tmp; wget $(ProdRelRoot)/released/software/ThirdParty/epm/$(mEpmMx); \
		apt-get install tmp/$(notdir $(mEpmMx)); \
	fi
	if [[ "$(ProdOSDist)" = "ubuntu" ]]; then \
		cd tmp; wget $(ProdRelRoot)/released/software/ThirdParty/epm/$(mEpmUbuntu); \
		apt-get install tmp/$(notdir $(mEpmUbuntu)); \
	fi

/usr/local/bin/mkver.pl :
	if [[ "$$USER" != "root" ]]; then exit 1; fi
	cd tmp; wget $(ProdRelRoot)/released/software/ThirdParty/epm/$(mEpmHelper)
	apt-get install tmp/$(mEpmHelper)

/usr/bin/pod2pdf :
	if [[ "$$USER" != "root" ]]; then exit 1; fi
	apt-get install pod2pdf

/usr/bin/pod2markdown :
	if [[ "$$USER" != "root" ]]; then exit 1; fi
	apt-get install libpod-markdown-perl pod2pdf

product-packages : package/mx.require package/ubuntu.require
	if [[ "$$USER" != "root" ]]; then exit 1; fi
	if [[ "$(ProdOSDist)" = "mx" ]]; then \
		apt-get install $$(awk '/%requires/ {print $$2}' package/mx.require); \
	fi
	if [[ "$(ProdOSDist)" = "ubuntu" ]]; then \
		apt-get install $$(awk '/%requires/ {print $$2}' package/ubuntu.require); \
	fi

# ----------------------------------------
mBeekeeperVer=3.9.17
mBeekeeper=Beekeeper-Studio-$(mBeekeeperVer).AppImage

/usr/local/bin/beekeeper : /usr/local/bin/$(mBeekeeper)
	if [[ "$$USER" != "root" ]]; then exit 1; fi
	cd /usr/local/bin; ln -sf $(mBeekeeper) beekeeper

/usr/local/bin/$(mBeekeeper) :
	if [[ "$$USER" != "root" ]]; then exit 1; fi
	cd tmp; wget https://github.com/beekeeper-studio/beekeeper-studio/releases/download/v$(mBeekeeperVer)/$(mBeekeeper)
	mv -f tmp/$(mBeekeeper) $@
	chmod a+rx $@

# ----------------------------------------
build-setup : update-my-util update-shfmt update-pre-commit update-php-util
	make check

#src/bin/sort-para.sh \
#bin/incver.sh \
#bin/rm-trailing-sp \
#bin/shunit2.1 \
#bin/shfmt \
#bin/phptidy.php \
#.git/hooks/pre-commit

# ----------------------------------------
check :
	bin/check.sh
	bin/unit-test-shell.sh

# ----------------------------------------
# my-utility-scripts - multiple scripts
mMyUtil=tag-1-16-0
mMyUtilList = \
	bin/incver.sh \
	bin/org2html.sh \
	bin/rm-trailing-sp \
	bin/shunit2.1 \
	bin/sort-para.sh \
	src/bin/sort-para.sh

update-my-util : tmp/my-utility-scripts-$(mMyUtil) $(mMyUtilList)

tmp/my-utility-scripts-$(mMyUtil) : tmp/$(mMyUtil).zip
	cd tmp; unzip -o $(mMyUtil).zip

tmp/$(mMyUtil).zip :
	cd tmp; wget https://github.com/TurtleEngr/my-utility-scripts/archive/refs/tags/$(mMyUtil).zip

bin/sort-para.sh : tmp/my-utility-scripts-$(mMyUtil)
	cp tmp/my-utility-scripts-$(mMyUtil)/bin/$(notdir $@) $@

bin/incver.sh : tmp/my-utility-scripts-$(mMyUtil)
	cp tmp/my-utility-scripts-$(mMyUtil)/bin/$(notdir $@) $@

bin/rm-trailing-sp : tmp/my-utility-scripts-$(mMyUtil)
	cp tmp/my-utility-scripts-$(mMyUtil)/bin/$(notdir $@) $@

bin/shunit2.1 : tmp/my-utility-scripts-$(mMyUtil)
	cp tmp/my-utility-scripts-$(mMyUtil)/bin/$(notdir $@) $@

src/bin/sort-para.sh : bin/sort-para.sh
	cp $? $@

# ----------------------------------------
# shfmt
mShFmt=v3.1.2

update-shfmt : bin/shfmt

bin/shfmt : tmp/shfmt_$(mShFmt)_linux_amd64
	cp $? $@
	chmod a+rx $@

tmp/shfmt_$(mShFmt)_linux_amd64 :
	cd tmp; wget https://github.com/mvdan/sh/releases/download/$(mShFmt)/shfmt_$(mShFmt)_linux_amd64

# ----------------------------------------
# phptidy.php phpunit.phar
mPhpTidy=3.3
mPhpUnit = phpunit-9.6.13.phar

update-php-util : bin/phptidy.php bin/phpunit.phar

bin/phptidy.php : tmp/phptidy
	cp $?/phptidy.php $@
	chmod a+rx $@

tmp/phptidy : tmp/phptidy-$(mPhpTidy).tar.gz

tmp/phptidy-$(mPhpTidy).tar.gz :
	cd tmp; wget https://github.com/cmrcx/phptidy/releases/download/v$(mPhpTidy)/phptidy-$(mPhpTidy).tar.gz
	cd tmp; tar -xzf phptidy-$(mPhpTidy).tar.gz

bin/phpunit.phar : tmp/$(mPhpUnit)
	cp tmp/$(mPhpUnit) bin
	chmod a+rx bin/$(mPhpUnit)
	cd bin; ln -s $(mPhpUnit) phpunit.phar

tmp/$(mPhpUnit) :
	cd tmp; wget https://phar.phpunit.de/$(mPhpUnit)

# ----------------------------------------
# pre-commit hook
# The detault gitproj.hook.tab-include-list is '*"
#     Only text files are looked at.
# gitproj.hook.tab-exclude-list is a "grep -E" pattern

mGitProj=tag-0-7-6-1

update-pre-commit : .git/hooks/pre-commit

.git/hooks/pre-commit : bin/pre-commit
	cp $? $@
	git config --bool gitproj.hook.pre-commit-enabled true
	git config --bool gitproj.hook.check-file-names true
	git config --bool gitproj.hook.check-whitespace true
	git config --bool gitproj.hook.check-for-tabs true
	git config gitproj.hook.tab-include-list
	git config gitproj.hook.tab-exclude-list 'Makefile|*.mak'
	git config --bool gitproj.hook.check-in-raw false
	git config --bool gitproj.hook.check-for-big-files true
	git config --int gitproj.hook.binary-file-size 30000
	git config --bool gitproj.hook.verbose true

bin/pre-commit : tmp/gitproj-$(mGitProj)/doc/hooks/pre-commit
	cp $? $@

tmp/gitproj-$(mGitProj)/doc/hooks/pre-commit : tmp/$(mGitProj).zip
	cd tmp; unzip -o $(mGitProj).zip gitproj-$(mGitProj)/doc/hooks/pre-commit
	touch $@

tmp/$(mGitProj).zip :
	cd tmp; wget https://github.com/TurtleEngr/gitproj/archive/refs/tags/$(mGitProj).zip

# ----------------------------------------
# Note: these rules are also in src/bin/bib-cmd.mak

%.md : %.html
	pandoc -f html -t markdown < $<  > $@

%.odt : %.html
	libreoffice --headless --convert-to odt $<

%.html : %.org
	sed 's/^ *- /\n\n/g' $< | \
	pandoc -f org -t html > $@
	sed -i -f $(cgBin)/fixup.sed $@
	-$(mTidyXhtml) $@

# ----------------------------------------
tmp :
	-mkdir tmp

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

$(mRoot)/bin/phptidy.php : bin/phptidy.php
	'rsync' -a $? $@

$(mRoot)/bin : bin/sort-para.sh
	'rsync' -a $? $@

