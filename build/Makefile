# build Makefile

mRoot = dist/opt/libre-bib
mDirList = $(mRoot) dist/usr/local/bin
mCoreDir = ../src

# ----------------------------------------
all : mkCore

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
build-setup : ../.git/hooks/pre-commit bin/incver.sh bin/phptidy.php bin/rm-trailing-sp bin/shfmt bin/sort-para.sh

$(mRoot)/bin/phptidy.php : bin/phptidy.php
	'rsync' -a $? $@

$(mRoot)/bin : bin/sort-para.sh
	'rsync' -a $? $@

# The detault gitproj.hook.tab-include-list is '*"
#     Only text files are looked at.
# gitproj.hook.tab-exclude-list is a "grep -E" pattern
../.git/hooks/pre-commit : etc/pre-commit
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

mGitProj=tag-0-7-6-1
etc/pre-commit :
	wget https://github.com/TurtleEngr/gitproj/archive/refs/tags/$(mGitProj).tar.gz
	tar -xzf $(mGitProj).tar.gz gitproj-$(mGitProj)/doc/hooks/pre-commit
	mv gitproj-$(mGitProj)/doc/hooks/pre-commit $@
	chmod a+rx $@
	rm -rf $(mGitProj).tar.gz gitproj-$(mGitProj)

mMyUtil=tag-0-3-0
bin/incver.sh bin/rm-trailing-sp bin/sort-para.sh :
	wget https://github.com/TurtleEngr/my-utility-scripts/archive/refs/tags/$(mMyUtil).tar.gz
	tar -xzf $(mMyUtil).tar.gz my-utility-scripts-$(mMyUtil)/bin/incver.sh my-utility-scripts-$(mMyUtil)/bin/rm-trailing-sp my-utility-scripts-$(mMyUtil)/bin/sort-para.sh
	mv my-utility-scripts-$(mMyUtil)/bin/* bin
	rm $(mMyUtil).tar.gz

mShFmt=v3.1.2
bin/shfmt :
	wget https://github.com/mvdan/sh/releases/download/$(mShFmt)/shfmt_$(mShFmt)_linux_amd64
	mv shfmt_$(mShFmt)_linux_amd64 $@
	chmod a+rx $@

mPhpTidy=3.3
bin/phptidy.php :
	wget https://github.com/cmrcx/phptidy/releases/download/v$(mPhpTidy)/phptidy-$(mPhpTidy).tar.gz
	tar -xzf phptidy-$(mPhpTidy).tar.gz phptidy/phptidy.php
	mv phptidy/phptidy.php $@
	chmod a+rx $@
	rm -rf phptidy-$(mPhpTidy).tar.gz phptidy

check :
	build/bin/check.sh
