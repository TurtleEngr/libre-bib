# $Header$

# --------------------
SHELL = /bin/bash

# ========================================
# Adjust these to match the php version

mPkgDep = \
	mariadb-client \
	mariadb-client-core \
	mariadb-common \
	mariadb-plugin-provider-bzip2 \
	mariadb-plugin-provider-lz4 \
	mariadb-plugin-provider-lzma \
	mariadb-plugin-provider-lzo \
	mariadb-plugin-provider-snappy \
	mariadb-server \
	mariadb-server-core \
	libapache2-mod-php \
	libapache2-mod-php8.2 \
	shellcheck \
	tidy \
	wget \
	php \
	php-common \
	php-mysql \
	$(mPhp8.2)

mPhp7.4 = \
	php7.4 \
	php7.4-cli \
	php7.4-common \
	php7.4-json \
	php7.4-mysql \
	php7.4-opcache \
	php7.4-readline \
	php7.4-mbstring \
	php7.4-xml

mPhp8.2 = \
	php8.2 \
	php8.2-cli \
	php8.2-common \
	php8.2-fpm \
	php8.2-mysql \
	php8.2-opcache \
	php8.2-readline \
	php8.2-mbstring \
	php8.2-xml

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
test :
	src/bin/bib -T all
	src/bin/bib -T com
	src/bin/phpunit src/test

# ========================================
create-build-env : get-dep update-my-utility-scripts $(mBin) .git/hooks/pre-commit check-php-ini
	bin/rm-trailing-sp -t bin/org2html.sh

check-php-ini : $(mPhpIniFile)
	@for tLine in $(mPhpIni); do \
		if ! grep -q "^$$tLine" $(mPhpIniFile); then \
			echo "Error: $$tLine not found in $(mPhpIniFile)"; \
			exit 1; \
		fi; \
	done

get-dep :
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

