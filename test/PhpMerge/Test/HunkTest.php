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

use PhpMerge\internal\Hunk;
use PhpMerge\internal\Line;
use PHPUnit\Framework\TestCase;

/**
 * Class HunkTest
 * @package PhpMerge\Test
 *
 * @group hunk
 */
class HunkTest extends TestCase
{


    public function testCreate() 
    {
        $lines = [
          new Line(Line::ADDED, 'added', -1),
          new Line(Line::UNCHANGED, 'unchanged', 0),
          new Line(Line::REMOVED, 'replaced', 1),
          new Line(Line::ADDED, 'replacement', 1),
          new Line(Line::UNCHANGED, 'unchanged', 2),
          new Line(Line::REMOVED, 'removed', 3),
        ];

        $expected = [
          new Hunk($lines[0], Hunk::ADDED, -1, -1),
          new Hunk([$lines[2], $lines[3]], Hunk::REPLACED, 1, 1),
          new Hunk($lines[5], Hunk::REMOVED, 3, 3),
        ];
        $result = Hunk::createArray($lines);

        $this->assertEquals($expected, $result);
        $this->assertEquals([$lines[2], $lines[3]], $result[1]->getLines());
        $this->assertEquals([$lines[2]], $result[1]->getRemovedLines());
        $this->assertEquals([$lines[3]], $result[1]->getAddedLines());
        $this->assertEquals(['replacement'], $result[1]->getLinesContent());
    }
}
