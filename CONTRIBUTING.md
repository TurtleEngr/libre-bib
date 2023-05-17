- Mainly follow the naming convention and code formatting style.

- Create a branch with your changes. Do a pull request for a merge.

- I follow the git-flow style of managing branch, when others are involved.

- "main" is only for stable releases. Never commit directly to "main".

- "develop" is the primary branch for development changes. Start here,
  not "main".

## Some details

- format php code with phptidy.php. https://github.com/cmrcx/phptidy
  These are the default settings I changed:

        $indent_char             = "    ";
        $replace_shell_comments  = false;
        $add_operator_space      = true;
        $add_file_docblock       = false;
        $add_function_docblocks  = false;
        $add_doctags             = false;

- format bash code with shfmt. https://github.com/mvdan/sh/releases
  These are the options I use:
  
        shfmt -i 4 -ci

- bib commands are manage with the bin/Makefile.

- A lot of options are managed with the conf.env file. This reduces
  the need for a lot of script option processing.

- If there is a risk of data loss, back up the user's data. Either by
  cloning tables or copying files to the backup/ dir.
