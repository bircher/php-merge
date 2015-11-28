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

use PhpMerge\PhpMerge;

/**
 * @group php-merge
 */
class PhpMergeTest extends AbstractPhpMergeTest
{
    /**
     *
     */
    public function setUp()
    {
        parent::setUp(new PhpMerge());
    }

}