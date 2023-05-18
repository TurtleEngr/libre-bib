
# -c config/conf.php can be ignored. Just include:
# $_ENV["cgDirApp"]/etc/conf.php

# --------------------
# Macros

SHELL = /bin/bash

mConfigDB = ~/.config/libreoffice/4/user/database

mTidyXhtml = tidy -m -q -i -w 78 -asxhtml --break-before-br yes --indent-attributes yes --indent-spaces 2 --tidy-mark no --vertical-space no

mTidyWide = tidy -q -i -w 4000 -asxhtml --break-before-br no --indent-attributes no --indent-spaces 2 --tidy-mark no --vertical-space no

mTidyXml = tidy -q -i -w 78 -xml --break-before-br yes --indent-attributes yes --indent-spaces 2 --tidy-mark no --vertical-space no

# ----------

check :
	@echo 'OK'
	@exit 0

version :
	cat $(cgDirApp)/VERSION

inc-ver :
	echo '$$Revision: 1.3 $$ $$Date: 2023/05/18 01:53:17 $$ GMT' >$cgDirApp/VERSION

add edit :
	emacs $(cgLoFile) &
	@echo "When done run: make import-lo"

clean :
	-find . -name '*~' -exec rm {} \; &>/dev/null
	-rm $(cgDirTmp)/* $(cgDirTmp)/.pass.tmp &>/dev/null

help :
	@echo "See file: $(cgDirApp)/doc/manual/libre-bib.html"
	sensible-browser file://$(cgDirApp)/doc/manual/libre-bib.html

commit-all :
	cd ..; cvs ci -m Updated
	git ci -am Updated
	git push origin develop

release :
	git co main; git merge develop
	git push origin main
	git co develop

$(cgDbPassCache) :
	read -srp 'Password ($(cgDbPassHint))? '; \
	echo $$REPLY >$@

connect : $(cgDbPassCache)
	@echo First define tunnel: ssh HOST.example.com
	@echo See: ~/ssh/config
	@echo "show databases; use DBNAME; show tables;"
	@echo
	if [[ "$(cgUseRemote)" == "true" ]]; then \
	    tPort=$(cgDbPortRemote); \
	else \
	    tPort=$(cgDbPortLocal); \
	fi; \
	mysql -P $$tPort -u $(cgDbUser) --password=$$(cat $(cgDbPassCache)) -h $(cgDbHost) $(cgDbName)

connect-admin :
	mysql -P $(gPortRemote) -u admin -p -h $(gHost)

$(cgDirStatus) $(cgDirTmp) $(cgDirBackup) $(cgDirConf) ~/.ssh :
	mkdir -p $@

# ========================================
# Normal update of src/$(cgLoFile) and the lo db

# Edit src/$(cgLoFile)
  # Run: make import-lo
  # cvs ci -m Updated
  # This is a full copy from $(cgLoFile), not a merge

# If lo db is modified,
  # Run: make export-lo
  # Diffs can be merged into $(cgLoFile) to create an updated lo db
  # If OK, cp gen/biblio-new.txt to src/$(cgLoFile)
  # cvs ci -m Updated

# Backup
  # Run: make backup-lo
  # cvs ci -m Updated

# ----------
# Import: $(cgLoFile)

import-lo : $(cgDirStatus)/import-lo.date

$(cgDirStatus)/import-lo.date : conf.env $(cgLoFile)
	$(cgBin)/import-txt-2-lo.php -c
	$(cgBin)/convert-lo-2-bib.php -c
	date +%F_%T >$@

# ----------
# export: biblio-new.txt

export-lo :
	$(cgBin)/export-lo-2-txt.php -c
	@echo "See: $(cgDirTmp)/$(cgLoFile)

# ----------
# backup: lo db

backup-lo :
	-cp --backup=t $(cgDirBackup)/backup-lo.csv $(cgDirBackup)/backup-lo.csv.sav
	$(cgBin)/export-lo-2-tcsv.php -c -s c
	date +%F_%T >$(cgDirStatus)/backup-lo.date

restore-lo :
	cgBackup=true; \
	$(cgBin)/import-tcsv-2-lo-db.php -c -s c

# ----------
# If lib-db or lo-db is changed, then run this

update-lo :
	@echo "Update lo from lib where Titles are similar, first 40 char"
	@echo "Run this after lib-db, lo-db"
	$(cgBin)/update-lib-2-lo.php -c
	$(cgBin)/convert-lo-2-bib.php -c
	date +%F_%T >$(cgDirStatus)/update-lo.date

# --------------------
# Update lib-db

## -c config/conf.php is not needed. conf.env valuse will be used to
## create AppDir/etc/conf.php -c

import-lib : $(cgDirStatus)/import-lib.date

$(cgDirStatus)/import-lib.date : $(cgLibFile)
	@echo "librarything schema and import"
	$(cgBin)/import-tsv-2-lib-db.php -c
	date +%F_%T >$@
	head -n 1 $(cgLibFile) | sed 's/ /_/g' >$(cgDirTmp)/lib-schema.tsv
	-diff $(cgDirApp)/etc/lib-schema.tsv $(cgDirTmp)/lib-schema.tsv
	@echo "Warning: If there are differences, there could be problems."

ref-new : $(cgDocFile)
	cp --backup=t $(cgDocFile) $(cgDirBackup)
	$(cgBin)/bib-ref-new.php -c
	date +%F_%T >$(cgDirStatus)/bib-update.date

ref-update :
	cp --backup=t $(cgDocFile) $(cgDirBackup)
	$(cgBin)/bib-ref-update.php -c
	date +%F_%T >$(cgDirStatus)/bib-update.date

# ----------
setup-bib : $(cgDirStatus) $(cgDirTmp) $(cgDirBackup) $(cgDirConf) $(cgDocFile) $(cgLoFile) $(cgLibFile) ~/.ssh/libre-bib.ssh

$(cgLoFile) :
	@echo -e '\nMissing: $@. Copy an example from'
	@echo '$(cgDirApp)/doc/example/biblio.txt'
	cp -i $(cgDirApp)/doc/example/biblio.txt $@
	cp -i $(cgDirApp)/doc/example/biblio-note.txt $(basename $(cgLoFile))-note.txt
	cp -i $(cgDirApp)/doc/example/key.txt key.txt

$(cgDocFile) :
	@echo -e '\nMissing $@. Copy an example from'
	@echo '$(cgDirApp)/doc/example/example.odt'
	-cp -i $(cgDirApp)/doc/example/example.odt $@
	touch $@

$(cgLibFile) :
	@echo -e '\nMissing $@. Copy an example from'
	@echo '$(cgDirApp)/doc/example/librarything.tsv'
	@echo 'Manually update it with an export from LibraryThing.'
	-cp -i $(cgDirApp)/doc/example/librarything.tsv $@
	touch $@

# ----------
status-bib :
	$(cgBin)/bib-status.php -c

# ----------
# Rules

%.md : %.html
	pandoc -f html -t markdown < $<  > $@


%.odt : %.html
	libreoffice --headless --convert-to odt $<

%.html : %.org
	sed 's/^ *- /\n\n/g' $< | \
	pandoc -f org -t html > $@
	sed -i -f $(cgBin)/fixup.sed $@
	-$(mTidyXhtml) $@

# ========================================
# Fixup user customization in etc/

# ----------
rebuild : $(cgDirApp)/etc/conf.php $(cgDirApp)/doc/example/conf.env

$(cgDirApp)/etc/conf.php : $(cgDirApp)/etc/conf.env
	$(cgBin)/gen-conf-php.sh <$? >$@
	echo -e '\nglobal $$cgBin;' >>$@
	echo -e '$$cgBin=$$_ENV["cgBin"];' >>$@
	echo -e '\nglobal $$cgDirApp;' >>$@
	echo -e '$$cgDirApp=$$_ENV["cgDirApp"];' >>$@

$(cgDirApp)/doc/example/conf.env : $(cgDirApp)/etc/conf.env
	sed 's/^export /#export /' <$? >$@
	chmod a+rx $@

conf.env : $(cgDirApp)/doc/example/conf.env
	@if [[ ! -f conf.env  ]]; then \
	    cp -v $? $@; \
	else \
	    echo 'A new conf.env has been copied to $(cgDirTmp)'; \
	    cp -v --backup=t $? $(cgDirTmp); \
	    chmod a+rx $(cgDirTmp)/$?
	fi
	chmod a+rx $@

# ----------
build : rebuild $(cgDirApp)/etc/lo-schema.csv $(cgDirApp)/etc/lib-schema.tsv

$(cgDirApp)/etc/lo-schema.csv : $(cgDirApp)/doc/ref/biblio.csv
	head -n 1 $? | sed 's/,C,254//g; s/,M//g' >$@

$(cgDirApp)/doc/ref/biblio.csv: $(cgDirApp)/doc/ref/biblio.dbf
	libreoffice --headless --convert-to csv $?
	mv biblio.csv $@

$(cgDirLoConf)/biblio.dbf :
	@echo "Error: It looks like Libreoffice is not installed"
	exit 10

$(cgDirApp)/doc/ref/biblio.dbf : $(cgDirLibreofficeConf)/biblio.dbf
	cp $(cgDirLibreofficeConf)/biblio.db* $(cgDirApp)/doc/ref/

$(cgDirApp)/etc/lib-schema.tsv : $(cgDirApp)/doc/ref/librarything.tsv
	head -n 1 <$? | sed 's/ /_/g' >$@

~/.ssh/libre-bib.ssh : ${cgDirApp}/etc/libre-bib.ssh conf.env
	export tHostList="$$cgDbHostRemote $${cgDbHostRemote%%.*}"; \
	envsubst <${cgDirApp}/etc/libre-bib.ssh >$@
	chmod u+rw,go= $@

# ========================================
# Tests

# ----------
verify-lo-schema :
	cp $(cgDirLibreofficeConf)/biblio.db* $(cgDirTmp)/
	cd $(cgDirTmp); libreoffice --headless --convert-to csv biblio.dbf
	cd $(cgDirTmp); head -n 1 biblio.cvs | sed 's/,C,254//g; s/,M//g' >lo-schema.csv
	if ! diff $(cgDirApp)/etc/lo-schema.csv $(cgDirTmp)/lo-schema.csv; then \
	    echo 'Unexpected differences between'; \
	    echo '$(cgDirApp)/etc/lo-schema.csv and'; \
	    echo '$(cgDirTmp)/lo-schema.csv'; \
	fi

# ----------
verify-lib-schema : $(cgLibFile)
	head -n 1 <$? >$(cgDirTmp)/lib-schema.tsv
	if ! diff $(cgDirApp)/etc/lib-schema.tsv $(cgDirTmp)/lib-schema.tsv; then \
	    echo 'Unexpected differences between'; \
	    echo '$(cgDirApp)/etc/lib-schema.tsv and'; \
	    echo '$(cgDirTmp)/lib-schema.tsv'; \
	fi

# ----------
test-reset :
	-rm ../test/example*
	-rm ../test/*~

test-add test-new : ../test/example.odt
	$(cgBin)/bib-ref-new.php -f ../test/example.odt
	mv ../test/example.odt ../test/example-add.odt
	@echo "Verify: libreoffice ../test/example-add.odt"

test-update : ../test/example-add.odt
	cp $? ../test/example.odt
	$(cgBin)/bib-ref-update.php -f ../test/example.odt
	mv ../test/example.odt ../test/example-update.odt
	@echo "Verify: libreoffice ../test/example.odt"

test-dup-add : ../test/example-add.odt
	cp $? ../test/example.odt
	$(cgBin)/bib-ref-new.php -f ../test/example.odt -d
	@echo "Should be 0 references"

test-dup-update : ../test/example-update.odt
	cp $? ../test/example.odt
	$(cgBin)/bib-ref-update.php -f ../test/example-update.odt
	@echo "Should be lots of references"

../test/example.odt : ../doc/example/example.odt
	cp $? $@

# Optional
#../doc/example/example.odt : ../doc/example/example.org
