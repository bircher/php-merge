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


use PhpMerge\internal\Line;
use SebastianBergmann\Diff\Differ;

/**
 * Class LineTest
 * @package PhpMerge\Test
 */
class LineTest extends \PHPUnit_Framework_TestCase
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
        ['added', 1],
        ['unchanged', 0],
        ['replaced', 2],
        ['replacement', 1],
        ['unchanged', 0],
        ['removed', 2]
        ];

        $lines = [
        new Line(Line::ADDED, 'added', -1),
        new Line(Line::UNCHANGED, 'unchanged', 0),
        new Line(Line::REMOVED, 'replaced', 1),
        new Line(Line::ADDED, 'replacement', 1),
        new Line(Line::UNCHANGED, 'unchanged', 2),
        new Line(Line::REMOVED, 'removed', 3),
        ];

        $differ = new Differ();
        $array_diff = $differ->diffToArray($before, $after);
        $this->assertEquals($diff, $array_diff);

        $result = Line::createArray($diff);
        $this->assertEquals($lines, $result);

        try {
            $diff[] = ['invalid', 3];
            Line::createArray($diff);
            $this->assertTrue(false, 'An exception was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertEquals('Unsupported diff line type.', $e->getMessage());
        }
    }
}
