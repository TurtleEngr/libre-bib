# libre-bib
A tool for managing a large Libreoffice Bibliography

## Features

### Libreoffice Database

* Add bibliographic entries to a simple text file

* Import the text file into a Libreoffice compatible DB (lo-db)

* Update the lo-db from the text file

* Make a backup csv file of the lo-db

### LibraryThing Database

* Import a LibraryThing tsv file (lib-db)

* Merge selected fields from lib-db to the lo-db

### Formatted Bibliography

* Make an updated DB (bib), with changes that makes biblo layout easier.

* Bibliography types supported: articles, books (all types), misc
  (videos, DVDs, mp4, audio, etc.), www (links)

### Make references to bib-db in your Write document

* Simple insert of bib reference tags in your Libreoffice Write
  document (odt)

* Update new bib-db references, so they include the fields from the
  bib-db. Also the refs will be formatted with EndNote text style.

* Update bib references with any changes in the bib-db.

### Optional

* Optionally the Libreoffice Write file can be created from an emacs
  org file.

## Requirements

* Linux system

### Required Packages

* libreoffice
* libreoffice-sdbc-mysql (needed for DB connection)
* mariadb-client - mysql
* mariadb-server - mariadbd (only on remote host)
* php
* php-mysqlnd - php-PDO
* perl - pod2html, pod2man, pod2text, pod2usage
* bash
* tidy
* make (script and file manager)

### Optional Packages

* ssh - if using a remote DB
* epm-helper - mkver.pl (generate conf.* files)
* sed
* pandoc
* libpod-markdown-perl - pod2markdown
* pod2pdf
* beekeeper - https://github.com/beekeeper-studio/beekeeper-studio

## Installation and Usage

See doc/manual/libre-bib.md
