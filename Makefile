# Product Makefile

export SHELL = /bin/bash
export cgDirApp = /opt/libre-bib
export cgBin = $(cgDirApp)/bin

mMake = . src/etc/conf.env; cgDirApp=$(PWD)/src; cgBin=$(PWD)/src/bin; make -f src/bin/bib-cmd.mak

mPackgeList = \
	libreoffice \
	libreoffice-sdbc-mysql \
	mariadb-client \
	mariadb-server \
	php \
	php-mysqlnd \
	perl \
	bash \
	sed \
	tidy \
	make \
	pandoc \
	libpod-markdown-perl

# --------------------
clean :
	-find . -type f -name '*~' -exec rm {} \; &>/dev/null
	-find . -type f -name '.phptidy-cache' -exec rm {} \; &>/dev/null
	-find . -type f -name '*.tmp' -exec rm {} \; &>/dev/null
	-find . -type f -name '*.bak' -exec rm {} \; &>/dev/null

dist-clean : clean
	. test-dir/conf.env; echo "drop database $$cgDbName;" >cmd.tmp
	-sudo mysql -u root <cmd.tmp
	-rm cmd.tmp
	-rm -rf test-dir dist pkg

# --------------------
build-setup : .git/hooks/pre-commit

# --------------------
# Cleanup and make dist/ area
build : build-setup check

# --------------------
# Make deb package
package :

# --------------------
# Manual install - only for testing
install : $(cgDirApp) check mk-doc clean
	-find src -name '*~' -exec rm {} \; &>/dev/null
	-mkdir $(cgDirApp)/etc/old &>/dev/null
	cp --backup=t $$(find $(cgDirApp)/etc/* -prune -type f) $(cgDirApp)/etc/old/
	rsync -aC src/* $(cgDirApp)/
	find $(cgDirApp) -type d -exec chmod a+rx {} \;
	find $(cgDirApp) -type f -exec chmod a+r {} \;
	find $(cgDirApp) -type f -executable -exec chmod a+rx {} \;
	build/bin/incver.sh -p src/VERSION
	@echo "Installed OK"

#sudo ln -fs /opt/libre-bib/bin/bib /usr/local/bin/bib

incver :
	build/bin/incver.sh -m src/VERSION

# --------------------
release :
	build/bin/incver.sh -m src/VERSION
	git commit -am "Inc Ver"
	git push origin develop
	git checkout main
	git merge develop
	git push origin main
	git checkout develop
	build/bin/incver.sh -p src/VERSION

# --------------------
# So far these are just crude "happy-path" tests.
test : db-setup check  # install
	echo -e "show databases;\n quit" | mysql -u example
	. test-dir/conf.env; echo "" >test-dir/$$cgDbPassCache
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
	test -f test-dir/status/bib-update.date
	test -f test-dir/backup/example.odt
	if diff -q test-dir/backup/example.odt test-dir/example.odt; then exit 1; fi
	@echo "It is not clear why there is always a diff here. Timestamp?"
	cmp test-dir/example.odt test-ref/example.odt | grep 'byte 16166, line 60'
	@echo -e "\n==========\nTest ref-update"
	cd test-dir; bib ref-update
	test -f test-dir/status/bib-update.date
	test -f test-dir/backup/example.odt
	cmp test-dir/example.odt test-ref/example.odt | grep 'byte 16166, line 60'
	@echo -e "\n==========\nTest status-bib"
	cd test-dir; bib status-bib
	@echo -e "\n==========\nTest reset example.odt"
	@echo "Reset, so test can be repeated"
	mv -f test-dir/example.odt test-dir/tmp
	cp -f src/doc/example/example.odt test-dir/
	@echo -e "\n==========\nPassed"

# --------------------
db-setup : test-dir/conf.env test-dir/status-pkg.txt test-dir/status-db.txt

test-dir/conf.env :
	-rm -rf test-dir
	mkdir test-dir
	-cd test-dir; bib setup-bib
	-cd test-dir; bib setup-bib
	cd test-dir; sed -i 's/^#export /export /g' conf.env
	echo 'cgDbUser="example"' >>$@
	echo 'cgUseRemote=false' >>$@
	echo 'cgUseLib=true' >>$@
	echo 'cgVerbose=true' >>$@

test-dir/status-pkg.txt :
	sudo apt-get update
	-sudo apt-get -y install $(mPackgeList)
	date >$@

test-dir/status-db.txt :
	-echo 'show databases' | mysql -u example | grep biblio_example; \
	if [ $$? -ne 0 ]; then \
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
	sudo chown -R $SUDO_USER:$SUDO_USER $(cgDirApp)
	sudo find $(cgDirApp) -type d -exec chmod a+rx {} \;
	sudo find $(cgDirApp) -type f -exec chmod a+r {} \;

# Use the rules
mk-doc :
	-$(mMake) src/doc/manual/libre-bib.html
	-$(mMake) src/doc/manual/libre-bib.md
	-$(mMake) src/doc/example/example-outline.html

rebuild :
	-$(mMake) rebuild


# The detault gitproj.hook.tab-include-list is '*"
#     Only text files are looked at.
# gitproj.hook.tab-exclude-list is a "grep -E" pattern
.git/hooks/pre-commit : build/etc/pre-commit
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

build/etc/pre-commit :
	cp -a ~/ver/public/app/gitproj/gitproj/doc/hooks/pre-commit $@
	# See gitproj repo

check :
	build/bin/check.sh
