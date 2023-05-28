
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
# bib Commands

check :
	@echo 'OK'
	@exit 0

version ver :
	cat $(cgDirApp)/VERSION

add edit :
	$(EDITOR) $(cgLoFile) &
	@echo "When done run: make import-lo"

clean :
	-find . -name '*~' -exec rm {} \; &>/dev/null
	-rm $(cgDirTmp)/* $(cgDirTmp)/.pass.tmp &>/dev/null

help :
	@echo "See file: $(cgDirApp)/doc/manual/libre-bib.html"
	sensible-browser file://$(cgDirApp)/doc/manual/libre-bib.html

connect : $(cgDbPassCache)
	@echo
	@echo "Test: show databases; use DBNAME; show tables; quit"
	if [[ "$(cgUseRemote)" == "true" ]]; then \
	    tPort=$(cgDbPortRemote); \
	    echo First define tunnel: ssh HOST.example.com; \
	    echo See: ~/ssh/config; \
	else \
	    tPort=$(cgDbPortLocal); \
	fi; \
	mysql -P $$tPort -u $(cgDbUser) --password=$$(cat $(cgDbPassCache)) -h $(cgDbHost) $(cgDbName)

$(cgDbPassCache) :
	read -srp 'Password ($(cgDbPassHint))? '; \
	echo $$REPLY >$@

setup-bib : $(cgDirEtc) $(cgDirStatus) $(cgDirTmp) $(cgDirBackup) $(cgDirConf) $(cgLoFile) $(cgLibFile) $(cgDocFile) ~/.ssh/libre-bib.ssh

# ----------
# Import: $(cgLoFile)

import-lo : $(cgDirStatus)/import-lo.date

$(cgDirStatus)/import-lo.date : conf.env $(cgLoFile)
	$(cgBin)/import-txt-2-lo.php -c
	$(cgBin)/convert-lo-2-bib.php -c
	date +%F_%T >$@

# ----------
# export: tmp/biblio.txt

export-lo :
	$(cgBin)/export-lo-2-txt.php -c
	@echo "See: $(cgDirTmp)/$(cgLoFile)"

# ----------
# backup: lo-db to backup/

backup-lo :
	-cp --backup=t $(cgDirBackup)/backup-lo.csv $(cgDirBackup)/backup-lo.csv.sav
	$(cgBin)/export-lo-2-tcsv.php -c -s c
	date +%F_%T >$(cgDirStatus)/backup-lo.date

restore-lo :
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

import-lib : $(cgDirStatus)/import-lib.date

$(cgDirStatus)/import-lib.date : $(cgLibFile)
	@echo "librarything schema and import"
	$(cgBin)/import-tsv-2-lib-db.php -c
	date +%F_%T >$@
	head -n 1 $(cgLibFile) | sed 's/ /_/g' >$(cgDirTmp)/lib-schema.tsv
	-diff $(cgDirApp)/etc/lib-schema.tsv $(cgDirTmp)/lib-schema.tsv
	@echo "Warning: If there are differences, there could be problems."

# --------------------
ref-new : $(cgDocFile) $(cgDirEtc)/cite-new.xml
	cp --backup=t $(cgDocFile) $(cgDirBackup)
	$(cgBin)/bib-ref-new.php -c
	date +%F_%T >$(cgDirStatus)/bib-update.date

$(cgDirEtc)/cite-new.xml : $(cgDirApp)/etc/cite-new.xml
	-cp --backup=t $@ $(cgDirBackup)
	cp $? $@

# --------------------
ref-update : $(cgDocFile) $(cgDirEtc)/cite-update.xml
	cp --backup=t $(cgDocFile) $(cgDirBackup)
	$(cgBin)/bib-ref-update.php -c
	date +%F_%T >$(cgDirStatus)/bib-update.date

$(cgDirEtc)/cite-update.xml : $(cgDirApp)/etc/cite-update.xml
	-cp --backup=t $@ $(cgDirBackup)
	cp $? $@

# --------------------
save-style : $(cgDirEtc) $(cgDirBackup) $(cgDocFile) $(cgDirEtc)/bib-style.xml $(cgDirEtc)/bib-template.xml
	-cp $(cgDirEtc)/*.xml $(cgDirBackup)
	$(cgBin)/bib-style-save.php -c

# --------------------
update-style : $(cgDirEtc) $(cgDirEtc)/bib-style.xml $(cgDirEtc)/bib-template.xml
	$(cgBin)/bib-style-update.php -c

$(cgDirEtc)/bib-style.xml : $(cgDirApp)/etc/bib-style.xml
	-cp --backup=t $@ $(cgDirBackup)
	cp --backup=t $? $@

$(cgDirEtc)/bib-template.xml : $(cgDirApp)/etc/bib-template.xml
	-cp --backup=t $@ $(cgDirBackup)
	cp --backup=t $? $@

# --------------------
status-bib :
	$(cgBin)/bib-status.php -c

# ========================================
# Rules supporting cmds

conf.env : $(cgDirApp)/doc/example/conf.env
	@if [[ ! -f conf.env  ]]; then \
	    cp -v $? $@; \
	    chmod a+rx $@; \
	else \
	    echo 'A new conf.env has been copied to $(cgDirTmp)'; \
	    cp -v --backup=t $? $(cgDirTmp); \
	    chmod a+rx $(cgDirTmp)/$@; \
	    touch $@; \
	    diff -ZBbw $? $@; \
	fi

$(cgDirBackup) $(cgDirConf) $(cgDirEtc) $(cgDirStatus) $(cgDirTmp) ~/.ssh :
	mkdir -p $@

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
# Extension Rules

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
# Fixup user customizations in etc/

# ----------
rebuild : $(cgDirApp)/etc/conf.php $(cgDirApp)/doc/example/conf.env

$(cgDirApp)/etc/conf.php : $(cgDirApp)/etc/conf.env
	$(cgBin)/gen-conf-php.sh <$? >$@
	echo -e '\nglobal $$cgBin;' >>$@
	echo -e '$$cgBin=$$_ENV["cgBin"];' >>$@
	echo -e '\nglobal $$cgDirApp;' >>$@
	echo -e '$$cgDirApp=$$_ENV["cgDirApp"];' >>$@

$(cgDirApp)/doc/example/conf.env : $(cgDirApp)/etc/conf.env
	sed 's/^export /    #/' <$? >$@
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
