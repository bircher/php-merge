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
 * Interface PhpMergeInterface.
 *
 * The interface implemented by the different mergers.
 */
interface PhpMergeInterface
{

    /**
     * Merge texts.
     *
     * @param string $base
     *   The original text.
     * @param string $remote
     *   The first variant text.
     * @param string $local
     *   The second variant text.
     *
     * @return string
     *   The merge result.
     *
     * @throws MergeException
     *   Thrown when there is a merge conflict.
     */
    public function merge(string $base, string $remote, string $local) : string;
}
