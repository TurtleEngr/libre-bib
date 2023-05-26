# Product Makefile

export SHELL = /bin/bash
export cgDirApp = /opt/libre-bib
export cgBin = $(cgDirApp)/bin

mMake = . src/etc/conf.env; make -f src/bin/Makefile

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
clean:
	-find . -type f -name '*~' -exec rm {} \; &>/dev/null
	-find . -type f -name '.phptidy-cache' -exec rm {} \; &>/dev/null
	-find . -type f -name '*.tmp' -exec rm {} \; &>/dev/null
	-find . -type f -name '*.bak' -exec rm {} \; &>/dev/null
	-rm -rf test-dir

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
install : $(cgDirApp) check
	-find src -name '*~' -exec rm {} \; &>/dev/null
	-mkdir $(cgDirApp)/etc/old &>/dev/null
	cp --backup=t $$(find $(cgDirApp)/etc/* -prune -type f) $(cgDirApp)/etc/old/
	rsync -aC src/* $(cgDirApp)/
	find $(cgDirApp) -type d -exec chmod a+rx {} \;
	find $(cgDirApp) -type f -exec chmod a+r {} \;
	find $(cgDirApp) -type f -executable -exec chmod a+rx {} \;

#	build/bin/incver.sh -p src/VERSION

#sudo ln -fs /opt/libre-bib/bin/bib /usr/local/bin/bib


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
test : db-setup
	echo -e "show databases;\n quit" | mysql -u example
	. test-dir/conf.env; echo "" >test-dir/$$cgDbPassCache
	cd test-dir; bib import-lo
	test -f test-dir/status/import-lo.date
	echo 'show tables;' | mysql -u example biblio_example | grep lo
	cd test-dir; bib export-lo
	test -f test-dir/tmp/biblio.txt
	if diff test-dir/biblio.txt test-dir/tmp/biblio.txt | grep 'Id: '; then exit 1; fi
	cd test-dir; bib backup-lo
	test -f test-dir/status/backup-lo.date
	test -f test-dir/backup/backup-lo.csv
	cd test-dir; bib import-lib
	test -f test-dir/status/import-lib.date
	echo 'show tables;' | mysql -u example biblio_example | grep lib
	cd test-dir; bib ref-new
	test -f test-dir/status/bib-update.date
	test -f test-dir/backup/example.odt
	if diff -q test-dir/backup/example.odt test-dir/example.odt; then exit 1; fi
	@echo "It is not clear why there is always a diff here."
	cmp test-dir/example.odt test-ref/example.odt | grep 'byte 16166, line 60'
	@echo "Reset, so test can be repeated"
	mv -f test-dir/example.odt test-dir/tmp
	mv test-dir/backup/example.odt test-dir/
	cd test-dir; bib status-bib

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
	echo 'show databases' | mysql -u example | grep biblio_example; \
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

mk-doc :
	-$(mMake) src/doc/manual/libre-bib.md
	-$(mMake) src/doc/example/example-outline.html

.git/hooks/pre-commit : build/etc/pre-commit
	cp $? $@
	git config --bool gitproj.hook.pre-commit-enabled true
	git config --bool gitproj.hook.check-file-names true
	git config --bool gitproj.hook.check-whitespace true
	git config --bool gitproj.hook.check-for-tabs true
	git config gitproj.hook.tab-include-list
	git config gitproj.hook.tab-exclude-list Makefile
	git config --bool gitproj.hook.check-in-raw false
	git config --bool gitproj.hook.check-for-big-files true
	git config --int gitproj.hook.binary-file-size 20000
	git config --bool gitproj.hook.verbose true

build/etc/pre-commit :
	cp -a ~/ver/public/app/gitproj/gitproj/doc/hooks/pre-commit $@
	# See gitproj repo

check :
	build/bin/check.sh
