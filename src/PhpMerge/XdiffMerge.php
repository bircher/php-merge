<?php
/**
 * This file is part of the php-merge package.
 *
 * (c) Fabian Bircher <opensource@fabianbircher.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpMerge;


/**
 * Class XdiffMerge merges three strings with the xdiff functions.
 *
 * This implementation uses the functions provided by the xdiff php extension.
 * While it does not seem to be installed on a big variety of servers it is
 * much more performant for simple merges. However the merge algorithm is only
 * compatible with the one of git for simple cases, if that fails it falls back
 * to the algorithm used by PhpMerge.
 *
 * @package    PhpMerge
 * @author     Fabian Bircher <opensource@fabianbircher.com>
 * @copyright  Fabian Bircher <opensource@fabianbircher.com>
 * @license    https://opensource.org/licenses/MIT
 * @version    Release: @package_version@
 * @link       http://github.com/bircher/php-merge
 */
class XdiffMerge extends PhpMerge
{


    /**
     * @inheritDoc
     */
    public function merge($base, $remote, $local)
    {
        // Skip merging if there is nothing to do.
        if ($merged = PhpMergeBase::simpleMerge($base, $remote, $local)) {
            return $merged;
        }

        // First try the built in xdiff_string_merge3.
        $merged = xdiff_string_merge3($base, $remote, $local, $error);

        if ($error) {
            // So there might be a merge conflict.
            $baseLines = $this->base = Line::createArray(
                array_map(
                    function ($l) {
                        return [$l, 0];
                    },
                    explode("\n", $base)
                )
            );
            // Get patch strings and transform them to Hunks.
            $remotePatch = xdiff_string_diff($base, $remote, 0);
            $localPatch = xdiff_string_diff($base, $local, 0);
            // Note that patching $remote with $localPatch will work only in
            // some cases because xdiff patches differently than git and will
            // apply the same change twice.
            $remote_hunks = self::interpretDiff($remotePatch);
            $local_hunks = self::interpretDiff($localPatch);

            // Merge Hunks and detect conflicts the same way PhpMerge does.
            $conflicts = [];
            $merged = PhpMerge::mergeHunks($baseLines, $remote_hunks, $local_hunks, $conflicts);
            if ($conflicts) {
                throw new MergeException('A merge conflict has occured.', $conflicts, $merged);
            }
        }
        return $merged;
    }

    /**
     * Interpret a unified diff from xdiff_string_diff()
     *
     * @param string $diff
     *   The diff string with no extra contextual lines.
     *
     * @return Hunk[]
     *   The hunks extracted from
     */
    protected static function interpretDiff($diff)
    {
        $i = new \ArrayIterator(explode("\n", $diff));
        $hunks = [];
        while ($i->valid()) {
            preg_match('/^@@ \-(\d),(\d) \+(\d),(\d) @@$/', $i->current(), $matches);
            if (isset($matches[0]) && $matches[0] == $i->current()) {
                // Our line numbers start from 0.
                $matches[1]--;
                $removed = [];
                for ($j = $matches[1]; $j < $matches[1] + $matches[2]; $j++) {
                    $i->next();
                    $line = substr($i->current(), 1);
                    $removed[] = new Line(Line::REMOVED, $line, $j);
                }
                $added = [];
                // The hunks expect the line numbers to correspond to the base.
                for ($j = $matches[1]; $j < $matches[1] + $matches[4]; $j++) {
                    $i->next();
                    while (substr($i->current(), 0, 1) != '+') {
                        // skip over lines that don't start with a "+"
                        // For example xdiff complaining about no-new lines.
                        $i->next();
                    }
                    $line = substr($i->current(), 1);
                    $added[] = new Line(Line::ADDED, $line, $matches[1]);
                }

                if (!$removed) {
                    $hunks[] = new Hunk($added, Hunk::ADDED, $matches[1], $matches[1]);
                } elseif (!$added) {
                    $hunks[] = new Hunk($removed, Hunk::REMOVED, $matches[1], $matches[1] + $matches[2] -1);
                } else {
                    $combined = array_merge($removed, $added);
                    $hunks[] = new Hunk($combined, Hunk::REPLACED, $matches[1], $matches[1] + $matches[2] -1);
                }
            }
            $i->next();
        }
        return $hunks;
    }
}
