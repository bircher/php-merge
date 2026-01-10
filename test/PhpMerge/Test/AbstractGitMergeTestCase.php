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
use PhpMerge\MergeConflict;
use PhpMerge\MergeException;
use PhpMerge\PhpMergeInterface;

/**
 * @group git-merge
 */
abstract class AbstractGitMergeTestCase extends AbstractPhpMergeTestCase
{

    /**
     * Test that the git directory is properly cleaned up.
     */
    public function testCleanup()
    {
        $merger = $this->createMerger();
        $getDir = \Closure::bind(function () {
            return $this->dir;
        }, $merger, GitMerge::class);

        $this->assertNull($getDir(), "No temporary file created.");

        $abc = $merger->merge("A\nb\nC", "A\nb\nc", "a\nb\nC");
        $this->assertEquals($abc, "a\nb\nc");
        $temp = (string) $getDir();
        unset($getDir);
        $this->assertTrue(is_dir($temp), "Temporary directory created.");

        unset($merger);
        $this->assertFalse(is_dir($temp), "Temporary directory cleaned up.");
    }
}
