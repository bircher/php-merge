# php-merge

[![Build Status](https://travis-ci.org/bircher/php-merge.svg?branch=master)](https://travis-ci.org/bircher/php-merge)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/e9399164-2b7d-4351-97ae-a600442d1e47/mini.png)](https://insight.sensiolabs.com/projects/e9399164-2b7d-4351-97ae-a600442d1e47)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/bircher/php-merge/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/bircher/php-merge/?branch=master)
[![Coverage Status](https://coveralls.io/repos/github/bircher/php-merge/badge.svg?branch=master)](https://coveralls.io/github/bircher/php-merge?branch=master)
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
// $result === $expected;

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
    // $original_lines === ["normal\n"];
    
    $version2_lines = $conflicts[0]->getRemote();
    // $version2_lines === ["normal??\n"];
    
    $conflicting_lines = $conflicts[0]->getLocal();
    // $conflicting_lines === ["normal!!\n"];
    
    $line_numer_of_conflict = $conflicts[0]->getBaseLine();
    // $line_numer_of_conflict === 3; // Count starts with 0.
    
    // It is also possible to get the merged version using the first version
    // to resolve conflicts.
    $merged = $exception->getMerged();
    // $merged === $version2;
    // In this case, but in general there could be non-conflicting changes.
    
    $line_in_merged = $conflicts[0]->getMergedLine();
    // $line_in_merged === 3; // Count starts with 0.
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
        "bircher/php-merge": "~3.0"
    }
}
```

To use the command line git with `GitMerge`:

```json
{
    "require": {
        "bircher/php-merge": "~3.0",
        "cpliakas/git-wrapper": "~2.0"
    }
}
```

Please refer to [Composer's documentation](https://github.com/composer/composer/blob/master/doc/00-intro.md#introduction)
for installation and usage instructions.


## Difference to ~2.0

In the ~3.0 version we updated sebastian/diff from "^1.3" to "~2.0|~3.0".
This update means that the lines contain the end-of-line character.
Consequently, we can treat conflicts at the end of the text the same way
git does and we can return the complete line in the merge conflicts.

If there are no conflicts the behaviour is not changed from ~1.0 and ~2.0.
Merge conflicts now contain the lines including the \n character.

The 2.1.0 release also has the updated version of sebastian/diff but
retains the 2.0.0 merge and merge conflict behaviour.
