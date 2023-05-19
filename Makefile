
export SHELL = /bin/bash

export cgDirApp = /opt/libre-bib
export cgBin = $(cgDirApp)/bin
mMake = . src/etc/conf.env; make -f src/bin/Makefile


clean:
	find . -type f -name '*~' -exec rm {} \;

# Cleanup and make dist/ area
build :

# Make deb package
package :

# Manual install - only for testing
install :
	find src -name '*~' -exec rm {} \;
	rsync -aC src/* $(cgDirApp)/
	find $(cgDirApp) -type d -exec chmod a+rx {} \;
	find $(cgDirApp) -type f -exec chmod a+r {} \;
	find $(cgDirApp) -type f -executable -exec chmod a+rx {} \;

mk-app-dir :
	sudo mkdir -p $(cgDirApp)
	sudo chown -R $SUDO_USER:$SUDO_USER $(cgDirApp)
	sudo find $(cgDirApp) -type d -exec chmod a+rx {} \;
	sudo find $(cgDirApp) -type f -exec chmod a+r {} \;

mk-doc :
	$(mMake) src/doc/manual/libre-bib.md
	$(mMake) src/doc/example/example-outline.html
