
# --------------------
# Dependencies

# Required Packages
  # libreoffice
  # libreoffice-sdbc-mysql (needed for DB connection)
  # mariadb-client - mysql
  # mariadb-server - mariadbd (only on remote host)
  # php
  # php-mysqlnd - php-PDO
  # perl - pod2html, pod2man, pod2text, pod2usage
  # bash
  # tidy
  # make (script and file manager)

# Optional Packages
  # epm-helper - mkver.pl (generate conf.* files)
  # sed
  # pandoc
  # libpod-markdown-perl - pod2markdown
  # pod2pdf
  # beekeeper - https://github.com/beekeeper-studio/beekeeper-studio

# --------------------
# Macros

SHELL = /bin/bash

mDataPath = ../doc/example

include config/conf.mak
# If on REMOTE-HOST use gPortLocal, otherwise use gPortRemote

mConfigDB = ~/.config/libreoffice/4/user/database

mTidyXhtml = tidy -m -q -i -w 78 -asxhtml --break-before-br yes --indent-attributes yes --indent-spaces 2 --tidy-mark no --vertical-space no

mTidyWide = tidy -q -i -w 4000 -asxhtml --break-before-br no --indent-attributes no --indent-spaces 2 --tidy-mark no --vertical-space no

mTidyXml = tidy -q -i -w 78 -xml --break-before-br yes --indent-attributes yes --indent-spaces 2 --tidy-mark no --vertical-space no

# ----------

build : config/conf.php config/conf.mak

add edit :
	emacs $(mDataPath)/biblio.txt &
	@echo "When done run: make import-lo"

clean :
	-cd ..; find * -name '*~' -exec rm {} \;
	-rm .pass.tmp parse-tmp.awk gen/*

commit-all :
	cd ..; cvs ci -m Updated
	git ci -am Updated
	git push origin develop

release :
	git co main; git merge develop
	git push origin main
	git co develop

.pass.tmp :
	read -srp 'Password ($(gPassHint))? '; \
	echo $$REPLY >.pass.tmp

connect : .pass.tmp
	@echo First define tunnel: ssh HOST.example.com
	@echo See: ~/ssh/config
	@echo "show databases; use DBNAME; show tables;"
	@echo
	mysql -P $(gPortRemote) -u $(gUserName) --password=$$(cat .pass.tmp) -h $(gHost) $(gDBName)

connect-admin :
	mysql -P $(gPortRemote) -u admin -p -h $(gHost)

# ----------
config/conf.php config/conf.env config/conf.mak : config/mkconf.sh
	cd config; mkver.pl -d mkconf.sh -e 'php env mak' -u 'gDBName gDsn gHost gPassHint gPassCache gPort gPortLocal gPortRemote gUserName'

# ========================================
# Normal update of src/biblio.txt and the lo db

# Edit src/biblio.txt
  # Run: make import-lo
  # cvs ci -m Updated
  # This is a full copy from biblio.txt, not a merge

# If lo db is modified,
  # Run: make export-lo
  # Diffs can be merged into biblio.txt to create an updated lo db
  # If OK, cp gen/biblio-new.txt to src/biblio.txt
  # cvs ci -m Updated

# Backup
  # Run: make backup-lo
  # cvs ci -m Updated

# ----------
# Import: biblio.txt

import-lo : src/import-txt-2-lo.date

src/import-txt-2-lo.date : config/conf.php bin/util.php $(mDataPath)/biblio.txt
	bin/import-txt-2-lo.php -c config/conf.php -t lo -b -i $(mDataPath)/biblio.txt
	bin/convert-lo-2-bib.php -c config/conf.php -f lo -t bib -b
	date +%F_%T >$@

# ----------
# export: biblio-new.txt

export-lo : config/conf.php bin/util.php
	bin/export-lo-2-txt.php -c config/conf.php -t lo -o gen/biblio-new.txt

# ----------
# backup: lo db

backup-lo : config/conf.php bin/util.php src/ref-biblio-col.csv
	-cp --backup=t src/backup-lo.csv src/backup-lo.csv.sav
	bin/export-lo-2-tcsv.php -c config/conf.php -s c -t lo -o src/backup-lo.csv
	date +%F_%T >src/backup-lo.date

# src/backup-lo.csv : src/import-txt-2-lo.date

restore-lo : config/conf.php bin/util.php
	bin/import-tcsv-2-lo-db.php -c config/conf.php -s c -c lo -b -i src/backup-lo.csv

# ----------
# If lib-db or lo-db is changed, then run this

update-lo : config/conf.php
	@echo "Update lo from lib where Titles are similar, first 40 char"
	@echo "Run this after lib-db, lo-db"
	bin/update-lib-2-lo.php -c config/conf.php

# ----------
src/librarything_export-col.tsv : src/librarything_export.tsv
	head -n 1 <$? >$@

src/librarything_export.tsv :
	@echo Manually export $@ from Library Thing

# --------------------
# Update lib-db

update-lib-db : src/import-to-lo.date

src/import-to-lib.date : config/conf.php src/librarything_export.tsv
	@echo librarything schema and import
	bin/import-tsv-2-lib-db.php -c config/conf.php -s t -t lib -b -i src/librarything_export.tsv
	date +%F_%T >$@

# ----------
# Rules

%.odt : %.html
	libreoffice --headless --convert-to odt $<

%.html : %.org
	sed 's/^ *- /\n\n/g' $< | \
	pandoc -f org -t html >$@
	sed -i -f fixup.sed $@
	-$(mTidyXhtml) $@

# ========================================
# Tests

test-reset :
	-rm ../test/example*
	-rm ../test/*~

test-add test-new : ../test/example.odt
	bin/bib-ref-new.php -f ../test/example.odt
	mv ../test/example.odt ../test/example-add.odt
	@echo "Verify: libreoffice ../test/example-add.odt"

test-update : ../test/example-add.odt
	cp $? ../test/example.odt
	bin/bib-ref-update.php -f ../test/example.odt
	mv ../test/example.odt ../test/example-update.odt
	@echo "Verify: libreoffice ../test/example.odt"

test-dup-add : ../test/example-add.odt
	cp $? ../test/example.odt
	bin/bib-ref-new.php -f ../test/example.odt -d
	@echo "Should be 0 references"

test-dup-update : ../test/example-update.odt
	cp $? ../test/example.odt
	bin/bib-ref-update.php -f ../test/example-update.odt
	@echo "Should be lots of references"

../test/example.odt : ../doc/example/example.odt
	cp $? $@

# Optional
#../doc/example/example.odt : ../doc/example/example.org
