
mAppDir = /opt/libre-bib

# Cleanup and make dist/ area
build :

# Make deb package
package :

# Manual install - only for testing
install :
	find src -name '*~' -exec rm {} \;
	rsync -aC src/* $(mAppDir)/
	find $(mAppDir) -type d -exec chmod a+rx {} \;
	find $(mAppDir) -type f -exec chmod a+r {} \;
	find $(mAppDir) -type f -executable -exec chmod a+rx {} \;

mk-app-dir :
	sudo mkdir -p $(mAppDir)
	sudo chown -R $SUDO_USER:$SUDO_USER $(mAppDir)
	sudo find $(mAppDir) -type d -exec chmod a+rx {} \;
	sudo find $(mAppDir) -type f -exec chmod a+r {} \;
