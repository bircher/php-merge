<?php
/**
 * This file is part of the php-merge package.
 *
 * (c) Fabian Bircher <opensource@fabianbircher.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpMerge;

use SebastianBergmann\Diff\Line as DiffLine;

/**
 * Class Line
 *
 * @package    PhpMerge
 * @author     Fabian Bircher <opensource@fabianbircher.com>
 * @copyright  Fabian Bircher <opensource@fabianbircher.com>
 * @license    https://opensource.org/licenses/MIT
 * @version    Release: @package_version@
 * @link       http://github.com/bircher/php-merge
 */
class Line extends DiffLine
{

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
        parent::__construct($type, $content);

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

}