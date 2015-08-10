php-fastgit
===========

A read-only low level local Git repository access library that will have a clean API, low memory footprint and fast access times.

 - Zero dependencies
 - Uses native PHP zlib decompression
 - Requires at least PHP 5.4
 - **API currently unstable**

ISC licensed, see boilerplate.

There's very little error checking in the index and pack file handling code. It is expected the git repository is in good shape and no validation or verification is done.

Performance
-----------

On a single Core 2 Quad core clocked at 2.4GHz on top of an SSD/ext4 it can iterate through 100 000 commits in about 17 seconds from single pack file at initial commit.

For comparison, full `git log` with output on the same repository on the same system takes only around 2 seconds to complete.

YMMV and contributions welcome if you can optimize pack index reading which takes the most time.