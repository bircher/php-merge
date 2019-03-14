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
 * Class MergeConflict
 *
 * This represents a merge conflict it includes the lines of the original and
 * both variations as well as the index on the original text where the conflict
 * starts.
 *
 * @package    PhpMerge
 * @author     Fabian Bircher <opensource@fabianbircher.com>
 * @copyright  Fabian Bircher <opensource@fabianbircher.com>
 * @license    https://opensource.org/licenses/MIT
 * @version    Release: @package_version@
 * @link       http://github.com/bircher/php-merge
 */
final class MergeConflict
{

    /**
     * The lines from the original.
     *
     * @var string[]
     */
    protected $base;

    /**
     * The conflicting line changes from the first source.
     *
     * @var string[]
     */
    protected $remote;

    /**
     * The conflicting line changes from the second source.
     *
     * @var string[]
     */
    protected $local;

    /**
     * The line number in the original text.
     *
     * @var int
     */
    protected $baseLine;

    /**
     * The line number in the merged text.
     *
     * @var int
     */
    protected $mergedLine;

    /**
     * MergeConflict constructor.
     *
     * @param string[] $base
     *   The original lines where the conflict happened.
     * @param string[] $remote
     *   The conflicting line changes from the first source.
     * @param string[] $local
     *   The conflicting line changes from the second source.
     * @param int $baseLine
     *   The line number in the original text.
     * @param int $mergedLine
     *   The line number in the merged text.
     */
    public function __construct($base, $remote, $local, $baseLine, $mergedLine)
    {
        // Compatibility for 2.x branch and sebastian/diff 2.x and 3.x.
        $this->base = static::fixEOL($base);
        $this->remote = static::fixEOL($remote);
        $this->local = static::fixEOL($local);
        $this->baseLine = $baseLine;
        $this->mergedLine = $mergedLine;
    }

    /**
     * Get the base text of the conflict.
     *
     * @return string[]
     *   The array of lines which are involved in the conflict.
     */
    public function getBase()
    {
        return $this->base;
    }

    /**
     * Get the lines from the first text.
     *
     * @return string[]
     *   The array of lines from the first text involved in the conflict.
     */
    public function getRemote()
    {
        return $this->remote;
    }

    /**
     * Get the lines from the second text.
     *
     * @return string[]
     *   The array of lines from the first text involved in the conflict.
     */
    public function getLocal()
    {
        return $this->local;
    }

    /**
     * Get the line number in the original text where the conflict starts.
     *
     * @return int
     *   The line number as in the original text.
     */
    public function getBaseLine()
    {
        return $this->baseLine;
    }

    /**
     * Get the line number in the merged text where the conflict starts.
     *
     * @return int
     *   The line number in the merged text.
     */
    public function getMergedLine()
    {
        return $this->mergedLine;
    }

    /**
     * Remove the trailing EOL that are now added by sebastian/diff.
     *
     * @param string[] $lines
     *   The lines to fix.
     *
     * @return string[]
     *   The fixed lines.
     */
    private static function fixEOL(array $lines)
    {
        $ltrim = function ($line) {
            return rtrim($line, "\n");
        };
        return array_map($ltrim, $lines);
    }
}
