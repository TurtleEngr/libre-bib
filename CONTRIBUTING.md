# Contributing

## Branches

- In past SW teams I've used
  [GitFlow](https://datasift.github.io/gitflow/IntroducingGitFlow.html)
  for managing branches and releases. (See the git-flow package.) This
  version control style will keep everyone sane, when there are large
  teams (>12), with multiple feature streams, that have muliple steps
  in the development, QA, and deployment process.

  When it is just me, I commit directly to the develop branch. When
  the code is "stable," I merge "develop" to "main", then increment
  the version on "develop". If I make branches, I still use the
  git-flow tool.

- If others want to contribute, I'll try to follow the"Github-flow"
  style. It'll work fine for a small group. You: fork the repository,
  create a feature branch, from the "develop" branch, with your
  changes. Then, do a pull request for a merge. See:
  [GitHub-flow](https://docs.github.com/en/get-started/quickstart/github-flow)

- "main" is *only* for stable releases. I never commit directly to "main".

- "develop" is the primary branch for development changes. Start there,
  not "main".

## Pull-Requests

- For pull requests use the [pull request
  template](.github/pull_request_template.md)

* Include an issue number in your pull request.  If you don't have an
  issue, then go back and create one, giving details. In other-words
  the commit messages are not enough--the issues help track the "why"
  behind changes.

* You have setup your build env **make build-setup**

* You have run **make install**. For now that installs to
  /opt/libre-bib/ and "make check" is done against that. In the future
  this will install to test/.

* You have followed the Coding Convention.

* You have made a "feature" branch from develop and that is what I'll
  pull in, check, then merge to develop.

## Coding Convention

- Mainly follow the naming convention and code formatting style that
  you see.

- The **naming convention** is CamelCase, with leading lower case letters
  that give clues about the "scope" of a variable.

  ```
  gpVar - global parameter (could be external to the script)
  cgVar - a global config constant (could be external to the script)
  gVar  - global variable (within current file)
  cVar  - a local config constant (within current file)
  pVar  - a function parameter (local)
  tVar  - temporary variable (usually local to a function)
  fFun  - function in the current file
  uFun  - a function in util.php
  ```

- **Format php code** with phptidy.php.
  [phptidy](https://github.com/cmrcx/phptidy) These are the default
  settings I changed (See bin/.phptidy-config.php)

  ```
  $diff = "diff";
  $indent_char             = "    ";  # 4 spaces
  $replace_shell_comments  = false;
  $add_operator_space      = true;
  $add_file_docblock       = false;
  $add_function_docblocks  = false;
  $add_doctags             = false;
  ```

  Use:

  ```
  cd libre-bib
  tBin=$PWD/build/bin
  cd src/bin
  $tBin/phptidy.php replace *.php
  ```

- **Format bash code** with
  shfmt. [shfmt}(https://github.com/mvdan/sh/releases) These are the
  options I use:

  ```
  cd libre-bib
  tBin=$PWD/build/bin
  cd src/bin
  for i in $(shfmt -l -i 4 -ci .); do
      if ! bash -n $i; then exit 1; fi
      $tBin/shfmt -i 4 -ci -w $i;
  done
  ```

- Run **make build-setup** to setup pre-commit hook and other things for
  building the tool.

- **pre-commit checks:**
  - File names can only use letters, numbers, hypen, dash, and periods.
  - File names cannot begin with hyphens or end with periods.
  - File names cannot be all periods.
  - These file names are not allowed: CON|PRN|AUX|NUL|COM[1-9]|LPT[1-9]
  - No trailing spaces in files.
  - No TABs in most files. (Makefile and bib-cmd.mak are exceptions)
  - "Large" binary files are not allowed.

- Run **make check** before commiting code.

- The **bib commands are managed with bin/bib-cmd.mak (a Makefile)**
  For example: "bib connect" will call "make -f $cgBin/bib-cmd.mak
  connect" All of conf.env values are available for use in
  bib-cmd.mak.  Using make keeps dependent files up-to-date. It is
  also easy to add commands. Chaining commands works too. Consider
  this:

  ```
  cd PROJECT/
  bib connect
  bib import-lo import-lib backup-lo update-lo ref-new ref-update
  ```

  That will execute all the commands in order, but if there is an
  error in one, the processing will stop. No need to write all the
  code to manage that! Just be consistent with return errors and
  ignore the ones that don't matter. (For example, ignore the error
  from removing a file that does not exist.)

- A lot of **options** are managed with the **conf.env file.** This reduces
  the need for a lot of script command line option processing.

- The **sanity-check.sh script** verifies values of the conf.env
  variables.  And is verifies the expected App and user files. This
  helps identify problem across the whole product, not just what is
  currently running.  This also eliminates most of the verification
  code that would have to be put in each script.

- If there is a risk of data loss, **back up the user's data.** Either by
  cloning tables or copying files to the backup/ dir.
