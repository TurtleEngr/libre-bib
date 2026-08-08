# $Header$

# --------------------
SHELL = /bin/bash

# ========================================
# Adjust these to match the php version

mPkgDep = \
	bash \
	libreoffice \
	libreoffice-sdbc-hsqldb \
	make \
	mariadb-client \
	mariadb-server \
	pandoc \
	perl \
	php \
	php-cli \
	php-common \
	php-fpm \
	php-json \
	php-mbstring \
	php-mysql \
	php-opcache \
	php-readline \
	php-xml \
	sed \
	shellcheck

mPhpUnit = phpunit-9.6.35.phar
    # Version  8.x needs php 7.2.0
    # Version  9.x needs php 7.3.0
    # Version 10.x needs php 8.1.0
    # Version 11.x needs php 8.2.0
    # Version 12.x needs php 8.3.0

mPhpIniFile = /etc/php/8.2/cli/php.ini
            # /etc/php/7.4/cli/php.ini
mPhpIni = \
	'memory_limit = -1' \
	'error_reporting = E_ALL' \
	'log_errors_max_len = 0' \
	'zend.assertions = 1' \
	'assert.exception = 1' \
	'xdebug.show_exception_trace = 0' \
	'variables_order = "EGPCS"'

# ========================================
mUtilScriptDir = ~/ver/github/app/my-utility-scripts

mBin = \
	/usr/bin/php \
	/usr/bin/shellcheck \
	/usr/bin/tidy \
	bin/.phptidy-config.php \
	bin/bash-fmt \
	bin/incver.sh \
	bin/org2html.sh \
	bin/phptidy.php \
	bin/pre-commit \
	bin/rm-trailing-sp \
	bin/shfmt \
	bin/sort-para.sh \
	src/bin/bash-com.inc \
	src/bin/bash-com.test \
	src/bin/shunit2.1 \
	src/bin/$(mPhpUnit)

# ========================================
usage :
	@echo 'clean - remove tmp files'
	@echo 'dist-clean - cleanup the build area'
	@echo 'create-build-env - set up the build env'
	@echo 'test - run all tests'
	@echo 'build - creates dist/'
	@echo 'install - copy dist/ to /opt/libre-bib2/'
	@echo 'package'
	@echo 'release'

# ========================================
clean :
	-find . -type f -name '*~' -exec rm {} \;
	-find . -type f -name pod2htmd.tmp -exec rm {} \;

dist-clean : clean
	-rm -rf dist

# ========================================
create-build-env : get-dep-pkg update-my-utility-scripts $(mBin) .git/hooks/pre-commit check-php-ini
	bin/rm-trailing-sp -t bin/org2html.sh

check-php-ini : $(mPhpIniFile)
	@for tLine in $(mPhpIni); do \
		if ! grep -q "^$$tLine" $(mPhpIniFile); then \
			echo "Error: $$tLine not found in $(mPhpIniFile)"; \
			exit 1; \
		fi; \
	done

get-dep-pkg :
	sudo apt-get update
	@for tPkg in $(mPkgDep); do \
		if ! dpkg -l $$tPkg >/dev/null 2>&1; then \
			sudo apt-get install -y $$tPkg; \
		fi; \
		if ! dpkg -l $$tPkg >/dev/null 2>&1; then \
			echo "Error: could not install $$tPkg"; \
			exit 1; \
		fi; \
	done

# ========================================
test :
	src/bin/bib -T all
	src/bin/bib -T com
	src/bin/phpunit src/test

# ========================================
build :


# ========================================
# Complex Targets

# --------------------
# EPM

mEpmMx=mx19/epm-5.0.2-1-mx19-x86_64.deb
mEpmUbuntu=ubuntu18/epm-5.0.1-2-linux-5.3-x86_64.deb

/usr/local/bin/epm :
	if [[ "$(ProdOSDist)" = "mx" ]]; then \
		cd tmp; wget $(ProdRelRoot)/released/software/ThirdParty/epm/$(mEpmMx); \
		sudo apt-get install -y tmp/$(notdir $(mEpmMx)); \
	fi
	if [[ "$(ProdOSDist)" = "ubuntu" ]]; then \
		cd tmp; wget $(ProdRelRoot)/released/software/ThirdParty/epm/$(mEpmUbuntu); \
		sudo apt-get install -y tmp/$(notdir $(mEpmUbuntu)); \
	fi

# --------------------
# EPM Helper

mEpmHelper=epm-helper-1.6.1-3-linux-noarch.deb

/usr/local/bin/mkver.pl :
	cd tmp; wget $(ProdRelRoot)/released/software/ThirdParty/epm/$(mEpmHelper)
	sudo apt-get install -y tmp/$(mEpmHelper)

# --------------------
# Beekeeper

mBeekeeperVer=3.9.17
mBeekeeper=Beekeeper-Studio-$(mBeekeeperVer).AppImage

/usr/local/bin/beekeeper : /usr/local/bin/$(mBeekeeper)
	cd /usr/local/bin; sudo ln -sf $(mBeekeeper) beekeeper

/usr/local/bin/$(mBeekeeper) :
	cd tmp; wget https://github.com/beekeeper-studio/beekeeper-studio/releases/download/v$(mBeekeeperVer)/$(mBeekeeper)
	sudo mv -f tmp/$(mBeekeeper) $@
	sudo chmod a+rx $@

# ========================================
# Single Targets

update-my-utility-scripts : $(mUtilScriptDir)
	cd $(mUtilScriptDir); git pull origin develop

$(mUtilScriptDir) :
	cd ~/ver/github/app/; \
	git clone git@github.com:TurtleEngr/my-utility-scripts.git

bin/incver.sh : $(mUtilScriptDir)/bin/incver.sh
	cp $? $@

bin/org2html.sh : $(mUtilScriptDir)/bin/org2html.sh
	cp $? $@

bin/phptidy.php : $(mUtilScriptDir)/bin/phptidy.php
	cp $? $@

bin/.phptidy-config.php : $(mUtilScriptDir)/bin/.phptidy-config.php
	cp $? $@

bin/pre-commit : $(mUtilScriptDir)/bin/pre-commit
	cp $? $@

bin/rm-trailing-sp : $(mUtilScriptDir)/bin/rm-trailing-sp
	cp $? $@

bin/bash-fmt : $(mUtilScriptDir)/bin/bash-fmt
	cp $? $@

bin/shfmt : $(mUtilScriptDir)/bin/shfmt
	cp $? $@

src/bin/shunit2.1 : $(mUtilScriptDir)/bin/shunit2.1
	cp $? $@

src/bin/bash-com.inc : $(mUtilScriptDir)/bin/bash-com.inc 
	cp $? $@

src/bin/bash-com.test : $(mUtilScriptDir)/bin/bash-com.test
	cp $? $@

bin/sort-para.sh : $(mUtilScriptDir)/bin/sort-para.sh
	cp $? $@

.git/hooks/pre-commit : $(mUtilScriptDir)/bin/pre-commit
	cp $? $@

/usr/bin/shellcheck :
	sudo apt-get install shellcheck

/usr/bin/tidy :
	sudo apt-get install tidy

/usr/bin/php :
	sudo apt-get install php php-common

phpunit src/bin/$(mPhpUnit) :
	rsync -P moria.whyayh.com:/rel/archive/software/ThirdParty/phpunit/*.phar src/bin/
	cd src/bin; ln -sf $(mPhpUnit) phpunit
