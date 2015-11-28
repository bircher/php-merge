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
 * both variations as wel as the index on the original text where the conflict
 * starts.
 *
 * @package    PhpMerge
 * @author     Fabian Bircher <opensource@fabianbircher.com>
 * @copyright  Fabian Bircher <opensource@fabianbircher.com>
 * @license    https://opensource.org/licenses/MIT
 * @version    Release: @package_version@
 * @link       http://github.com/bircher/php-merge
 */
class MergeConflict
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
     * @param int $line
     *   The line number in the original text.
     * @param int $merged
     *   The line number in the merged text.
     */
    public function __construct($base, $remote, $local, $baseLine, $mergedLine)
    {
        $this->base = $base;
        $this->remote = $remote;
        $this->local = $local;
        $this->baseLine = $baseLine;
        $this->mergedLine = $mergedLine;
    }

    /**
     * @return string[]
     */
    public function getBase()
    {
        return $this->base;
    }

    /**
     * @return string[]
     */
    public function getRemote()
    {
        return $this->remote;
    }

    /**
     * @return string[]
     */
    public function getLocal()
    {
        return $this->local;
    }

    /**
     * @return int
     */
    public function getBaseLine()
    {
        return $this->baseLine;
    }

    /**
     * @return int
     */
    public function getMergedLine()
    {
        return $this->mergedLine;
    }

}