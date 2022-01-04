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
 * Class Line
 *
 * @internal This class is not part of the public api.
 */
final class Line
{
    public const ADDED     = 1;
    public const REMOVED   = 2;
    public const UNCHANGED = 3;

    /**
     * @var int
     */
    private $type;

    /**
     * @var string
     */
    private $content;

    /**
     * @var int
     */
    protected $index;

    /**
     * Line constructor.
     * @param int $type
     * @param string $content
     * @param int $index
     */
    public function __construct($type = self::UNCHANGED, $content = '', $index = null)
    {
        $this->type = $type;
        $this->content = $content;
        $this->index = $index;
    }

    /**
     * @return int
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * @param array $diff
     *
     * @return Line[]
     */
    public static function createArray($diff)
    {
        $index = -1;
        $lines = [];
        foreach ($diff as $key => $value) {
            switch ($value[1]) {
                case 0:
                    $index++;
                    $line = new Line(Line::UNCHANGED, $value[0], $index);
                    break;

                case 1:
                    $line = new Line(Line::ADDED, $value[0], $index);
                    break;

                case 2:
                    $index++;
                    $line = new Line(Line::REMOVED, $value[0], $index);
                    break;

                default:
                    throw new \RuntimeException('Unsupported diff line type.');
            }
            $lines[] = $line;
        }

        return $lines;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }
}
