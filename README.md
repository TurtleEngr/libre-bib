# libre-bib

[//]: # (For more badges see: https://shields.io/badges)

Libre-Bib is still in development.

![GitHub issue custom search](https://img.shields.io/github/issues-search?query=repo%3ATurtleEngr%2Flibre-bib%20is%3Aopen&style=flat&label=issues)
![GitHub License](https://img.shields.io/github/license/TurtleEngr/libre-bib)

This tool will help with managing a large Libreoffice Bibliography.  It
can be used across multiple Libreoffice odt documents.

Libre-Bib is mostly functional. I am using it for managing a large
Bibliography with about 3,000 records, and my LibraryThing DB has over
400 references to books or media I own.

## Features

### Libreoffice Database

* Add bibliographic entries with a simple text file (biblio.txt).
  This is the only file that you will not want to lose (so version
  it). Everything else can be rebuilt from this file.

* Import the text file into a Libreoffice compatible DB (lo-db).

* Update the lo-db from changes in text file.

* Make a backup csv file of the lo-db.

### LibraryThing Database

The [LibraryThing](https://www.librarything.com/home) application can
used to very quickly collect your book's information, by using the
ISBN bar codes. No need to type, author, publisher, date, etc.

* Import an exported LibraryThing tsv file to lib-db.

* Merge selected fields from the lib-db to the lo-db.

* Export a new biblio.txt file with the lo-db updates.

### Formatted Bibliography

* Make a partially formatted bib-db from the lib-db.  This DB table
  makes formatting the bibliography entries easier. For example most
  of the non-empty fields will be prefixed with ", ".

* The supported Bibliography types: articles, books (all types), misc
  (media, videos, DVDs, mp4, audio, etc.), www (links)

### Make references to bib-db in your Write document

* Automatic insert of bib reference tags in your Libreoffice Write
  document (odt). (No need to highlight then select the bibliographic
  reference; the {REF} tag is all you need, so you can keep writing
  and not be slowed down with a manual GUI steps.)

* Update new {REF} tags, so they will include the fields from the
  bib-db. Also the refs will be formatted with "Endnote Character"
  style.

* Update existing {REF} tags with any changes in the bib-db.

### Emacs Org-Mode helper

* Optionally the Libreoffice Write file can be created from an emacs
  org-mode file. See
  [example-outline.org](src/doc/example/example-outline.org)

### Requirements and Installation

See the [Libre Bib Manual](src/doc/manual/libre-bib.md) for system and
package requirements.
