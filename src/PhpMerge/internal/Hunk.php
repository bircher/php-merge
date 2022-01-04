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

namespace PhpMerge\internal;

/**
 * Class Hunk
 *
 * This represents a collection of changed lines.
 *
 * @internal This class is not part of the public api.
 */
final class Hunk
{

    const ADDED = 1;
    const REMOVED = 2;
    const REPLACED = 3;

    /**
     * @var int
     */
    protected $start;
    /**
     * @var int
     */
    protected $end;
    /**
     * @var Line[]
     */
    protected $lines;
    /**
     * @var int
     */
    protected $type;

    /**
     * The Hunk constructor.
     *
     * @param Line|Line[] $lines
     *   The lines belonging to the hunk.
     * @param int $type
     *   The type of the hunk: Hunk::ADDED Hunk::REMOVED Hunk::REPLACED
     * @param int $start
     *   The line index where the hunk starts.
     * @param int $end
     *   The line index where the hunk stops.
     */
    public function __construct($lines, $type, $start, $end = null)
    {
        $this->start = $start;
        if (is_null($end)) {
            $end = $start;
        }
        $this->end = $end;
        if (!is_array($lines)) {
            $lines = [$lines];
        }
        $this->lines = $lines;
        $this->type = $type;
    }

    /**
     * Add a new line to the hunk.
     *
     * @param \PhpMerge\internal\Line $line
     *   The line to add.
     */
    public function addLine(Line $line)
    {
        $this->lines[] = $line;
        $this->end = $line->getIndex();
    }

    /**
     * Create an array of hunks out of an array of lines.
     *
     * @param Line[] $lines
     *   The lines of the diff.
     * @return Hunk[]
     *   The hunks in the lines.
     */
    public static function createArray($lines)
    {
        $op = Line::UNCHANGED;
        $hunks = [];
        /** @var Hunk $current */
        $current = null;
        foreach ($lines as $line) {
            switch ($line->getType()) {
                case Line::REMOVED:
                    if (Line::REMOVED !== $op) {
                        // The last line was not removed so we start a new hunk.
                        $current = new Hunk($line, Hunk::REMOVED, $line->getIndex());
                    } else {
                        // continue adding the line to the hunk.
                        $current->addLine($line);
                    }
                    break;
                case Line::ADDED:
                    switch ($op) {
                        case Line::REMOVED:
                            // The hunk is a replacement.
                            $current->setType(Hunk::REPLACED);
                            $current->addLine($line);
                            break;
                        case Line::ADDED:
                            $current->addLine($line);
                            break;
                        case Line::UNCHANGED:
                            // Add a new hunk with the added type.
                            $current = new Hunk($line, Hunk::ADDED, $line->getIndex());
                            break;
                    }
                    break;
                case Line::UNCHANGED:
                    if ($current) {
                        // The hunk exists so add it to the array.
                        $hunks[] = $current;
                        $current = null;
                    }
                    break;
            }
            $op = $line->getType();
        }
        if ($current) {
            // The last line was part of a hunk, so add it.
            $hunks[] = $current;
        }

        return $hunks;
    }

    /**
     * Get the line index where the hunk starts.
     *
     * @return int
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * Get the line index where the hunk ends.
     *
     * @return int
     */
    public function getEnd()
    {
        return $this->end;
    }

    /**
     * Get the type of the hunk.
     *
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Get the lines of the hunk.
     *
     * @return Line[]
     */
    public function getLines()
    {
        return $this->lines;
    }

    /**
     * Get the removed lines.
     *
     * @return Line[]
     */
    public function getRemovedLines()
    {
        return array_values(array_filter(
            $this->lines,
            function (Line $line) {
                return $line->getType() === Line::REMOVED;
            }
        ));
    }

    /**
     * Get the added lines.
     *
     * @return Line[]
     */
    public function getAddedLines()
    {
        return array_values(array_filter(
            $this->lines,
            function (Line $line) {
                return $line->getType() === Line::ADDED;
            }
        ));
    }

    /**
     * Get the lines content.
     *
     * @return string[]
     */
    public function getLinesContent()
    {
        return array_map(
            function (Line $line) {
                return $line->getContent();
            },
            $this->getAddedLines()
        );
    }

    /**
     * Test whether the hunk is to be considered for a conflict resolution.
     *
     * @param int $line
     *   The line number in the original text to test.
     *
     * @return bool
     *   Whether the line is affected by the hunk.
     */
    public function isLineNumberAffected($line)
    {
        // Added lines also affect the ones afterwards in conflict resolution,
        // because they are added in between.
        $bleed = ($this->type === self::ADDED ? 1 : 0);

        return ($line >= $this->start && $line <= $this->end + $bleed);
    }

    /**
     * @param \PhpMerge\internal\Hunk|null $hunk
     *
     * @return bool
     */
    public function hasIntersection(Hunk $hunk = null)
    {
        if (!$hunk) {
            return false;
        }
        if ($this->type === self::ADDED && $hunk->type === self::ADDED) {
            return $this->start === $hunk->start;
        }

        return $this->isLineNumberAffected($hunk->start) || $this->isLineNumberAffected($hunk->end)
          || $hunk->isLineNumberAffected($this->start) || $hunk->isLineNumberAffected($this->end);
    }

    /**
     * @param \PhpMerge\internal\Hunk|null $other
     *
     * @return bool
     */
    public function isSame(Hunk $other = null): bool
    {
        if (is_null($other)) {
            return false;
        }
        if ($this->type !== $other->type || $this->start !== $other->start || $this->end !== $other->end) {
            return false;
        }
        if (count($this->lines) !== count($other->lines)) {
            return false;
        }
        foreach ($this->lines as $key => $line) {
            if (!$line->isSame($other->lines[$key])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Set the type of the hunk.
     *
     * @param int $type
     */
    protected function setType($type)
    {
        $this->type = $type;
    }
}
