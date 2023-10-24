# Product Makefile

# ========================================
export SHELL = /bin/bash
export cgDirApp = /opt/libre-bib
export cgBin = $(cgDirApp)/bin
#?? export cgBuild=true

include build/ver.mak

mAppMake = . src/etc/conf.env; cgDirApp=$(PWD)/src; cgBin=$(PWD)/src/bin; make -f src/bin/bib-cmd.mak

# ========================================
clean :
	-find . -type f -name '*~' -exec rm {} \; &>/dev/null
	-find . -type f -name '.phptidy-cache' -exec rm {} \; &>/dev/null
	-find . -type f -name '*.tmp' -exec rm {} \; &>/dev/null
	-find . -type f -name '*.bak' -exec rm {} \; &>/dev/null

dist-clean : clean
	. $(cgDirApp)/etc/conf.env; \
	    . test-dir/conf.env; \
	    echo "drop database $$cgDbName;" >cmd.tmp
	-sudo mysql -u root <cmd.tmp
	-rm cmd.tmp
	-rm -rf test-dir dist pkg tmp

# ========================================
# Cleanup and make dist/ area
build : build-setup build/ver.mak

# ========================================
release :
	build/bin/incver.sh -m src/VERSION
	git commit -am "Inc Ver"
	git push origin develop
	git checkout main
	git pull origin main
	git merge develop
	git tag -f -F src/VERSION "v$$(cat src/VERSION)"
	git push --tags origin main
	git checkout develop
	build/bin/incver.sh -p src/VERSION

# ========================================
# Make deb package
package : build/ver.epm

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
	build/bin/incver.sh -p src/VERSION
	@echo "Installed OK"

#sudo ln -fs /opt/libre-bib/bin/bib /usr/local/bin/bib

# ========================================
incver :
	build/bin/incver.sh -m src/VERSION

# ========================================
# So far these are just crude "happy-path" tests.
test : db-setup check # install
	echo -e "show databases;\n quit" | mysql -u example
	. $(cgDirApp)/etc/conf.env; \
	    . test-dir/conf.env; \
	    echo "" >test-dir/$$cgDbPassCache
	@echo -e "\n==========\nTest import-lo"
	cd test-dir; bib import-lo
	test -f test-dir/status/import-lo.date
	echo 'show tables;' | mysql -u example biblio_example | grep lo
	@echo -e "\n==========\nTest export-lo"
	cd test-dir; bib export-lo
	test -f test-dir/tmp/biblio.txt
	if diff test-dir/biblio.txt test-dir/tmp/biblio.txt | grep 'Id: '; then exit 1; fi
	@echo -e "\n==========\nTest backup-lo"
	cd test-dir; bib backup-lo
	test -f test-dir/status/backup-lo.date
	test -f test-dir/backup/backup-lo.csv
	@echo -e "\n==========\nTest import-lib"
	cd test-dir; bib import-lib
	test -f test-dir/status/import-lib.date
	echo 'show tables;' | mysql -u example biblio_example | grep lib
	@echo -e "\n==========\nTest ref-new"
	cd test-dir; bib ref-new
	test -f test-dir/status/ref-new.date
	test -f test-dir/backup/example.odt
	if diff -q test-dir/backup/example.odt test-dir/example.odt; then exit 1; fi
	@echo "It is not clear why there is always a diff here. Timestamp?"
	cmp test-dir/example.odt test-ref/example.odt | grep 'byte 16166'
	@echo -e "\n==========\nTest ref-update"
	cd test-dir; bib ref-update
	test -f test-dir/status/ref-update.date
	test -f test-dir/backup/example.odt
	cmp test-dir/example.odt test-ref/example.odt | grep 'byte 16166'
	@echo -e "\n==========\nTest status-bib"
	cd test-dir; bib status-bib
	@echo -e "\n==========\nTest reset example.odt"
	@echo "Reset, so test can be repeated"
	mv -f test-dir/example.odt test-dir/tmp
	cp -f src/doc/example/example.odt test-dir/
	@echo -e "\n==========\nPassed"

# --------------------
db-setup : test-dir/conf.env test-dir/status-pkg.txt test-dir/status-db.txt
	-rm test-dir/status/*

test-dir/conf.env :
	-rm -rf test-dir
	mkdir test-dir
	-cd test-dir; bib setup-bib
	-cd test-dir; bib setup-bib
	echo 'cgDbUser="example"' >>$@
	echo 'cgUseRemote=false' >>$@
	echo 'cgUseLib=true' >>$@
	echo 'cgVerbose=true' >>$@
	exit 1

test-dir/status-pkg.txt :
	sudo apt-get update
	-sudo apt-get -y install $(mPackgeList)
	date >$@

test-dir/status-db.txt :
	-echo 'show databases' | mysql -u example | grep biblio_example; \
	if [ $$? -ne 0 ]; then \
		. $(cgDirApp)/etc/conf.env; \
		. test-dir/conf.env; \
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
build/ver.sh :  src/VERSION
	sed -i "s/ProdVer=.*/ProdVer=\"$$(cat src/VERSION)\"/" $@

build/ver.mak build/ver.env build/ver.epm : build/ver.sh
	cd build; mkver.pl -e 'epm env mak'

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

product-packages : build/mx.require build/ubuntu.require
	if [[ "$$USER" != "root" ]]; then exit 1; fi
	if [[ "$(ProdOSDist)" = "mx" ]]; then \
		apt-get install $$(awk '/%requires/ {print $$2}' build/mx.require); \
	fi
	if [[ "$(ProdOSDist)" = "ubuntu" ]]; then \
		apt-get install $$(awk '/%requires/ {print $$2}' build/ubuntu.require); \
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
build-setup : \
		src/bin/sort-para.sh \
		build/bin/incver.sh \
		build/bin/rm-trailing-sp \
		build/bin/shunit2.1 \
		build/bin/shfmt \
		build/bin/phptidy.php \
		.git/hooks/pre-commit
	check

# ----------------------------------------
check :
	build/bin/check.sh
	build/bin/unit-test-shell.sh

# ----------------------------------------
# my-utility-scripts - multiple scripts
mMyUtil=tag-0-3-0

tmp/my-utility-scripts-$(mMyUtil) : tmp/$(mMyUtil).zip

tmp/$(mMyUtil).zip :
	cd tmp; wget https://github.com/TurtleEngr/my-utility-scripts/archive/refs/tags/$(mMyUtil).zip
	cd tmp; unzip -o $(mMyUtil).zip

src/bin/sort-para.sh : build/bin/sort-para.sh
	cp $? $@

build/bin/sort-para.sh : tmp/my-utility-scripts-$(mMyUtil)
	cp tmp/my-utility-scripts-$(mMyUtil)/bin/$(notdir $@) $@

build/bin/incver.sh : tmp/my-utility-scripts-$(mMyUtil)
	cp tmp/my-utility-scripts-$(mMyUtil)/bin/$(notdir $@) $@

build/bin/rm-trailing-sp : tmp/my-utility-scripts-$(mMyUtil)
	cp tmp/my-utility-scripts-$(mMyUtil)/bin/$(notdir $@) $@

build/bin/shunit2.1 : tmp/my-utility-scripts-$(mMyUtil)
	cp tmp/my-utility-scripts-$(mMyUtil)/bin/$(notdir $@) $@

# ----------------------------------------
# shfmt
mShFmt=v3.1.2

build/bin/shfmt : tmp/shfmt_$(mShFmt)_linux_amd64
	cp $? $@
	chmod a+rx $@

tmp/shfmt_$(mShFmt)_linux_amd64 :
	cd tmp; wget https://github.com/mvdan/sh/releases/download/$(mShFmt)/shfmt_$(mShFmt)_linux_amd64

# ----------------------------------------
# phptidy.php
mPhpTidy=3.3

build/bin/phptidy.php : tmp/phptidy
	cp $?/phptidy.php $@
	chmod a+rx $@

tmp/phptidy : tmp/phptidy-$(mPhpTidy).tar.gz

tmp/phptidy-$(mPhpTidy).tar.gz :
	cd tmp; wget https://github.com/cmrcx/phptidy/releases/download/v$(mPhpTidy)/phptidy-$(mPhpTidy).tar.gz
	cd tmp; tar -xzf phptidy-$(mPhpTidy).tar.gz

# ----------------------------------------
# pre-commit
mGitProj=tag-0-7-6-1

build/bin/pre-commit : tmp/gitproj-$(mGitProj)/doc/hooks/pre-commit
	cp $? $@

tmp/gitproj-$(mGitProj)/doc/hooks/pre-commit : tmp/$(mGitProj).zip
	cd tmp; unzip -o $(mGitProj).zip gitproj-$(mGitProj)/doc/hooks/pre-commit
	touch $@

tmp/$(mGitProj).zip :
	cd tmp; wget https://github.com/TurtleEngr/gitproj/archive/refs/tags/$(mGitProj).zip

# ----------------------------------------
# pre-commit hook
# The detault gitproj.hook.tab-include-list is '*"
#     Only text files are looked at.
# gitproj.hook.tab-exclude-list is a "grep -E" pattern

.git/hooks/pre-commit : build/bin/pre-commit
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
