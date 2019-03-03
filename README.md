# php-merge

[![Build Status](https://travis-ci.org/bircher/php-merge.svg?branch=master)](https://travis-ci.org/bircher/php-merge)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/e9399164-2b7d-4351-97ae-a600442d1e47/mini.png)](https://insight.sensiolabs.com/projects/e9399164-2b7d-4351-97ae-a600442d1e47)
[![GitHub license](https://img.shields.io/badge/license-MIT-blue.svg)](https://raw.githubusercontent.com/bircher/php-merge/master/LICENSE.txt)

## Introduction

When working with revisions of text one sometimes faces the problem that there
are several revisions based off the same original text. Rather than choosing
one and discarding the other we want to merge the two revisions.

Git does that already wonderfully. In a php application we want a simple tool
that does the same. There is the [xdiff PECL extension](http://php.net/manual/en/book.xdiff.php)
which has the [xdiff_string_merge3](http://php.net/manual/en/function.xdiff-string-merge3.php)
function. But `xdiff_string_merge3` does not behave the same way as git and
xdiff may not be available on your system.

PhpMerge is a small library that solves this problem. There are two classes:
`\PhpMerge\PhpMerge` and `\PhpMerge\GitMerge` that implement the 
`\PhpMerge\PhpMergeInterface` which has just a `merge` method.

`PhpMerge` uses `SebastianBergmann\Diff\Differ` to get the differences between
the different versions and calculates the merged text from it.
`GitMerge` uses `GitWrapper\GitWrapper`, writes the text to a temporary file
and uses the command line git to merge the text. 

## Usage

Simple example:

```php
use PhpMerge\PhpMerge;

// Create a merger instance.
$merger = new PhpMerge();

// Get the texts to merge.
$original = <<<'EOD'
unchanged
replaced
unchanged
normal
unchanged
unchanged
removed
EOD;
$version1= <<<'EOD'
added
unchanged
replacement
unchanged
normal
unchanged
unchanged
EOD;
$version2 = <<<'EOD'
unchanged
replaced
unchanged
normal??
unchanged
unchanged
EOD;
$expected = <<<'EOD'
added
unchanged
replacement
unchanged
normal??
unchanged
unchanged
EOD;

$result = $merger->merge($original, $version1, $version2);
// $result == $expected;

```

With merge conflicts:

```php
// Continuing from before with:
use Phpmerge\MergeException;
use PhpMerge\MergeConflict;


$conflicting = <<<'EOD'
unchanged
replaced
unchanged
normal!!
unchanged
unchanged
EOD;

try {
    $merger->merge($original, $version2, $conflicting);
} catch (MergeException $exception) {
    /** @var MergeConflict[] $conflicts */
    $conflicts = $exception->getConflicts();

    $original_lines = $conflicts[0]->getBase();
    // $original_lines == ['normal'];
    
    $version2_lines = $conflicts[0]->getRemote();
    // $version2_lines == ['normal??'];
    
    $conflicting_lines = $conflicts[0]->getLocal();
    // $conflicting_lines == ['normal!!'];
    
    $line_numer_of_conflict = $conflicts[0]->getBaseLine();
    // $line_numer_of_conflict == 3; // Count starts with 0.
    
    // It is also possible to get the merged version using the first version
    // to resolve conflicts.
    $merged = $exception->getMerged();
    // $merged == $version2;
    // In this case, but in general there could be non-conflicting changes.
    
    $line_in_merged = $conflicts[0]->getMergedLine();
    // $line_in_merged == 3; // Count starts with 0.
}

```

Using the command line git to perform the merge:

```php
use PhpMerge\GitMerge;

$merger = new GitMerge();

// Use as the previous example.
```


## Installation

PhpMerge can be installed with [Composer](http://getcomposer.org) by adding
the library as a dependency to your composer.json file.

```json
{
    "require": {
        "bircher/php-merge": "~2.0"
    }
}
```

To use the command line git with `GitMerge`:

```json
{
    "require": {
        "bircher/php-merge": "~2.0",
        "cpliakas/git-wrapper": "~1.0"
    }
}
```

Please refer to [Composer's documentation](https://github.com/composer/composer/blob/master/doc/00-intro.md#introduction)
for installation and usage instructions.


## Difference to ~1.0

In the ~2.0 version we dropped support for php 5 and use php 7 constructs
instead. This means that the `PhpMergeInterface` type-hints the arguments and
return type as strings. In addition to that all classes are now final and it
is clearer what the API is. We can consider making the classes inheritable if
needed without breaking the api but not the other way around.

If you have just been using the ~1.0 version as described in this document
the version ~2.0 will continue to work.
