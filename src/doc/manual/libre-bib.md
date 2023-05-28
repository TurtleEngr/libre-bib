------------------------------------------------------------------------

------------------------------------------------------------------------

Libre Bib Manual
================

------------------------------------------------------------------------

------------------------------------------------------------------------

Install and Setup
=================

------------------------------------------------------------------------

O.S. Requirments
----------------

-   A Linux system
    -   mx linux (21.x) - works
    -   Ubuntu (18.04) - in progress
    -   Ubuntu (20+) - not tested, will probably work
    -   Debian - not tested, will probably work
    -   RedHat - not tested, packages will be different
-   Windows with CygWin - not tested yet, packages will be different
-   MacOS with brew packages - not tested yet, packages will be
    different

``` {.in}
| OS Version         | Package | Tested      | Notes       |
|--------------------+---------+-------------+-------------|
| mx linux 21.x      | native  | in-progress |             |
| Ubuntu 18.04       | native  | planned     |             |
| Ubuntu 20+         | generic | no          | manual deps |
| Debian ??          | generic | no          | manual deps |
| RedHat ??          | generic | no          | manual deps |
| Windows ??, CygWin | generic | planned     | manual deps |
| MacOS ??, brew     | native  | planned     | manual deps |
```

------------------------------------------------------------------------

Install Package
---------------

-   If you install with a libre-bib.deb package with a package manager
    such as \"apt\", all of the required and most of the optional
    packages will be installed.

-   If you are installing from tgz file, then you\'ll need to install
    these manually.

### Required Packages

-   libreoffice (7.0)
-   libreoffice-sdbc-mysql (7.0) - needed for libreoffice DB connection
-   mariadb-client (10.5) - mysql
-   mariadb-server (10.5) - mariadbd (only needed on remote host)
-   php (7.4)
-   php-mysqlnd - php-PDO
-   perl (5.32) for: pod2html, pod2man, pod2text, pod2usage
-   bash (5.1 version probably not too important)
-   sed (4.7 version probably not important)
-   tidy (5.6 version probably not important)
-   make - for script and file management

### Optional Packages

-   pandoc - required to convert org to odt

-   libpod-markdown-perl - pod2markdown

-   pod2pdf

-   shfmt - get from: ???? or include in pkg (give credit)

-   phptidy.php - included src/bin (give credit)

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

Fix any errors then run it again, until no more errors.

If you are planing on using a remote DB, then see the \"Configure ssh\"
section.

------------------------------------------------------------------------

Configure the DB
----------------

### Install and test

The DB packages, mariadb-client and mariadb-server, have been installed
on the remote server (or local sever if you are doing this all on one
server). Most likely the mariadbd process will already be running.
Verify this with:

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

``` {.in}
sudo -s
mysql -P 3306 -u root -p
```

-   Create the DB

``` {.in}
create database $cgDbName;
show databases;
```

-   Create users

    The create user and grants are best done with the \'root\' DB user
    on the mysql system.

``` {.in}
create user 'admin'@'localhost' identified by 'ADMIN-PASS';
grant all privileges on *.* to 'admin'@localhost;

create user '$cgDbUser'@'localhost' identified by 'USER-PASS';
grant all privileges on $cgDbName.* to '$cgDbUser'@localhost;

flush privileges;

select user from mysql.user;
show grants for 'root'@localhost;
show grants for 'admin'@localhost;
show grants for '$cgDbUser'@localhost;

quit;
```

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

``` {.in}
| conf.env var   | Description                              |
|----------------+------------------------------------------|
| cgDbHost       | keep this set to the localhost IP        |
| cgDbName       | name of the mysql database               |
| cgDbPortRemote | remote port, on project's system.        |
| cgDbLocalPort  | port for mysql on the remote system      |
| cgDbUser       | DB user with grants to cgDbName          |
| cgDbPassHint   | hint for the password prompt             |
| cgDbSshUser    | user that can login to the remote system |
| cgDbSshKey     | key login to the remote system           |
```

Remove \~/ssh/libre-bib.ssh file and run again:

> bib setup-bib

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

Leave the terminal window open and start another terminal window. In the
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
files and the minimal default configuration.

For a detailed example see Appendix \"A Full Example.\"

``` {.in}
mkdir -p project/biblio
cd project/biblio
bib setup bib      # This creates your default conf.env file
edit conf.env      # Uncomment and set these values
    set cgDbName="YOUR-DB-NAME"
    set cgDbUser="YOUR-DB-USER"
    set cgDbPassHint="YOUR-HINT"
bib setup bib      # Your project are will be setup
bib connect        # Connect to DB to cache the  password
bib import-lo      # Import the biblio.txt file
bib ref-new        # A DB values for any new REFs
bib ref-update    # Update REFs with any DB changes
```

------------------------------------------------------------------------

libre-bib Tour
--------------

### Files and Dirs

This will be a quick summary of the directories and files setup in your
project directory. The details will be describe in later sections as
they are used.

The bib commands will notice changes and rebuild any dependent files
they need. So you might see more things running than what you\'ve seen
before. The \"Env-Var\" column show the variable for the File-Dir. The
Cmd column shows the command or commands that create or use the
File-Dir.

``` {.in}
$ cd $HOME
| File or Dir                | Var / Cmd                                |
|----------------------------+------------------------------------------|
| conv.env                   | Cmd: setuup-bib                          |
| biblio.txt                 | Var: $cgLoFile;  Cmd: setup-bib          |
| biblio-note.txt            | Var: $cgLoFile;  Cmd: setup-bib          |
| key.txt                    | Cmd: setup-bib                           |
| example.odt                | Var: $cgDocFile; Cmd: setup-bib          |
| librarything.tsv           | Var: $cgLibFile; Cmd: setup-bib          |
| status/                    | Var: $cgDirStatus; Cmd: setup-bib        |
| .... import-lo.date        | Cmd: import-lo                           |
| .... backup-lo.date        | Cmd: backup-lo                           |
| .... import-lib.date       | Cmd: import-lib (from lib)               |
| .... update-lo.date        | Cmd: update-lo (from lib)                |
| backup/                    | Var: $cgDirBackup;  Cmd: setup-bib       |
| .... backup-lo.csv         | Var; $cgBackupFile; Cmd: backup-lo       |
| .... backup-lo.csv.bak     | Cmd: backup-lo                           |
| .... backup-lo.csv.bak.~2~ | Cmd: backup-lo                           |
| .... backup-lo.csv.bak.~1~ | Cmd: backup-lo                           |
| tmp/                       | Var: $cgDirTmp; Cmd: setup-bib           |
| .... .pass.tmp             | Var: $cgDbPassCache; Cmd: connect        |
| .... biblio.txt            | Var: $cgDirTmp/$cgLoFile; Cmd: export-lo |
```

### DB Tables

``` {.in}
$ cd $HOME
| biblio_example | Var: $cgDbName                            |
| lo             | Var: $cgDbLo;  Cmd: import-lo,  export-lo |
| lib            | Var: $cgDbLib; Cmd: import-lib, update-lo |
| bib            | Var: $cgDbBib; Cmd: import-lo             |
| join_lib_lo    | Cmd: update-lo                            |
```

### Annotated conf.env

Understanding the variables in the conf.env file will probably give you
the best understanding of how the libre-bib applicaion works.

The conf.env files are the core configuration files for the libre-bib
app. They are executed in this order, so the last definition wins.

``` {.in}
$ cd $HOME
/opt/libre-bib/etc/conf.env
~/.config/libre-bib/conf.env ($cgDirConf)
$PWD/conf.env
```

File: ****/opt/libre-bib/doc/example/conf.env**** - Example document
config

This file is copied to \$PWD/conf.env when you first run bib.

File: ****/opt/libre-bib/etc/conf.env**** - System config

All the default values must be defined in this file. You can edit this
file to overide things for all your bib directories, but it would be
better to edit \~/.config/libre-bib/conf.env. That way the app can be
updated without overriding your changes.

File: ****\~/.config/libre-bib/conf.env**** - User config

This is optional, but it is useful for defining all of the common
settings across all of your bib directories. Copy \$PWD/conf.env to this
location and uncomment and change the values.

If you use the same cgDbName for all the bibs, then you\'ll want to
define different table name. Using different DB names is safer for
keeping the different bibs seperate, but more DB setup will be needed.

Typically these vars will be the same acorss all your bibs: cgDbName,
cgDbHost, cgDbPassCache, cgDbPassHint, cgDbUser, cgUseRemote
cgDbHostRemote, cgDbPortRemote, cgSshUser, cgSshKey

File: ****\$PWD/conf.env**** - Document config

This is required, but everything can be commented out. Uncomment the
ones that are specific to the current bib document.

Var: ****cgDebug=false****

If \"true\" then some diagnostic messages will be output.

FYI: true/false values can also be defind with: y/n, yes/no, t/f, or
1/0. Uppper case can letters can be used too.

Var: ****cgNoExec=false****

If \"true\" then things will be checked with non-destructive reads.
Execution will stopped before anything would be changed.

Note: this is not the same as the \"-n\" option. \"-n\" will show the
commnds that will be executed. cgNoExec forces the command to not make
any destructive changes. Files might be copied to backup locations, but
tables and files will not be changed.

Var: ****cgVerbose=true****

If \"true\" the commands being executed will be shown and there could be
more status output as things are run.

Note: Currently some errors messages are not output if this is set to
\"false\". If you see no output and no changes, the set this to \"true\"
and try again.

Var: ****cgDirBackup=\"backup\"****

This is the directory name (or path) where backup files are put. \"\~\"
numbers will be put after duplicate backups. With no \"/\" at the
beginning, the name will be relative to \$PWD.

Var: ****cgDirConf=\"\$HOME/.config/libre-bib\"****

Config files that are common for your user can be put here. If you have
multiple bib directories, then this will be useful. This should be an
absolute path.

Var: ****cgDirEtc=\"etc\"****

Templates and other doc related files are put here. Initially they are
copied from *opt/libre-bib/etc*. The files are copied to cgDirBackup if
a command would change any of the files.

Var:
****cgDirLibreofficeConf=\"\$HOME/.config/libreoffice/4/user/database/biblio\"****

Thls is the location of Libreoffice\'s bibliography DB connection
information.

Var: ****cgDirStatus=\"status\"****

When a command updates a file, a datestamped status file is created in
the cgDirStatus directory. If dependent file has a newer time than it\'s
correspoinding status file, then the update command will be run.

Deleting all the files in the cgDirStatus dir will force all of the
commands to run. That is, they will not check to see if things are
newer.

Var: ****cgDirTmp=\"tmp\"****

Temporary working files are put in this dir. This is usually relative to
\$PWD. If set to an absolute location, be sure there is space and that
it is unique across all users and bib processes that couild be run. For
example, do not define it to \"/tmp\" because when you run \"bib clean\"
that would remove all files and dirs in /tmp !

Var: ****cgBackupNum=10****

This variable defined the number of backup files or tables to be kept.
This can be set to 2 to 100.

Var: ****cgDbHost=\"127.0.0.1\"****

Usually this will always be set to the localhost IP. That works better
than using a name or localhost.

Var: ****cgDbName=\"biblio~example~\"****

This is the name of the database.

Var: ****cgDbUser=\"\$USER\"****

This is the name of your DB user. Typically it is the same as your login
user name, but you can used any name.

Var: ****cgDbPassHint=\"b4n\"****

This will be shown when you are prompted for the DB User\'s password.

Var: ****cgDbPassCache=\"\$cgDirTmp/.pass.tmp\"****

When you use commnds that need to connect to the DB you will be prompted
for the user\'s DB password. It will be saved here. It is not encrypted,
so don\'t use the DB User/Pass for sensitive DBs.

Var: ****cgDbPortLocal=\"3306\"****

This is the port for the DB, on the system where the DB is running.

Var: ****cgUseRemote=false****

If \"true\" then the remote DB will be accessed over a ssh tunnel. See
the ssh setup section for the details on setting up the tunnel.

Var: ****cgDbHostRemote=\"NAME.example.com\"****

If you are using a DB on another system, then define that system\'s name
here.

Var: ****cgDbPortRemote=\"3308\"****

This will be the port for the DB tunnel. It can be most any unused port
number.

Var: ****cgSshUser=\"\$USER\"****

This is your user name on the remote system.

Var: ****cgSshKey=\"\$HOME/.ssh/id.KEY-NAME\"****

This is the ssh key name for accessing the remote system. This will be
used to define the config file for setting up the ssh tunnel.

Var: ****cgDocFile=\"example.odt\"****

This it the whole reason for this app and hopefully this shows why you
went through the work of creating the biblio.txt file.

This is your Libreoffice document file that contains bibliographic
references. {REFs}

Var: ****cgLoFile=\"biblio.txt\"****

This is the text file you will use for adding and updating bibliographic
entries. This is much easier to manage and backup than using the DB for
everything.

biblio.txt and biblio-note.txt are the files you will be editing the
most. biblio.txt is where you will be putting most of the bibliographic
information about a book, article, web page, video, etc.

If you have setup a LibraryThing DB (see:
<https://www.librarything.com/home>) you can export a tsv file of your
LibraryThing DB to librarything.tsv. Then you can run \"bib update-lo\"
to update empty \"lo\" table fields from the \"lib\" DB table. See the
\"LibraryThing\" section for more details.

The key.txt file just gives some quick tip on the kind of values you can
put after the Tags. It isn\'t used anywhere else, so you can edit or
delete the file.

Var: ****cgDbLo=\"lo\"****

This is the name of the primary LibreOffice bibliographic DB table.

Var: ****cgDbBib=\"bib\"****

When the lo table is updated this table is created to do some simple
formatting, so the bibliography will not be cluttered with duplicate
commas when there are empty values.

Var: ****cgBackupFile=\"\$cgDirBackup/backup-lo.csv\"****

If you run the backup-lo command this is where the backup will be put.
If there is already one there, then that will be backed up.

Var: ****cgUseLib=false****

Set this to \"true\" if you will be using a Library Thing export.

Var: ****cgLibFile=\"librarything.tsv\"****

This is the name of the tsv (Tab Separated Value) file that was exported
from Libary Thing.

Using LibraryThing export your DB to librarything.tsv file
<https://www.librarything.com/home>

Var: ****cgDbLib=\"lib\"****

This is the name of the LibraryThing table that will be created from
cgLibFile.

### Commands

------------------------------------------------------------------------

Cmd: setup bib
--------------

------------------------------------------------------------------------

Cmd: connect
------------

------------------------------------------------------------------------

Cmd: check
----------

------------------------------------------------------------------------

Cmd: import-lo
--------------

Import any changes to \$cgLoFile (biblio.txt). The lo table will be
backed-up in the DB.

------------------------------------------------------------------------

Cmd: export-lo
--------------

------------------------------------------------------------------------

Cmd: backup-lo
--------------

------------------------------------------------------------------------

Cmd: import-lib
---------------

Import the librarything.tsv file to the lib table.

------------------------------------------------------------------------

Cmd: update-lo
--------------

------------------------------------------------------------------------

Cmd: ref-new
------------

New biblio {REF} tags have been added to your odt file. Run this command
to update your odt file with the current biblio entries found in the lo
table. If there are no new entries, the file will be unchanged.

If the file is changed, the original file will be found in the backup/
dir. So your odt file can be restored if there are problems.

If the lo table has been updated with different values, then run the
bib-update command.

Internal: see /opt/libre-bib/etc/cite-new.xml for the template that will
be used.

This will format the entries with the \"Endnote Characters\" style, and
insert the non-empty bib-field values.

------------------------------------------------------------------------

Cmd: ref-update
---------------

If the lo table has been updated with different values, then run this
command to update the odt file with the new values. This command will
not modify any new {REF} tags.

The original file will be found in the backup/ dir. So your odt file can
be restored if there are problems. It could be there are no changes to
the file, but this command doesn\'t check for difference, it just
replaces all of the biblio-entries it finds in the odt file.

Internal: see /opt/libre-bib/etc/cite-update.xml for the template that
will be used.

This will only update non-empty bib-field values. The style won\'t be
touched.

------------------------------------------------------------------------

Cmd: save-style
---------------

------------------------------------------------------------------------

Cmd: update-style
-----------------

------------------------------------------------------------------------

Cmd: status
-----------

------------------------------------------------------------------------

Cmd: clean
----------

------------------------------------------------------------------------

Cmd: version
------------

------------------------------------------------------------------------

Cmd: add, edit
--------------

------------------------------------------------------------------------

Cmd: help
---------

------------------------------------------------------------------------

------------------------------------------------------------------------

Appendix
========

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
bibliography directory, just delete the conf.env file.

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
Problem with setup: Missing: cgDbPassCache tmp/.pass.tmp. To set it,
run: bib connect [89]
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
mysql -P $tPort -u example --password=$(cat tmp/.pass.tmp)
    -h 127.0.0.1 biblio_example
Welcome to the MariaDB monitor.  Commands end with ; or \g.
Your MariaDB connection id is 784
Server version: 10.5.18-MariaDB-0+deb11u1 Debian 11

Copyright (c) 2000, 2018, Oracle, MariaDB Corporation Ab and others.

Type 'help;' or '\h' for help. Type '\c' to clear the current input statement.

MariaDB [biblio_example]> quit
Bye
```

(Clearly I need to cleanup the outputs.)

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
run \"bib connect\" and use sql commands to look the table. For example:

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

If you run import-lo or import-lib with updated entries, then run
ref-update to update them in the example.odt file. If you add new REFs
to the document then you would run ref-new again.

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

Customizing the defaults
------------------------

If you are managing multiple bibliographies, you might have some common
settings. For example, most of the things related to a remote DB will be
the same. You can change the application\'s etc/conf.env default file.
You can even add your own variables. Here are the steps.

``` {.in}
cd /opt/libre-bib/etc
edit conf.env
bash -n conf.env   # syntax check
cd BIB-PROJECT     # any of your bib project dirs
bib rebuild        # update user default file, and conf.php
```

Source /opt/libre-bib/etc/conf.env and conf.env in a bash script call
your own Makefile, other bash scripts, or php scripts to run things.
Your php scripts could include /opt/libre-bib/etc/conf.php to define the
ENV vars as globals, or just use \$~ENV~\[\'cgVarName\'\].

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
