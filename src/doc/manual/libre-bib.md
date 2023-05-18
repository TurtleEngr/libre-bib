------------------------------------------------------------------------

------------------------------------------------------------------------

Libre Bib Manual
================

------------------------------------------------------------------------

------------------------------------------------------------------------

Install and Setup
=================

------------------------------------------------------------------------

Install Package
---------------

-   If you install with a libre-bib.deb package with a package manager
    such as \"apt\", all of the required and most of the optional
    packages will be installed.

-   If you are installing from tgz file, then you\'ll need to install
    these manually.

### Required Packages

-   libreoffice
-   libreoffice-sdbc-mysql - needed for libreoffice DB connection
-   mariadb-client - mysql
-   mariadb-server - mariadbd (only on remote host)
-   php
-   php-mysqlnd - php-PDO
-   perl - pod2html, pod2man, pod2text, pod2usage
-   bash
-   sed
-   tidy
-   make - for script and file management

### Optional Packages

-   pandoc

-   libpod-markdown-perl - pod2markdown

-   pod2pdf

-   beekeeper - <https://github.com/beekeeper-studio/beekeeper-studio>

### Config

-   run libreoffice at least once before doing more with libre-bib

-   edit the cli/php.ini file (for example: /etc/php/7.4/cli/php.ini)
    Change the `"variables_order"` to this:

    > `variables_order = "EGPCS"`

------------------------------------------------------------------------

Setup libre-bib project
-----------------------

Run:

> bib setup-bib

Fix any errors then run it again, util no more errors.

If you are planing on using a remote DB, then see the \"Configure ssh\"
section.

------------------------------------------------------------------------

Configure the DB
----------------

### Install and test

The DB packages, mariadb-client and mariadb-server, have been installed
on the remote server (or local sever if you are doing this all on one
server). Most likely the mariadbd process will already be runnng. Verify
this with:

> ps -fC mariadbd

If you don\'t see it running, you\'ll need to consult the mariadb docs
to get it running.

-   <https://opensource.com/article/20/10/mariadb-mysql-linux> (alt:
    <https://archive.ph/yhDHm> )
-   <https://mariadb.com/docs/server/ref/cs10.3/>

The first one is a good source for quickly getting going. Depending on
your distribution, you may need to do things a bit differently.

Test the connection on the server system

> sudo mysql -P 3306 -u root -p

Most likely you\'ll use your sudo password, or the password you setup
for the mysql DB root user.

### Create Database, Users, and Grants

While signed in as root user to the DB type these commands. Replace the
\$cgNAME variables with the values of those variables in your
project/conf.env file. You can change those now or just use the example
names to try things out.

-   Connect to the DB

    > sudo -s mysql -P 3306 -u root -p

-   Create the DB

    > create database \$cgDbName; show databases;

-   Create users

    The create user and grants are best done with the \'root\' DB user
    on the mysql system.

    > create user \'admin\'@\'localhost\' identified by \'ADMIN-PASS\';
    > grant all privileges on **.** to \'admin\'[\@localhost]{.citation
    > cites="localhost"};
    >
    > create user \'\$cgDbUser\'@\'localhost\' identified by
    > \'USER-PASS\'; grant all privileges on \$cgDbName.\* to
    > \'\$cgDbUser\'[\@localhost]{.citation cites="localhost"};
    >
    > flush privileges;
    >
    > select user from mysql.user; show grants for
    > \'root\'[\@localhost]{.citation cites="localhost"}; show grants
    > for \'admin\'[\@localhost]{.citation cites="localhost"}; show
    > grants for \'\$cgDbName\'[\@localhost]{.citation
    > cites="localhost"};
    >
    > quit;

-   Test a local connection with \$cgDbName

    > mysql -P 3306 -u \$cgDbName -p -h 127.0.0.1 \$cgDbName

-   If you will be using libre-bib on the same system as the DB, then
    try connecting with the \"bib\" command.

    > bib connect

If that doesn\'t work look at the cgDsn variable setting in
project/conf.env. It should be set to \$cgLocalDsn for local access. Try
again, If that works, your conf.env setting are good for continuing
(skip the ssh section and other areas mentioning remote db access).

------------------------------------------------------------------------

Configure ssh
-------------

In your \~/.ssh/ dir you should see a libre-bib.ssh file. For this to be
setup properly edit your project/conf.env file. Set the variables:

  conf.env var     Description
  ---------------- ------------------------------------------------------------
  cgDbHost         keep this set to the localhost IP
  cgDbName         name of the mysql database
  cgDbPortRemote   remote port, on project\'s system. Can be any unused port.
  cgDbLocalPort    port for mysql on the remote system (probably no change)
  cgDbUser         DB user with grants to cgDbName and all of it\'s tables
  cgDbPassHint     hint for the password prompt
  cgDbSshUser      user that can login to the remote system
  cgDbSshKey       key used by user for login to the remote system

Remove \~/ssh/libre-bib.ssh file and run again:

bib setup-bib

If the \~/ssh/libre-bib.ssh file looks OK, add following line top of
your \~/.ssh/config file (or near a Host config for your system).

> Include libre-bib.ssh

If you want to add more ssh options for the Host, don\'t add them to
libre-bib.ssh, because that could be overwritten if project/conf.env is
changed. Create another Host line with the same host name and add the
option you want.

### Test the tunnel

Do this after you have setup the DB, and you have tested connecting
locally.

In a terminal ssh to the remote system.

> ssh \$cgDbSshUser@\$cgDbHostRemote

Leave the teminal window open and start another terminal window. In the
new terminal window type:

> telnet 127.0.0.1 \$cgDbPortRemote

You should see \"Connected to 127.0.0.1\" and probably password prompt.
Exit with ctrl-C or ctrl-\] then \"quit\".

Now test the connection to the database:

> mysql -P \$cgDbPortRemote -u \$cgDbUser -p -h 127.0.0.1 \$cgDbName

If that doesn\'t work, look at the error message and see what needs to
be fixed. Check: db user name, db name, ports, grants and other settings
on the db system.

If that does work, try connecting with the \"bib\" command.

> bib connect

If that doesn\'t work look at the cgDsn variable setting in
project/conf.env. It should be set to \$cgRemoteDsn for remote access.
Try again, If that works, your conf.env setting are good for continuing.

------------------------------------------------------------------------

------------------------------------------------------------------------

Using libre-bib
===============

------------------------------------------------------------------------

Quick Start
-----------

This shows a minimal setup with a local DB. This will use the example
files and the minimal defalut configuration.

------------------------------------------------------------------------

A Full Example
--------------

This assumes you have everything installed and working. This will use
the example files.

``` {.in}
$ cd $HOME
$ mkdir -p project/biblio
$ cd project/biblio
$ bib 
```

``` {.out}
Usage:
    bib [-n] Cmd
Cmds:
    import-lo, export-lo, backup-lo, restore-lo
    import-lib, update-lo
    ref-new, ref-update
    status, setup-bib, clean, connect, version, help
```

``` {.in}
$ bib help
```

``` {.out}
Error: Missing conf.env, copying it now
Edit conf.env with your details. Uncomment the ones you are changing.
Then run: bib setup-bib
Usage:
        bib [-n] Cmd
Cmds:
    import-lo, export-lo, backup-lo, restore-lo
    import-lib, update-lo
    ref-new, ref-update
    status, setup-bib, clean, connect, version, help
```

``` {.in}
$ ls
```

``` {.out}
conf.env*
```

If you accidentally ran bib in a directory that is no going to be a
bibliography direcory, just delete the conf.env file.

``` {.in}
$ emacs conf.env
change:
    export cgDbHostRemote="NAME.example.com"
    export cgDbPassHint="b4n"
    export cgDbUser="$USER"
    export cgUseRemote=false
    export cgSshKey="$HOME/.ssh/id.KEY-NAME"
    export cgUseLib=false
to 
    export cgDbHostRemote="myserver.example.com"
    export cgDbPassHint="fav-pet"
    export cgDbUser="example"
    export cgUseRemote=true
    export cgSshKey="$HOME/.ssh/id.mysys"
    export cgUseLib=true
save, and exit

$ bib setup-bib
```

``` {.out}
Missing example.odt. Copy an example from
/opt/libre-bib/doc/example/example.odt
Missing: biblio.txt. Copy an example from
/opt/libre-bib/doc/example/biblio.txt
Missing librarything.tsv. Copy an example from
/opt/libre-bib/doc/example/librarything.tsv
Manually update it with an export from Library Thing.
```

``` {.in}
$ ls
```

``` {.out}
backup/          biblio.txt  conf.env~*   key.txt           status/
biblio-note.txt  conf.env*   example.odt  librarything.tsv  tmp/
```

``` {.in}
$ bib import-lo
```

``` {.out}
Problem with setup: SQLSTATE[HY000] [2002] Connection refused
make: *** [/opt/libre-bib/bin/Makefile:100: status/import-lo.date] Error 1
```

Open another terminal:

``` {.in}
$ ssh myserver
```

``` {.out}
Enter passphrase for key '/home/bob/.ssh/id.mysys': 

bob@mxlinux:/home/bob
$ 
```

Minimize the terminal window.

``` {.in}
$ bib import-lo
```

``` {.out}
Nothing was output. Edit conf.env and change cgVerbose to true.
```

``` {.in}
$ bib import-lo
```

``` {.out}
/opt/libre-bib/bin/import-txt-2-lo.php -c
Verbose is on.
Backup is on.
UseRemote is on.
UseLib is on.
Problem with setup: Missing: cgDbPassCache tmp/.pass.tmp. To set it, run: bib connect [89]
make: *** [/opt/libre-bib/bin/Makefile:100: status/import-lo.date] Error 1
```

``` {.in}
$ bib connect
```

``` {.out}
read -srp 'Password (fav-pet)? '; \
echo $REPLY >tmp/.pass.tmp
Password (fav-pet)? First define tunnel: ssh HOST.example.com
See: /home/bob/ssh/config
show databases; use DBNAME; show tables;

if [[ "true" == "true" ]]; then \
    tPort=3308; \
else \
    tPort=3306; \
fi; \
mysql -P $tPort -u example --password=$(cat tmp/.pass.tmp) -h 127.0.0.1 biblio_example
Welcome to the MariaDB monitor.  Commands end with ; or \g.
Your MariaDB connection id is 784
Server version: 10.5.18-MariaDB-0+deb11u1 Debian 11

Copyright (c) 2000, 2018, Oracle, MariaDB Corporation Ab and others.

Type 'help;' or '\h' for help. Type '\c' to clear the current input statement.

MariaDB [biblio_example]> quit
Bye
```

Clearly I need to cleanup the outputs.

``` {.in}
$ bib import-lo
```

``` {.out}
/opt/libre-bib/bin/import-txt-2-lo.php -c
Verbose is on.
Backup is on.
UseRemote is on.
UseLib is on.
.
Processed 292 lines. [263]
Inserted 31 records. [264]
/opt/libre-bib/bin/convert-lo-2-bib.php -c
Verbose is on.
Backup is on.
UseRemote is on.
UseLib is on.

Processed: 31 [221]
date +%F_%T >status/import-lo.date
```

This imported the biblio.txt file, creating the \"lo\" table. You can
run \"bib conect\" and use sql commands to look the table. For example:

``` {.in\"}
show tables;
show fields from table lo;
select Identifier,Booktitle from table lo;
```

Now let\'s import the export from LibraryThing.

``` {.in}
$ bib import-lib
```

``` {.out}
librarything schema and import
/opt/libre-bib/bin/import-tsv-2-lib-db.php -c
Verbose is on.
Backup is on.
UseRemote is on.
UseLib is on.
............
Processed: 12 
date +%F_%T >status/import-lib.date
head -n 1 librarything.tsv | sed 's/ /_/g' >tmp/lib-schema.tsv
diff /opt/libre-bib/etc/lib-schema.tsv tmp/lib-schema.tsv
Warning: If there are differences, there could be problems.
```

``` {.in}
$ bib update-lo
```

``` {.out}
Update lo from lib where Titles are similar, first 40 char
Run this after lib-db, lo-db
...................
Processed: 19
...........
Processed: 11
Created: bib_2023-05-17_01-40-14 

Processed: 31 [221]
```

This will have created a join table with Titles are in the \"lo\" and
\"lib\" tables. It then updated some empty \"lo\" fields from the
\"lib\" data. For example: Publisher is tricky one. (Enhancement:
Provide an option so some \"lib\" values will override the \'lo\'
values.)

``` {.in}
$ bib backup-lo
```

``` {.out}
cp: cannot stat 'backup/backup-lo.csv': No such file or directory
...............................
Processed: 31 
```

``` {.in}
$ ls backup/
```

``` {.out}
backup-lo.csv
```

``` {.in}
$ bib ref-new
```

``` {.out}
Unpack example.odt [319]
Start processing [292]

Processed 1056 lines. [303]
Found 2 references. [304]
Backup example.odt [339]
Final clean-up with tidy [343]
Repack example.odt [354]
Done. [386]
```

This updated the REF tags so they are now biblio entries. You\'ll also
see the original example.odt was copied to the backup/ dir.

Run: libreoffice to see how they have changed.

``` {.in}
$ libreoffice example.odt
```

If you run import-lo or import-lib with updated entrie, then run
ref-update to update them in the example.odt file. If you add new REFs
to the document then you wouild run ref-new again.

``` {.in}
$ bib ref-update
```

``` {.out}
Verbose is on.
Backup is on.
UseRemote is on.
UseLib is on.
Unpack example.odt [330]
Start processing [303]

Processed 1065 lines. [314]
Found 2 references. [315]
Backup example.odt [350]
Final clean-up with tidy [354]
Repack example.odt [365]
Done. [396]
```

Now you can add the Bibliography to the end of your document, and setup
the styles for the different Type of entries.

------------------------------------------------------------------------

libre-bib Tour
--------------

### Files and Dirs

This will be a quick summary of the direcories and files setup in your
project directory. The details will be describe in later sections as
they are used.

The bib commands will notice changes and rebuild any dependent files
they need. So you might see more things running than what you\'ve seen
before. The \"Env-Var\" column show the variable for the File-Dir. The
Cmd column shows the command or commands that create or use the
File-Dir.

  File or Dir                    Var / Cmd
  ------------------------------ --------------------------------------------
  conv.env                       Cmd: setuup-bib
  biblio.txt                     Var: \$cgLoFile; Cmd: setup-bib
  biblio-note.txt                Var: \$cgLoFile; Cmd: setup-bib
  key.txt                        Cmd: setup-bib
  example.odt                    Var: \$cgDocFile; Cmd: setup-bib
  librarything.tsv               Var: \$cgLibFile; Cmd: setup-bib
  status/                        Var: \$cgDirStatus; Cmd: setup-bib
  .... import-lo.date            Cmd: import-lo
  .... backup-lo.date            Cmd: backup-lo
  .... import-lib.date           Cmd: import-lib (from lib)
  .... update-lo.date            Cmd: update-lo (from lib)
  backup/                        Var: \$cgDirBackup; Cmd: setup-bib
  .... backup-lo.csv             Var; \$cgBackupFile; Cmd: backup-lo
  .... backup-lo.csv.bak         Cmd: backup-lo
  .... backup-lo.csv.bak.\~2\~   Cmd: backup-lo
  .... backup-lo.csv.bak.\~1\~   Cmd: backup-lo
  tmp/                           Var: \$cgDirTmp; Cmd: setup-bib
  .... .pass.tmp                 Var: \$cgDbPassCache; Cmd: connect
  .... biblio.txt                Var: \$cgDirTmp/\$cgLoFile; Cmd: export-lo

### DB Tables

  ----------------- --------------------------------------------
  biblio~example~   Var: \$cgDbName
  lo                Var: \$cgDbLo; Cmd: import-lo, export-lo
  lib               Var: \$cgDbLib; Cmd: import-lib, update-lo
  bib               Var: \$cgDbBib; Cmd: import-lo
  join~liblo~       Cmd: update-lo
  ----------------- --------------------------------------------

------------------------------------------------------------------------

------------------------------------------------------------------------

Var: \$cgLoFile - manage biblio.txt
===================================

biblio.txt and biblio-note.txt are the files you will be editing the
most. biblio.txt is where you will be putting most of the bibliographic
information about a book, article, web page, video, etc.

If you have setup a LibraryThing DB (see:
<https://www.librarything.com/home>) you can export a tsv file of your
LibraryThing DB to librarything.tsv. Then you can run \"bib update-lo\"
to update empty \"lo\" table fields from the \"lib\" DB table. See the
\"LibrayThing\" section for more details.

The key.txt file just gives some quick tip on the kind of values you can
put after the Tags. It isn\'t used anywhere else, so you can edit or
delete the file.

------------------------------------------------------------------------

Cmd: import-lo
--------------

Import any changes to \$cgLoFile (biblio.txt). The lo table will be
backed-up in the DB.

------------------------------------------------------------------------

Cmd: export-lo
--------------

This will probably only be needed if update-lo has been run after a new
librarything.tsv has been imported with import-lib.

This will output: \$cfDirTmp/\$cgLoFile (tmp/biblio.txt). Do a diff
between biblio.txt and tmp/biblio.txt to see if the new file looks OK.
If yes, then cp tmp/biblio.txt to biblio.txt.

------------------------------------------------------------------------

Cmd: backup-lo
--------------

Export the lo table to a cvs file.

------------------------------------------------------------------------

Cmd: update-lo
--------------

Run this if import-lo or import-lib have been run.

------------------------------------------------------------------------

------------------------------------------------------------------------

Var: \$cgLibFile - manage LibraryThing
======================================

Using LibraryThing export your DB to librarything.tsv file
<https://www.librarything.com/home>

------------------------------------------------------------------------

Cmd: import-lib
---------------

Import the librarything.tsv file to the lib table.

------------------------------------------------------------------------

------------------------------------------------------------------------

Var: \$cgDocFile - Updating your Libreoffice Write file
=======================================================

This it the whole reason for this app and hopefully this shows why you
went through the work of creating the biblio.txt file.

------------------------------------------------------------------------

Cmd: bib-new
------------

New biblio {REF} tags have been added to your odt file. Run this command
to update your odt file with the current biblio entries found in the lo
table. If there are no new entries, the file will be unchanged.

If the file is changed, the original file will be found in the bacckup/
dir. So your odt file can be restored if there are problems.

If the lo table has been updated with different values, then run the
bib-update command.

Internal: see /opt/libre-bib/etc/cite-new.xml for the template that will
be used.

This will format the entries with the \"Endnote Characters\" style, and
insert the non-empty bib-field values.

------------------------------------------------------------------------

Cmd: bib-update
---------------

If the lo table has been updated with different values, then run this
command to update the odt file with the new values. This command will
not modify any new {REF} tags.

The original file will be found in the bacckup/ dir. So your odt file
can be restored if there are problems. It could be there are no changes
to the file, but this command doesn\'t check for difference, it just
replaces all of the biblio-entries it finds in the odt file.

Internal: see /opt/libre-bib/etc/cite-update.xml for the template that
will be used.

This will only update non-empty bib-field values. The style won\'t be
touched.

------------------------------------------------------------------------

------------------------------------------------------------------------

Appendix
========

------------------------------------------------------------------------

Backups
-------

-   DB Tables: If a table exists and cgBackup is \"true\", then the
    table will be copied to the table name with a datestamp
    (~YYYY~-MM-DD~HH~-MM-SS) appended. For example, bib -\>
    bib~2023~-04-02~14~-18-37

-   Files: If a file exist and cgBackup is \"true\", then the file will
    be copied to FILE.bak. If the .bak file exist then a \".\~N\~\" will
    be appended after that (larger Ns are more recent).

-   Backup cleanup: run TBD????, it will prompt to confirm deletes of
    backup tables or files.

-   To restore a table. In mysql, follow this example:

    drop table \`bib\`; RENAME TABLE \`bib~2023~-04-02~14~-18-37\` TO
    bib;

------------------------------------------------------------------------

Build
-----

Use: \"make build\"

But first define cgBuild=true, so the sanity-check will be skipped.

------------------------------------------------------------------------

Maps
----

The best source for the maps can be found in bin/util.php.

### bib to libreoffice names

This has some minor differences when looking at the field in the
Bibliography style section.

### lo-file to lo-table

This maps the lo text file Tag names to the lo-table field names.

### lo-table to bib-table

Do some simple formatting of the lo-table values and put them in the
bib-table, so that the Bibliography style is easily setup.

### lib-table to lo-table

This maps the LibraryThing field names to the Libreoffice Bibliography
field names.
