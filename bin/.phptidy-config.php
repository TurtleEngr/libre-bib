<?php

// List of files in your project
// Wildcards for glob() may be used.
// Example: array("*.php", "inc/*.php");
$project_files = array();

// List of files you want to exclude from the project files
// Wildcards are not allowed here.
// Example: array("inc/external_lib.php");
$project_files_excludes = array();

// diff command
// Examples: "diff", "colordiff", "diff -u", "colordiff -u"
$diff = "diff";

// The automatically added author in the phpdoc file docblocks
// If left empty no new @author doctags will be added.
// Example: "Your Name <you@example.com>"
$default_author = "";

// Name of the automatically added @package doctag in the phpdoc file docblocks
// Example: "myproject"
$default_package = "default";

// String used for indenting
// If you indent with spaces you can use as much spaces as you like.
// Useful values: "\t" for indenting with tabs,
//                "  " for indenting with two spaces
$indent_char = "    ";

// Control structures with the opening curly brace on a new line
// Examples: false                      always on the same line
//           true                       always on a new line
//           array(T_CLASS, T_FUNCTION) for PEAR Coding Standards
$curly_brace_newline = false;

// PHP open tag
// All php open tags will be replaced by the here defined kind of open tag.
// Useful values: "<?", "<?php", "<?PHP"
$open_tag = "<?php";

// Keep short open echo tags even when using long open tags
// Values: false  if long open tags are used, convert <?= to open tag and echo
//         true   always leave <?= untouched
$keep_open_echo_tags = false;

// Check encoding
// If left empty the encoding will not be checked.
// See http://php.net/manual/en/ref.mbstring.html for a list of supported
// encodings.
// Examples: "ASCII", "UTF-8", "ISO-8859-1"
$encoding = "";

// Docroot-Variables
// phptidy will strip these variables and constants from the beginning of
// include and require commands to generate appropriate @see tags also for
// these files.
// Example: array('DOCROOT', '$docroot');
$docrootvars = array();

// Enable the single cleanup functions
$fix_token_case             = true;
$fix_builtin_functions_case = true;
$replace_inline_tabs        = true;
$replace_phptags            = true;
$replace_shell_comments     = false;
$fix_statement_brackets     = true;
$fix_separation_whitespace  = true;
$fix_comma_space            = true;
$add_operator_space         = true;
$fix_round_bracket_space    = false;
$add_file_docblock          = false;
$add_function_docblocks     = false;
$add_doctags                = false;
$add_usestags               = false;
$fix_docblock_format        = true;
$fix_docblock_space         = true;
$add_blank_lines            = true;
$indent                     = true;
