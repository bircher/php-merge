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

use PhpMerge\internal\Line;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\Diff\Differ;

/**
 * Class LineTest
 * @package PhpMerge\Test
 */
class LineTest extends TestCase
{

    public function testCreate()
    {

        $before = <<<'EOD'
unchanged
replaced
unchanged
removed

EOD;
        $after = <<<'EOD'
added
unchanged
replacement
unchanged

EOD;

        $diff = [
        ["added\n", 1],
        ["unchanged\n", 0],
        ["replaced\n", 2],
        ["replacement\n", 1],
        ["unchanged\n", 0],
        ["removed\n", 2],
        ];

        $lines = [
        new Line(Line::ADDED, "added\n", -1),
        new Line(Line::UNCHANGED, "unchanged\n", 0),
        new Line(Line::REMOVED, "replaced\n", 1),
        new Line(Line::ADDED, "replacement\n", 1),
        new Line(Line::UNCHANGED, "unchanged\n", 2),
        new Line(Line::REMOVED, "removed\n", 3),
        ];

        $differ = new Differ();
        $array_diff = $differ->diffToArray($before, $after);
        $this->assertEquals($diff, $array_diff);

        $result = Line::createArray($diff);
        $this->assertEquals($lines, $result);

        try {
            $diff[] = ["invalid", 3];
            Line::createArray($diff);
            $this->assertTrue(false, 'An exception was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertEquals('Unsupported diff line type.', $e->getMessage());
        }
    }
}
