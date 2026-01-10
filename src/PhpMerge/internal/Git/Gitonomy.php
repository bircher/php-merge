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

use Gitonomy\Git\Admin;
use Gitonomy\Git\Exception\RuntimeException;
use Gitonomy\Git\Repository;

/**
 * Our abstraction layer for the optional gitonomy/gitlib library.
 */
class Gitonomy implements GitAbstractionInterface
{
    protected ?Repository $repository;

    /**
     * {@inheritdoc}
     */
    public function init(string $directory): void
    {
        $this->repository = Admin::init($directory, false);

        $this->repository->run('config', ['user.name', 'GitMerge']);
        $this->repository->run('config', ['user.email', 'gitmerge@php-merge.example.com']);
        $this->repository->run('config', ['merge.conflictStyle', 'diff3']);
    }

    /**
     * {@inheritdoc}
     */
    public function add(string $path): void
    {
        $this->repository->run('add', [$path]);
    }

    /**
     * {@inheritdoc}
     */
    public function commit(string $message): void
    {
        $this->repository->run('commit', ['-m', $message]);
    }

    /**
     * {@inheritdoc}
     */
    public function branchExists(string $branch): bool
    {
        return $this->repository->getReferences(true)->hasBranch($branch);
    }

    /**
     * {@inheritdoc}
     */
    public function checkout(string $branch): void
    {
        $this->repository->getWorkingCopy()->checkout($branch);
    }

    /**
     * {@inheritdoc}
     */
    public function checkoutNewBranch(string $branch): void
    {
        $this->repository->getWorkingCopy()->checkout($this->repository->getHead(), $branch);
    }

    /**
     * {@inheritdoc}
     */
    public function rebase(string $branch): void
    {
        $this->repository->run('rebase', [$branch]);
    }

    /**
     * {@inheritdoc}
     */
    public function merge(string $branch): void
    {
        try {
            $this->repository->run('merge', [$branch]);
        } catch (RuntimeException $e) {
            throw new GitException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
