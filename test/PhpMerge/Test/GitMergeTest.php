<?php
/**
 * This file is part of the php-merge package.
 *
 * (c) Fabian Bircher <opensource@fabianbircher.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpMerge\Test;

use PhpMerge\GitMerge;
use PhpMerge\MergeConflict;
use PhpMerge\MergeException;
use PhpMerge\PhpMergeInterface;

/**
 * @group git-merge
 */
class GitMergeTest extends AbstractPhpMergeTest
{

    /**
     * {@inheritdoc}
     */
    protected function createMerger() : PhpMergeInterface
    {
        return new GitMerge();
    }

    /**
     * Test that the git directory is properly cleaned up.
     */
    public function testCleanup()
    {
        $merger = new GitMerge();
        $class = new \ReflectionClass('PhpMerge\GitMerge');
        $dir = $class->getProperty("dir");
        $dir->setAccessible(true);
        $this->assertNull($dir->getValue($merger), "No temporary file created.");

        $abc = $merger->merge("A\nb\nC", "A\nb\nc", "a\nb\nC");
        $this->assertEquals($abc, "a\nb\nc");
        $temp_dir = $dir->getValue($merger);
        $this->assertTrue(is_dir($temp_dir), "Temporary directory created.");

        unset($merger);
        $this->assertFalse(is_dir($temp_dir), "Temporary directory cleaned up.");
    }
}
