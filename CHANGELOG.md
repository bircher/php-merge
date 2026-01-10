# Changelog

## 4.2.0 - 2026-01-10

 * Refactor git wrapper to add gitonomy/gitlib and use it by default if it is available.

## 4.1.0 - 2026-01-04

 * Updated sebastian/diff dependency to "^2.0|^3.0|^4.0|^5.0|^6.0|^7.0"
 * Testing infrastructure for php versions: '7.4', '8.0', '8.1', '8.2', '8.3', '8.4', '8.5'

## 4.0.0 - 2021-12-27

 * Updated sebastian/diff dependency to "^2.0|^3.0|^4.0"
 * Switched git-wrapper dependency from cpliakas/git-wrapper to symplify/git-wrapper
 * Added testing infrastructure for php 8

## 3.0.0 - 2019-03-15

 * Updated sebastian/diff dependency to "~2.0|~3.0"
 * Changed behaviour of merge conflicts, lines now contain the \n character.
 * Merge conflicts at the end of the text now behave as git naturally does.

## 2.1.0 - 2019-03-15

 * Updated sebastian/diff dependency to "~2.0|~3.0"

## 2.0.0 - 2017-07-11

 * Removed php5 support.
 * Cleaned up api surface, php7 type hints etc.

## 1.0.0 - 2017-03-17

 * Tagged stable release.
 * Removed xdiff merge.
 
## 0.0.0 - 2015-10-28

 * Initial release.
 * Includes php merge, git merge and xdiff merge. 
