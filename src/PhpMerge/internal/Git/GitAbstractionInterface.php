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

/**
 * The interface of git commands we need.
 *
 * This is an internal implementation detail so that we can more easily
 * support different libraries wrapping around the git executable.
 */
interface GitAbstractionInterface
{
    /**
     * Sets up the git repository in the given directory.
     *
     * This method is also responsible for setting the git config.
     * For example the merge.conflictStyle needs to be set to diff3.
     *
     * @param string $directory
     *   The empty directory to set up git in.
     */
    public function init(string $directory): void;

    /**
     * Add a path to the stage in git.
     *
     * @param string $path
     *   The path to add.
     */
    public function add(string $path): void;

    /**
     * Commit the changes to git.
     *
     * @param string $message
     *   The commit message
     */
    public function commit(string $message): void;


    /**
     * Checks if a branch exists.
     *
     * @param string $branch
     *   The branch name.
     * @return bool
     *   True if it exists.
     */
    public function branchExists(string $branch): bool;

    /**
     * Checkout a given branch.
     *
     * @param string $branch
     */
    public function checkout(string $branch): void;

    /**
     * Checkout a new branch with a given name.
     *
     * @param string $branch
     */
    public function checkoutNewBranch(string $branch): void;

    /**
     * Rebase onto a branch.
     *
     * @param string $branch
     *   The branch to rebase onto.
     */
    public function rebase(string $branch): void;


    /**
     * Merge a branch.
     *
     * @param string $branch
     *   The branch to merge.
     *
     * @throws GitException
     *   Thrown when there is a merge conflict.
     */
    public function merge(string $branch): void;
}
