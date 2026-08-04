#!/bin/bash

# Usage:
#     sort-para.sh <FILE.in >FILE.out
#
# The -00 option enables "paragraph mode" which splits input on empty
# lines.  The last input line should be a blank line.

perl -n00 -e '
     push @a, $_;
     END { print sort @a }
'
