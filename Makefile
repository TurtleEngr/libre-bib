
mAppDir = /opt/libre-bib

# Cleanup and make dist/ area
build :

# Make deb package
package :

# Manual install
install :
	mkdir -p $(mAppDir)
	cd $(mAppDir); mkdir bin etc doc
	cp src/bin/* $(mAppDir)/bin
	cp src/config/* $(mAppDir)/etc

