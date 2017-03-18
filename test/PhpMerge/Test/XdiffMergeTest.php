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

use PhpMerge\XdiffMerge;

/**
 * @group xdiff-merge
 */
class XdiffMergeTest extends AbstractPhpMergeTest
{
    /**
     * {@inheritdoc}
     */
    protected function createMerger()
    {
        if (function_exists('xdiff_string_merge3')) {
            return new XdiffMerge();
        } else {
            $this->markTestSkipped('The xdiff php extension is not installed.');
            return NULL;
        }
    }

    public function testDuplicatedLines()
    {
        $this->markTestSkipped('The xdiff merge does not complain about this.');
    }

    public function testNewLines()
    {
        $this->markTestSkipped('The xdiff merge does not complain about this.');
    }

    public function testAddReplace()
    {
        $this->markTestSkipped('The xdiff merge does not complain about this.');
    }

}