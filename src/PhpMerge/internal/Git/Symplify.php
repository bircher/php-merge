<?php
/**
 * This file is part of the php-merge package.
 *
 * (c) Fabian Bircher <opensource@fabianbircher.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpMerge\internal\Git;

use Symplify\GitWrapper\GitWorkingCopy;
use Symplify\GitWrapper\GitWrapper;
use Symplify\GitWrapper\Exception\GitException as SymplifyGitException;

/**
 * Our abstraction layer for the optional symplify/git-wrapper library.
 */
class Symplify implements GitAbstractionInterface
{

    /**
     * The git working directory.
     *
     * @var \Symplify\GitWrapper\GitWorkingCopy|null
     */
    protected ?GitWorkingCopy $git = null;

    /**
     * The git wrapper to use for merging.
     *
     * @var \Symplify\GitWrapper\GitWrapper
     */
    protected GitWrapper $wrapper;

    /**
     * The constructor
     *
     * @param GitWrapper $gitWrapper
     *   The git wrapper to use.
     */
    public function __construct(GitWrapper $gitWrapper)
    {
        $this->wrapper = $gitWrapper;
    }
    /**
     * {@inheritdoc}
     */
    public function init(string $directory): void
    {
        $this->git = $this->wrapper->init($directory);
        $this->git->config('user.name', 'GitMerge');
        $this->git->config('user.email', 'gitmerge@php-merge.example.com');
        $this->git->config('merge.conflictStyle', 'diff3');
    }

    /**
     * {@inheritdoc}
     */
    public function add(string $path): void
    {
        $this->git->add($path);
    }

    /**
     * {@inheritdoc}
     */
    public function commit(string $message): void
    {
        $this->git->commit($message);
    }

    /**
     * {@inheritdoc}
     */
    public function branchExists(string $branch): bool
    {
        return in_array($branch, $this->git->getBranches()->all());
    }

    /**
     * {@inheritdoc}
     */
    public function checkout(string $branch): void
    {
        $this->git->checkout($branch);
    }

    /**
     * {@inheritdoc}
     */
    public function checkoutNewBranch(string $branch): void
    {
        $this->git->checkoutNewBranch($branch);
    }

    /**
     * {@inheritdoc}
     */
    public function rebase(string $branch): void
    {
        $this->git->rebase($branch);
    }

    /**
     * {@inheritdoc}
     */
    public function merge(string $branch): void
    {
        try {
            $this->git->merge($branch);
        } catch (SymplifyGitException $e) {
            throw new GitException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
