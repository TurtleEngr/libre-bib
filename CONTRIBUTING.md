
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
  style. It'll work fine for a small group. Fork the repository,
  create a feature branch, from the "develop" branch, with your
  changes. Then, do a pull request for a merge. See:
  [GitHub-flow](https://docs.github.com/en/get-started/quickstart/github-flow)

- "main" is *only* for stable releases. Never commit directly to "main".

- "develop" is the primary branch for development changes. Start there,
  not "main".

## Some details

- Mainly follow the naming convention and code formatting style that
  you see.

- The naming convention is CamelCase, with leading lower case letters
  that give clues about the "scope" of a variable.

  ```
  gpVar - global parameter (may be external to the script)
  gVar  - global variable (may be external to the script)
  cgVar - a global config constant (may be external to the script)
  cVar  - a local config constant
  pVar  - a function parameter (local)
  tVar  - temporary variable (usually local to a function)
  fFun  - function
  utilFun - a function in util.php (currently not used)
  ```

- Format php code with phptidy.php.
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

- Format bash code with
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
  
- bib commands are managed with bin/Makefile. For example: "bib
  connect" will call "make -f $cgBin/Makefile connect" All of conf.env
  values are available for use in the Makefile.  Using make keeps
  dependent files up-to-date. It is also easy to add
  commands. Chaining commands works too. Consider this:

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

- A lot of options are managed with the conf.env file. This reduces
  the need for a lot of script command line option processing.

- The sanity-check.sh script verifies values of the conf.env
  variables.  And is verifies the expected App and user files. This
  helps identify problem across the whole product, not just what is
  currently running.  This also eliminates most of the verification
  code that would have to be put in each script.

- If there is a risk of data loss, back up the user's data. Either by
  cloning tables or copying files to the backup/ dir.
