# TODO

These are some rough spots. If items remain unfinished in the TODO
file, for a while, I'll make them "issues."

## Output

- If cgVerbose is false, error messages are not visible.
  For now set cgVerbose to true.

- Add syslog logging?

## Manual

- Cleanup the manual (e.g. look at the md file.)

- Describe how to connect Libreoffice to the DB. The Libreoffice
  directions make it sound simple, it is only after you have done it
  once. Details are needed!

- Give directions for how to update the cite-*.xml files. There is no
  guarantee the xml tag for "Endnote Character" will be the same for
  all odt files.

- If the cite-*.xml files need to be changed, then there needs to be
  an easy way to revert all the biblio-entries to just the {REF} tags.

- Add a full description of how the {REF} tags are formatted.

- The install directions do not mention creating this symlink:

  ln -s /opt/libre-bib/bin/bib /usr/local/bin/

## Backup

- The "cgBackupNum" is not implemented, so backup tables and files
  just keep growing. When implemented, setting it to "2" could be an
  option for removing all but two backups.

- If there have been any errors, backups will not be removed until two
  (or more) days have elapsed. So the number of backups could be more
  than cgBackupNum. Maybe I'll implement this.

- Get rid of the cgBackup boolean. Always do backups.

## Build

- Make a deb install package. The hard part: defining the dependent
  packages for different distributions.

- Make a tgz package with a simple install script (Makefile?)

- Fix the permissions to the app so the user can change files in etc/
  and doc/. "make rebuild" can be used fix up dependent files.
  libre-bib/ and libre-bib/bin/* should be owned by root and only root
  writable.
