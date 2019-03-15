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

use PhpMerge\internal\Line;
use PhpMerge\internal\Hunk;
use PhpMerge\internal\PhpMergeBase;
use SebastianBergmann\Diff\Differ;

/**
 * Class PhpMerge merges three texts by lines.
 *
 * The merge class which in most cases will work, the diff is calculated using
 * an instance of \SebastianBergmann\Diff\Differ. The merge algorithm goes
 * through all the lines and decides which to line to use.
 *
 * @package    PhpMerge
 * @author     Fabian Bircher <opensource@fabianbircher.com>
 * @copyright  Fabian Bircher <opensource@fabianbircher.com>
 * @license    https://opensource.org/licenses/MIT
 * @version    Release: @package_version@
 * @link       http://github.com/bircher/php-merge
 */
final class PhpMerge extends PhpMergeBase implements PhpMergeInterface
{

    /**
     * The differ used to create the diffs.
     *
     * @var \SebastianBergmann\Diff\Differ
     */
    protected $differ;

    /**
     * PhpMerge constructor.
     */
    public function __construct(Differ $differ = null)
    {
        if (!$differ) {
            $differ = new Differ();
        }
        $this->differ = $differ;
    }


    /**
     * {@inheritdoc}
     */
    public function merge(string $base, string $remote, string $local) : string
    {
        // Skip merging if there is nothing to do.
        if ($merged = PhpMergeBase::simpleMerge($base, $remote, $local)) {
            return $merged;
        }

        // Compatibility for 2.x branch and sebastian/diff 2.x and 3.x.
        $base = self::preMergeAlter($base);
        $remote = self::preMergeAlter($remote);
        $local = self::preMergeAlter($local);

        $remoteDiff = Line::createArray($this->differ->diffToArray($base, $remote));
        $localDiff = Line::createArray($this->differ->diffToArray($base, $local));

        $baseLines = Line::createArray(
            array_map(
                function ($l) {
                    return [$l, 0];
                },
                self::splitStringByLines($base)
            )
        );

        $remoteHunks = Hunk::createArray($remoteDiff);
        $localHunks = Hunk::createArray($localDiff);

        $conflicts = [];
        $merged = PhpMerge::mergeHunks($baseLines, $remoteHunks, $localHunks, $conflicts);
        $merged = implode("", $merged);
        $merged = self::postMergeAlter($merged);

        if (!empty($conflicts)) {
            throw new MergeException('A merge conflict has occurred.', $conflicts, $merged);
        }

        return $merged;
    }

    /**
     * The merge algorithm.
     *
     * @param Line[] $base
     *   The lines of the original text.
     * @param Hunk[] $remote
     *   The hunks of the remote changes.
     * @param Hunk[] $local
     *   The hunks of the local changes.
     * @param MergeConflict[] $conflicts
     *   The merge conflicts.
     *
     * @return string[]
     *   The merged text.
     */
    protected static function mergeHunks(array $base, array $remote, array $local, array &$conflicts = []) : array
    {
        $remote = new \ArrayObject($remote);
        $local = new \ArrayObject($local);

        $merged = [];

        $a = $remote->getIterator();
        $b = $local->getIterator();
        $flipped = false;
        $i = -1;

        // Loop over all indexes of the base and all hunks.
        while ($i < count($base) || $a->valid() || $b->valid()) {
            // Assure that $aa is the first hunk by swaping $a and $b
            if ($a->valid() && $b->valid() && $a->current()->getStart() > $b->current()->getStart()) {
                self::swap($a, $b, $flipped);
            } elseif (!$a->valid() && $b->valid()) {
                self::swap($a, $b, $flipped);
            }
            /** @var Hunk $aa */
            $aa = $a->current();
            /** @var Hunk $bb */
            $bb = $b->current();

            if ($aa) {
                assert($aa->getStart() >= $i, 'The start of the hunk is after the current index.');
            }
            // The hunk starts at the current index.
            if ($aa && $aa->getStart() == $i) {
                // Hunks from both sources start with the same index.
                if ($bb && $bb->getStart() == $i) {
                    if ($aa != $bb) {
                        // If the hunks are not the same its a conflict.
                        $conflicts[] = self::prepareConflict($base, $a, $b, $flipped, count($merged));
                        $aa = $a->current();
                    } else {
                        // Advance $b it is the same as $a and will be merged.
                        $b->next();
                    }
                } elseif ($aa->hasIntersection($bb)) {
                    // The end overlaps with the start of the next other hunk.
                    $conflicts[] = self::prepareConflict($base, $a, $b, $flipped, count($merged));
                    $aa = $a->current();
                }
            }
            // The conflict resolution could mean the hunk starts now later.
            if ($aa && $aa->getStart() == $i) {
                if ($aa->getType() == Hunk::ADDED && $i >= 0) {
                    $merged[] = $base[$i]->getContent();
                }

                if ($aa->getType() != Hunk::REMOVED) {
                    foreach ($aa->getAddedLines() as $line) {
                        $merged[] = $line->getContent();
                    }
                }
                $i = $aa->getEnd();
                $a->next();
            } else {
                // Not dealing with a change, so return the line from the base.
                if ($i >= 0) {
                    $merged[] = $base[$i]->getContent();
                }
            }
            // Finally, advance the index.
            $i++;
        }
        return $merged;
    }

    /**
     * Get a Merge conflict from the two array iterators.
     *
     * @param Line[] $base
     *   The original lines of the base text.
     * @param \ArrayIterator $a
     *   The first hunk iterator.
     * @param \ArrayIterator $b
     *   The second hunk iterator.
     * @param bool $flipped
     *   Whether or not the a corresponds to remote and b to local.
     * @param int $mergedLine
     *   The line on which the merge conflict appears on the merged result.
     *
     * @return MergeConflict
     *   The merge conflict.
     */
    protected static function prepareConflict($base, &$a, &$b, &$flipped, $mergedLine)
    {
        if ($flipped) {
            self::swap($a, $b, $flipped);
        }
        /** @var Hunk $aa */
        $aa = $a->current();
        /** @var Hunk $bb */
        $bb = $b->current();

        // If one of the hunks is added but the other one does not start there.
        if ($aa->getType() == Hunk::ADDED && $bb->getType() != Hunk::ADDED) {
            $start = $bb->getStart();
            $end = $bb->getEnd();
        } elseif ($aa->getType() != Hunk::ADDED && $bb->getType() == Hunk::ADDED) {
            $start = $aa->getStart();
            $end = $aa->getEnd();
        } else {
            $start = min($aa->getStart(), $bb->getStart());
            $end = max($aa->getEnd(), $bb->getEnd());
        }
        // Add one to the merged line number if we advanced the start.
        $mergedLine += $start - min($aa->getStart(), $bb->getStart());

        $baseLines = [];
        $remoteLines = [];
        $localLines = [];
        if ($aa->getType() != Hunk::ADDED || $bb->getType() != Hunk::ADDED) {
            // If the start is after the start of the hunk, include it first.
            if ($aa->getStart() < $start) {
                $remoteLines = $aa->getLinesContent();
            }
            if ($bb->getStart() < $start) {
                $localLines = $bb->getLinesContent();
            }
            for ($i = $start; $i <= $end; $i++) {
                $baseLines[] = $base[$i]->getContent();
                // For conflicts that happened on overlapping lines.
                if ($i < $aa->getStart() || $i > $aa->getEnd()) {
                    $remoteLines[] = $base[$i]->getContent();
                } elseif ($i == $aa->getStart()) {
                    if ($aa->getType() == Hunk::ADDED) {
                        $remoteLines[] = $base[$i]->getContent();
                    }
                    $remoteLines = array_merge($remoteLines, $aa->getLinesContent());
                }
                if ($i < $bb->getStart() || $i > $bb->getEnd()) {
                    $localLines[] = $base[$i]->getContent();
                } elseif ($i == $bb->getStart()) {
                    if ($bb->getType() == Hunk::ADDED) {
                        $localLines[] = $base[$i]->getContent();
                    }
                    $localLines = array_merge($localLines, $bb->getLinesContent());
                }
            }
        } else {
            $remoteLines = $aa->getLinesContent();
            $localLines = $bb->getLinesContent();
        }

        $b->next();
        return new MergeConflict($baseLines, $remoteLines, $localLines, $start, $mergedLine);
    }

    /**
     * Swaps two variables.
     *
     * @param mixed $a
     *   The first variable which will become the second.
     * @param mixed $b
     *   The second variable which will become the first.
     * @param bool $flipped
     *   The boolean indicator which will change its value.
     */
    protected static function swap(&$a, &$b, &$flipped)
    {
        $c = $a;
        $a = $b;
        $b = $c;
        $flipped = !$flipped;
    }
}
