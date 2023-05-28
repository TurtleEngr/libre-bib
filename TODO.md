# TODO

See the issues section for this app. The Milestones will give an idea
for the proiority. But no dates have been set.

- Fix up bib-ref-new.php and bib-ref-update.php to use xml files in
  cgDirEtc

- Implement: save-style
  - Run this if style is changed
  - uppack Doc
  - error if no bibliography index has been defined
  - cp any existing xml files in etc/ to backup/
  - run bib-style-save.php to save xml files in Doc to etc/

- Implement: update-style
  - Recommend this is run first
  - uppack Doc
  - error if no bibliography index has been defined
  - if no xml files, cp them from app/etc/
  - run bib-style-update.php to use xml file in etc/ to update Doc
  - pack Doc

- Remove cgNoExecCmd (not needed).
- Remove cgBackup, always backup

- Documented more in manual: files and vars
