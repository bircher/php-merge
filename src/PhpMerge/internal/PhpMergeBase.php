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
}
