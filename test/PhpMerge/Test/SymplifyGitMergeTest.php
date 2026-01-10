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

namespace PhpMerge\Test;

use PhpMerge\GitMerge;
use PhpMerge\internal\Git\Symplify;
use PhpMerge\MergeConflict;
use PhpMerge\MergeException;
use PhpMerge\PhpMergeInterface;
use Symplify\GitWrapper\GitWrapper;

/**
 * @group git-merge
 */
class SymplifyGitMergeTest extends AbstractGitMergeTestCase
{

    /**
     * {@inheritdoc}
     */
    protected function createMerger() : PhpMergeInterface
    {
        return new GitMerge(new Symplify(new GitWrapper('git')));
    }
}
