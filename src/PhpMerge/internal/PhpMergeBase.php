<?php
/**
 * This file is part of the php-merge package.
 *
 * (c) Fabian Bircher <opensource@fabianbircher.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpMerge\internal;

use PhpMerge\PhpMergeInterface;

/**
 * Class PhpMergeBase
 *
 * The base class implementing only the simplest logic which is common to all
 * implementations.
 *
 * @package    PhpMerge
 * @author     Fabian Bircher <opensource@fabianbircher.com>
 * @copyright  Fabian Bircher <opensource@fabianbircher.com>
 * @license    https://opensource.org/licenses/MIT
 * @version    Release: @package_version@
 * @link       http://github.com/bircher/php-merge
 * @internal   This class is not part of the public api.
 */
abstract class PhpMergeBase implements PhpMergeInterface
{

    /**
     * Merge obvious cases when only one text changes..
     *
     * @param string $base
     *   The original text.
     * @param string $remote
     *   The first variant text.
     * @param string $local
     *   The second variant text.
     *
     * @return string|null
     *   The merge result or null if the merge is not obvious.
     */
    protected static function simpleMerge(string $base, string $remote, string $local)
    {
        // Skip complex merging if there is nothing to do.
        if ($base == $remote) {
            return $local;
        }
        if ($base == $local) {
            return $remote;
        }
        if ($remote == $local) {
            return $remote;
        }
        // Return nothing and let sub-classes deal with it.
        return null;
    }

    /**
     * Split it line-by-line.
     *
     * @param string $input
     *
     * @return array
     */
    protected static function splitStringByLines(string $input): array
    {
        return \preg_split('/(.*\R)/', $input, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
    }

    /**
     * @param $text
     * @return string
     */
    protected static function preMergeAlter($text) : string
    {
        // Append new lines so that conflicts at the end of the text work.
        return $text . "\nthe\nend";
    }

    /**
     * @param $text
     * @return bool|string
     */
    protected static function postMergeAlter($text) : string
    {
        // Remove the appended lines.
        return (string) substr($text, 0, -8);
    }
}
