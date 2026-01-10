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

namespace PhpMerge\internal\Git;

use RuntimeException;

/**
 * The exception thrown when a merge conflict happens.
 */
final class GitException extends RuntimeException
{
}
