# -*- mode: makefile -*-

# -c config/conf.php can be ignored. Just include:
# $_ENV["cgDirApp"]/etc/conf.php

# --------------------
# Macros

SHELL = /bin/bash

mConfigDB = ~/.config/libreoffice/4/user/database

mDate = $$(date +%F_%T)

mTidyXhtml = tidy -m -q -i -w 78 -asxhtml --break-before-br yes --indent-attributes yes --indent-spaces 2 --tidy-mark no --vertical-space no

mTidyWide = tidy -q -i -w 4000 -asxhtml --break-before-br no --indent-attributes no --indent-spaces 2 --tidy-mark no --vertical-space no

mTidyXml = tidy -q -i -w 78 -xml --break-before-br yes --indent-attributes yes --indent-spaces 2 --tidy-mark no --vertical-space no

# --------------------
# bib Commands

check :
	@echo 'OK'
	@exit 0

version ver :
	cat $(cgDirApp)/VERSION

add edit edit-lo :
	$(EDITOR) $(cgLoFile) &
	@echo "When done run: bib import-lo"

edit-conf :
	$(EDITOR) ./conf.env &
	@echo "When done run: bib setup-bib"

clean :
	-$(cgBin)/rm-old-files.sh all $(cgBackupNum)
	-$(cgBin)/rm-old-tables.sh all $(cgBackupNum)
	-rm *~ $(cgDirTmp)/* &>/dev/null

clean-all : clean
	-rm $(cgDirTmp)/.pass.tmp &>/dev/null

# ----------
help :
	@echo "See file: $(cgDirApp)/doc/manual/libre-bib.html"
	sensible-browser file://$(cgDirApp)/doc/manual/libre-bib.html &>/dev/null &
	exit 1

# ----------
setup-bib : conf.env
	@echo "Edit conf.env then: bib setup-dir"

# ----------
setup-dir : $(cgDirEtc) $(cgDirStatus) $(cgDirTmp) $(cgDirCache) $(cgDirBackup)  $(cgLoFile) $(cgDocFile)
	@echo "Now run: bib setup-db"

$(cgDirEtc) :
	mkdir -p $@
	cp -n $(cgDirApp)/etc/* $@

$(cgDirStatus) $(cgDirBackup) $(cgDirTmp) $(cgDirCache) :
	mkdir -p $@

# ----------
setup-db : $(cgDirStatus)/db-setup.date

$(cgDirStatus)/db-setup.date : $(cgDbPassCache) $(cgDirCache)/.root.pass $(cgDirCache)/.admin.pass
	@echo
	$(cgBin)/db-create.sh -c
	@echo "$(mDate) db-setup" >$@
	@echo "Now test with: bib connect"

$(cgDbPassCache) :
	@echo
	@read -srp 'DB $(cgDbUser) Password ($(cgDbPassHint))? '; \
	echo $$REPLY | caesar 13 >$@

$(cgDirCache)/.root.pass :
	@echo
	@read -srp 'DB root Password? '; \
	echo $$REPLY | caesar 13 >$@

$(cgDirCache)/.admin.pass :
	@echo
	@read -srp 'DB admin Password? '; \
	echo $$REPLY | caesar 13 >$@

# ----------
connect : $(cgDbPassCache)
	@echo
	@echo "Test: show databases; use $(cgDbName); show tables; quit"; \
	mysql -P $(cgDbPortLocal) -u $(cgDbUser) --password=$$(cat $(cgDbPassCache) | rot13) -h $(cgDbHost) $(cgDbName)

# --------------------
# Import: $(cgLoFile)

import-lo : $(cgDirStatus)/import-lo.date $(cgDirStatus)/update-bib.date
	@echo "Done. $(cgDbTblLo) table is up-to-date with $(cgLoFile)"

$(cgDirStatus)/import-lo.date : conf.env $(cgLoFile) $(cgBin)/import-txt-2-lo.php
	$(cgBin)/import-txt-2-lo.php -c
	echo "$(mDate) import-lo" >$@

update-bib : $(cgDirStatus)/update-bib.date

$(cgDirStatus)/update-bib.date : conf.env $(cgLoFile) $(cgBin)/convert-lo-2-bib.php
	$(cgBin)/convert-lo-2-bib.php -c
	echo "$(mDate) update-bib" >$@

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
	echo "$(mDate) backup-lo" >$(cgDirStatus)/$@.date

restore-lo :
	echo "Are you sure you want to replace the $(cgDbTblLo) table?"
	read -p "y/n: "
	if [[ $$REPLY != "y" ]]; then exit 10; fi
	$(cgBin)/import-tcsv-2-lo-db.php -c -s c
	echo "$(mDate) restore-lo" >$(cgDirStatus)/$@.date

# --------------------
ref-new : $(cgDirStatus)/ref-new.date
	@echo "Done, adding new refs to $(cgDocFile)"

$(cgDirStatus)/ref-new.date : $(cgDocFile) $(cgDirEtc)/cite-new.xml
	cp --backup=t $(cgDocFile) $(cgDirBackup)
	$(cgBin)/bib-ref-new.php -c
	echo "$(mDate) ref-new" >$@

$(cgDirEtc)/cite-new.xml : $(cgDirApp)/etc/cite-new.xml
	-cp --backup=t $@ $(cgDirBackup)
	cp $? $@

# --------------------
ref-update : $(cgDirStatus)/ref-update.date
	@echo "Done, updating refs in $(cgDocFile)"

$(cgDirStatus)/ref-update.date : $(cgDocFile) $(cgDirEtc)/cite-update.xml
	cp --backup=t $(cgDocFile) $(cgDirBackup)
	$(cgBin)/bib-ref-update.php -c
	echo "$(mDate) ref-update" >$@

$(cgDirEtc)/cite-update.xml : $(cgDirApp)/etc/cite-update.xml
	-cp --backup=t $@ $(cgDirBackup)
	cp $? $@

# --------------------
style-save : $(cgDirStatus)/style-save.date
	@echo "Done, saving bib style from $(cgDocFile)"

$(cgDirStatus)/style-save.date : $(cgDocFile)
	-cp --backup=t $(cgDirEtc)/bib-style.xml $(cgDirBackup)
	-cp --backup=t $(cgDirEtc)/bib-template.xml $(cgDirBackup)
	$(cgBin)/bib-style-save.php -c
	echo "$(mDate) style-save" >$@

# --------------------
style-update : $(cgDirStatus)/style-update.date
	@echo "Done, updating bib style in $(cgDocFile)"

$(cgDirStatus)/style-update.date : $(cgDocFile) $(cgDirEtc)/bib-style.xml $(cgDirEtc)/bib-template.xml
	cp --backup=t $(cgDocFile) $(cgDirBackup)
	$(cgBin)/bib-style-update.php -c
	echo "$(mDate) style-update" >$@

$(cgDirEtc)/bib-style.xml : $(cgDirApp)/etc/bib-style.xml
	-cp --backup=t $@ $(cgDirBackup)
	cp $? $@

$(cgDirEtc)/bib-template.xml : $(cgDirApp)/etc/bib-template.xml
	-cp --backup=t $@ $(cgDirBackup)
	cp $? $@

# --------------------
status-bib :
	$(cgBin)/bib-status.php -c
	@echo
	@echo "Last run commands:"
	@tail -n 1 $(cgDirStatus)/*.date | sort -r

# ========================================
# Rules supporting cmds

conf.env : $(cgDirApp)/doc/example/conf.env
	-if [[ ! -f $@  ]]; then \
	    cp -v $? $@; \
	    chmod a+rx $@; \
	else \
	    echo 'A new conf.env has been copied to $(cgDirTmp). Merge changes with conf.env'; \
	    cp -v $? $(cgDirTmp); \
	    chmod a+rx $(cgDirTmp)/$@; \
	    touch $@; \
	    diff -ZBbw $@ $?; \
	fi

$(cgLoFile) :
	@echo -e "\nMissing: $@. Copy an example $(cgLoFile) from"
	@echo "$(cgDirApp)/doc/example/biblio.txt"
	cp -i $(cgDirApp)/doc/example/biblio.txt $@
	cp -i $(cgDirApp)/doc/example/biblio-note.txt $(basename $(cgLoFile))-note.txt
	cp -i $(cgDirApp)/doc/example/key.txt key.txt

$(cgDocFile) :
	@echo -e '\nMissing $@. Copy an example from'
	@echo '$(cgDirApp)/doc/example/example.odt'
	-cp -i $(cgDirApp)/doc/example/example.odt $@
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
	chmod a+rx $@

$(cgDirApp)/doc/example/conf.env : $(cgDirApp)/etc/conf.env
	sed 's/^export /    #/' <$? >$@
	chmod a+rx $@
