
export SHELL = /bin/bash

export cgDirApp = /opt/libre-bib
export cgBin = $(cgDirApp)/bin
mMake = . src/etc/conf.env; make -f src/bin/Makefile


clean:
	-find . -type f -name '*~' -exec rm {} \; &>/dev/null
	-find . -type f -name '.phptidy-cache' -exec rm {} \; &>/dev/null
	-find . -type f -name '*.tmp' -exec rm {} \; &>/dev/null
	-find . -type f -name '*.bak' -exec rm {} \; &>/dev/null

# Cleanup and make dist/ area
build : build-setup check

# Make deb package
package :

# Manual install - only for testing
install : $(cgDirApp) check
	-find src -name '*~' -exec rm {} \; &>/dev/null
	-mkdir $(cgDirApp)/etc/old &>/dev/null
	cp --backup=t $$(find $(cgDirApp)/etc/* -prune -type f) $(cgDirApp)/etc/old/
	rsync -aC src/* $(cgDirApp)/
	find $(cgDirApp) -type d -exec chmod a+rx {} \;
	find $(cgDirApp) -type f -exec chmod a+r {} \;
	find $(cgDirApp) -type f -executable -exec chmod a+rx {} \;
	ln -fs /opt/libre-bib/bin/bib /usr/local/bin/bib

mk-app-dir $(cgDirApp) :
	sudo mkdir -p $(cgDirApp)
	sudo chown -R $SUDO_USER:$SUDO_USER $(cgDirApp)
	sudo find $(cgDirApp) -type d -exec chmod a+rx {} \;
	sudo find $(cgDirApp) -type f -exec chmod a+r {} \;

mk-doc :
	-$(mMake) src/doc/manual/libre-bib.md
	-$(mMake) src/doc/example/example-outline.html

build-setup : .git/hooks/pre-commit

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
