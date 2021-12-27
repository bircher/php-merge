<?php
/**
 * This file is part of the php-merge package.
 *
 * (c) Fabian Bircher <opensource@fabianbircher.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PhpMerge;

/**
 * Class MergeException
 *
 * A merge exception thrown when a merge conflict occurs, catch it to get both
 * the array of conflicts and a merged version of the text when the conflicts
 * have been resolved by using the first variation.
 *
 * @package    PhpMerge
 * @author     Fabian Bircher <opensource@fabianbircher.com>
 * @copyright  Fabian Bircher <opensource@fabianbircher.com>
 * @license    https://opensource.org/licenses/MITe
 * @version    Release: @package_version@
 * @link       http://github.com/bircher/php-merge
 */
final class MergeException extends \RuntimeException
{

    /**
     * @var \PhpMerge\MergeConflict[]
     */
    protected $conflicts;

    /**
     * @var string
     */
    protected $merged;

    /**
     * MergeException constructor.
     *
     * @param string $message
     *   The Exception message to throw.
     * @param \PhpMerge\MergeConflict[] $conflicts
     *   The array of merge conflicts that occured.
     * @param string $merged
     *   The merged string using remote to resolve the conflicts.
     * @param int $code
     *   The Exception code.
     * @param \Exception $previous
     *   The previous exception used for the exception chaining.
     */
    public function __construct($message = "", $conflicts = [], $merged = null, $code = 0, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->conflicts = $conflicts;
        $this->merged = $merged;
    }

    /**
     * Get the merge conflicts associated with the exception.
     *
     * @return \PhpMerge\MergeConflict[]
     *   All the conflicts that happened during the merge.
     */
    public function getConflicts()
    {
        return $this->conflicts;
    }

    /**
     * Get the merged text if the first text is used to resolve the conflict.
     *
     * @return string
     *   The merged text.
     */
    public function getMerged() : string
    {
        return $this->merged;
    }
}
