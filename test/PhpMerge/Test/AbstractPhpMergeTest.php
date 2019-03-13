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

use PhpMerge\MergeConflict;
use PhpMerge\MergeException;
use PhpMerge\PhpMergeInterface;
use PHPUnit\Framework\TestCase;

abstract class AbstractPhpMergeTest extends TestCase
{
    /**
     * Merger class.
     *
     * @var \PhpMerge\PhpMergeInterface
     */
    protected $merger;

    /**
     * Set up the merger to use.
     *
     * @return \PhpMerge\PhpMergeInterface
     *   The merger used in the subsequent tests.
     */
    abstract protected function createMerger() : PhpMergeInterface;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->merger = $this->createMerger();
    }

    /**
     * Test no change.
     */
    public function testUnity()
    {
        $text = self::split("12345");
        $result = $this->merger->merge($text, $text, $text);
        $this->assertEquals($text, $result);
    }

    /**
     * Test changing both texts the same way.
     */
    public function testSameChange()
    {
        $base = self::split("12345");
        $text = self::split("123456");
        $result = $this->merger->merge($base, $text, $text);
        $this->assertEquals($text, $result);

        $base = self::split("12345");
        $text = self::split("abc");
        $result = $this->merger->merge($base, $text, $text);
        $this->assertEquals($text, $result);

        $base = "123";
        $text = "abc";
        $result = $this->merger->merge($base, $text, $text);
        $this->assertEquals($text, $result);
    }

    /**
     * Test changing only one text.
     */
    public function testSingleChange()
    {
        $base = self::split("12345");
        $text = self::split("123456");
        $result = $this->merger->merge($base, $base, $text);
        $this->assertEquals($text, $result);
        $result = $this->merger->merge($base, $text, $base);
        $this->assertEquals($text, $result);

        $base = self::split("12345");
        $text = self::split("123abcde");
        $result = $this->merger->merge($base, $base, $text);
        $this->assertEquals($text, $result);
        $result = $this->merger->merge($base, $text, $base);
        $this->assertEquals($text, $result);

        $base = "123";
        $text = "abc";
        $result = $this->merger->merge($base, $base, $text);
        $this->assertEquals($text, $result);
        $result = $this->merger->merge($base, $text, $base);
        $this->assertEquals($text, $result);
    }

    /**
     *
     */
    public function testSimpleMerge()
    {
        $base     = self::split("12345");
        $remote   = self::split("A12345");
        $local    = self::split("12B4");
        $expected = self::split("A12B4");
        $result = $this->merger->merge($base, $remote, $local);
        $this->assertEquals($expected, $result);
        $result = $this->merger->merge($base, $local, $remote);
        $this->assertEquals($expected, $result);

        $base     = self::split("12345");
        $remote   = self::split("A234A");
        $local    = self::split("12B45");
        $expected = self::split("A2B4A");
        $result = $this->merger->merge($base, $remote, $local);
        $this->assertEquals($expected, $result);
        $result = $this->merger->merge($base, $local, $remote);
        $this->assertEquals($expected, $result);
    }

    /**
     * Test adding several lines.
     */
    public function testAppend()
    {
        $base     = self::split("12345");
        $remote   = self::split("AA2BB45");
        $local    = self::split("12BB45C");
        $expected = self::split("AA2BB45C");
        $result = $this->merger->merge($base, $remote, $local);
        $this->assertEquals($expected, $result);
        $result = $this->merger->merge($base, $local, $remote);
        $this->assertEquals($expected, $result);

        $base     = self::split("1234567");
        $remote   = self::split("AA12346");
        $local    = self::split("12BB46");
        $expected = self::split("AA12BB46");
        $result = $this->merger->merge($base, $remote, $local);
        $this->assertEquals($expected, $result);
        $result = $this->merger->merge($base, $local, $remote);
        $this->assertEquals($expected, $result);
    }

    /**
     * Test different conflict scenarios.
     */
    public function testConflict()
    {
        $base     = self::split("12345");
        $remote   = self::split("A2C45");
        $local    = self::split("12B4C");
        try {
            $this->merger->merge($base, $remote, $local);
            $this->assertTrue(false, "Merge Exception not thrown.");
        } catch (MergeException $e) {
            $conflicts = [
              new MergeConflict(["3\n"], ["C\n"], ["B\n"], 2, 2),
            ];
            $this->assertEquals($conflicts, $e->getConflicts());
            $this->assertEquals(self::split("A2C4C"), $e->getMerged());
        }
        try {
            $this->merger->merge($base, $local, $remote);
            $this->assertTrue(false, "Merge Exception not thrown.");
        } catch (MergeException $e) {
            $conflicts = [
              new MergeConflict(["3\n"], ["B\n"], ["C\n"], 2, 2),
            ];
            $this->assertEquals($conflicts, $e->getConflicts());
            $this->assertEquals(self::split("A2B4C"), $e->getMerged());
        }

        $base     = self::split("12345");
        $remote   = self::split("345");
        $local    = self::split("1BB45");
        try {
            $this->merger->merge($base, $remote, $local);
            $this->assertTrue(false, "Merge Exception not thrown.");
        } catch (MergeException $e) {
            $conflicts = [
              new MergeConflict(["1\n", "2\n", "3\n"], ["3\n"], ["1\n", "B\n", "B\n"], 0, 0),
            ];
            $this->assertEquals($conflicts, $e->getConflicts());
            $this->assertEquals($remote, $e->getMerged());
        }

        try {
            $this->merger->merge($base, $local, $remote);
            $this->assertTrue(false, "Merge Exception not thrown.");
        } catch (MergeException $e) {
            $conflicts = [
              new MergeConflict(["1\n", "2\n", "3\n"], ["1\n", "B\n", "B\n"], ["3\n"], 0, 0),
            ];
            $this->assertEquals($conflicts, $e->getConflicts());
            $this->assertEquals($local, $e->getMerged());
        }

        $base     = self::split("012345");
        $remote   = self::split("01456");
        $local    = self::split("01BBB45!6");
        try {
            $this->merger->merge($base, $remote, $local);
            $this->assertTrue(false, "Merge Exception not thrown.");
        } catch (MergeException $e) {
            $conflicts = [
              new MergeConflict(["2\n", "3\n"], [], ["B\n", "B\n", "B\n"], 2, 2),
              new MergeConflict([], ["6\n"], ["!\n", "6\n"], 5, 3),
            ];
            $this->assertEquals($conflicts, $e->getConflicts());
            $this->assertEquals($remote, $e->getMerged());
        }

        try {
            $this->merger->merge($base, $local, $remote);
            $this->assertTrue(false, "Merge Exception not thrown.");
        } catch (MergeException $e) {
            $conflicts = [
              new MergeConflict(["2\n", "3\n"], ["B\n", "B\n", "B\n"], [], 2, 2),
              new MergeConflict([], ["!\n", "6\n"], ["6\n"], 5, 6),
            ];
            $this->assertEquals($conflicts, $e->getConflicts());
            $this->assertEquals($local, $e->getMerged());
        }

        $base     = self::split("1234567890");
        $remote   = self::split("A345678A0");
        $local    = self::split("1234B678B0");
        try {
            $this->merger->merge($base, $remote, $local);
            $this->assertTrue(false, "Merge Exception not thrown.");
        } catch (MergeException $e) {
            $conflicts = [
              new MergeConflict(["9\n"], ["A\n"], ["B\n"], 8, 7),
            ];
            $this->assertEquals($conflicts, $e->getConflicts());
            $this->assertEquals(self::split("A34B678A0"), $e->getMerged());
        }

        try {
            $this->merger->merge($base, $local, $remote);
            $this->assertTrue(false, "Merge Exception not thrown.");
        } catch (MergeException $e) {
            $conflicts = $e->getConflicts();
            $this->assertEquals(["9\n"], $conflicts[0]->getBase());
            $this->assertEquals(["B\n"], $conflicts[0]->getRemote());
            $this->assertEquals(["A\n"], $conflicts[0]->getLocal());
            $this->assertEquals(8, $conflicts[0]->getBaseLine());
            $this->assertEquals(7, $conflicts[0]->getMergedLine());
            $this->assertEquals(self::split("A34B678B0"), $e->getMerged());
        }

        $base     = self::split("1234567890");
        $remote   = self::split("aaa34A678C");
        $local    = self::split("B3456B8C");
        try {
            $this->merger->merge($base, $remote, $local);
            $this->assertTrue(false, "Merge Exception not thrown.");
        } catch (MergeException $e) {
            $conflicts = [
              new MergeConflict(["1\n", "2\n"], ["a\n", "a\n", "a\n"], ["B\n"], 0, 0),
            ];
            $this->assertEquals($conflicts, $e->getConflicts());
            $this->assertEquals(self::split("aaa34A6B8C"), $e->getMerged());
        }
    }

    /**
     * @group double-line
     */
    public function testDuplicatedLines()
    {
        $base     = self::split("AaaaB", 0);
        $remote   = self::split("AaaB", 0);
        $local    = self::split("AaaaaB", 0);

        try {
            $this->merger->merge($base, $remote, $local);
            $this->assertTrue(false, "Merge Exception not thrown.");
        } catch (MergeException $e) {
            $conflicts = [
              new MergeConflict(["a\n"], [], ["a\n", "a\n"], 3, 3),
            ];
            $this->assertEquals($conflicts, $e->getConflicts());
            $this->assertEquals($remote, $e->getMerged());
        }

        try {
            $this->merger->merge($base, $local, $remote);
            $this->assertTrue(false, "Merge Exception not thrown.");
        } catch (MergeException $e) {
            $conflicts = [
              new MergeConflict(["a\n"], ["a\n", "a\n"], [], 3, 3),
            ];
            $this->assertEquals($conflicts, $e->getConflicts());
            $this->assertEquals($local, $e->getMerged());
        }
    }

    /**
     * @group new-line
     */
    public function testNewLines()
    {

        $base     = self::split("0123", 0);
        $remote   = self::split("0123A", 0);
        $local    = self::split("0123B", 0);

        try {
            $this->merger->merge($base, $remote, $local);
            $this->assertTrue(false, "Merge Exception not thrown.");
        } catch (MergeException $e) {
            $conflicts = [
              new MergeConflict(["3"], ["3\n", "A"], ["3\n", "B"], 3, 3),
            ];
            $this->assertEquals($conflicts, $e->getConflicts());
            $this->assertEquals($remote, $e->getMerged());
        }

        try {
            $this->merger->merge($base, $local, $remote);
            $this->assertTrue(false, "Merge Exception not thrown.");
        } catch (MergeException $e) {
            $conflicts = [
              new MergeConflict(["3"], ["3\n", "B"], ["3\n", "A"], 3, 3),
            ];
            $this->assertEquals($conflicts, $e->getConflicts());
            $this->assertEquals($local, $e->getMerged());
        }


        $base     = self::split("0123", 2);
        $remote   = self::split("0123", 1);
        $local    = self::split("0123", 3);

        try {
            $this->merger->merge($base, $remote, $local);
            $this->assertTrue(false, "Merge Exception not thrown.");
        } catch (MergeException $e) {
            $conflicts = [
              new MergeConflict(["\n"], [], ["\n", "\n"], 4, 4),
            ];
            $this->assertEquals($conflicts, $e->getConflicts());
            $this->assertEquals($remote, $e->getMerged());
        }

        try {
            $this->merger->merge($base, $local, $remote);
            $this->assertTrue(false, "Merge Exception not thrown.");
        } catch (MergeException $e) {
            $conflicts = [
              new MergeConflict(["\n"], ["\n", "\n"], [], 4, 4),
            ];
            $this->assertEquals($conflicts, $e->getConflicts());
            $this->assertEquals($local, $e->getMerged());
        }
    }

    /**
     * Test adding a line in one text just in front of changing it in the other.
     */
    public function testAddReplace()
    {
        $base     = self::split("012345");
        $remote   = self::split("A0123A45");
        $local    = self::split("B0123B5");

        try {
            $this->merger->merge($base, $remote, $local);
            $this->assertTrue(false, "Merge Exception not thrown.");
        } catch (MergeException $e) {
            $conflicts = [
              new MergeConflict([], ["A\n"], ["B\n"], -1, 0),
              new MergeConflict(["4\n"], ["A\n", "4\n"], ["B\n"], 4, 5),
            ];
            $this->assertEquals($conflicts, $e->getConflicts());
            $this->assertEquals($remote, $e->getMerged());
        }

        try {
            $this->merger->merge($base, $local, $remote);
            $this->assertTrue(false, "Merge Exception not thrown.");
        } catch (MergeException $e) {
            $conflicts = [
              new MergeConflict([], ["B\n"], ["A\n"], -1, 0),
              new MergeConflict(["4\n"], ["B\n"], ["A\n", "4\n"], 4, 5),
            ];
            $this->assertEquals($conflicts, $e->getConflicts());
            $this->assertEquals($local, $e->getMerged());
        }
    }

    /**
     * Test with a change of only some part of the line.
     */
    public function testInline()
    {
        $base = <<<"EOD"
unchanged
replaced
unchanged
normal
unchanged
unchanged
removed

EOD;
        $remote   = <<<"EOD"
added
unchanged
replacement
unchanged
normal
unchanged
unchanged

EOD;
        $local    = <<<"EOD"
unchanged
replaced
unchanged
normal??
unchanged
unchanged

EOD;
        $expected = <<<"EOD"
added
unchanged
replacement
unchanged
normal??
unchanged
unchanged

EOD;
        $conflicting = <<<"EOD"
unchanged
replaced
unchanged
normal!!
unchanged
unchanged

EOD;

        $result = $this->merger->merge($base, $remote, $local);
        $this->assertEquals($expected, $result);
        $result = $this->merger->merge($base, $local, $remote);
        $this->assertEquals($expected, $result);

        try {
            $this->merger->merge($base, $local, $conflicting);
            $this->assertTrue(false, "Merge Exception not thrown.");
        } catch (MergeException $e) {
            $conflicts = [
              new MergeConflict(["normal\n"], ["normal??\n"], ["normal!!\n"], 3, 3),
            ];
            $this->assertEquals($conflicts, $e->getConflicts());
        }
    }

    /**
     * Takes a string and splits it into new lines for easy line merging.
     *
     * @param string $string
     *   The string which is split into new lines.
     * @param int $emptyLines
     *   The amount of empty new lines at the end of the string.
     *
     * @return string
     *   A string with new-line characters inserted between all characters.
     */
    protected static function split($string, $emptyLines = 1)
    {
        return implode("\n", str_split($string)) . str_repeat("\n", $emptyLines);
    }
}
