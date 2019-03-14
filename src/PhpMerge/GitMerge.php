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

use GitWrapper\GitWrapper;
use GitWrapper\GitException;
use PhpMerge\internal\Line;
use PhpMerge\internal\Hunk;
use PhpMerge\internal\PhpMergeBase;
use SebastianBergmann\Diff\Differ;

/**
 * Class GitMerge merges three strings with git as the backend.
 *
 * A temporary directory is created and a git repository is initialised in it,
 * then a file is created within the directory containing the string to merge.
 * This was the original merge class but while it is nice not to have to deal
 * with merging, it has a considerable performance implication. So now this
 * implementation serves as a reference to make sure the other classes behave.
 *
 * @package   PhpMerge
 * @author    Fabian Bircher <opensource@fabianbircher.com>
 * @copyright 2015 Fabian Bircher <opensource@fabianbircher.com>
 * @license   https://opensource.org/licenses/MIT
 * @version   Release: @package_version@
 * @link      http://github.com/bircher/php-merge
 * @category  library
 */
final class GitMerge extends PhpMergeBase implements PhpMergeInterface
{

    /**
     * The git working directory.
     *
     * @var \GitWrapper\GitWorkingCopy
     */
    protected $git;

    /**
     * The git wrapper to use for merging.
     *
     * @var \GitWrapper\GitWrapper
     */
    protected $wrapper;

    /**
     * The temporary directory in which git can work.
     * @var string
     */
    protected $dir;

    /**
     * The text of the last conflict
     * @var string
     */
    protected $conflict;

    /**
     * {@inheritdoc}
     */
    public function merge(string $base, string $remote, string $local) : string
    {

        // Skip merging if there is nothing to do.
        if ($merged = PhpMergeBase::simpleMerge($base, $remote, $local)) {
            return $merged;
        }

        // Only set up the git wrapper if we really merge something.
        $this->setup();

        $file = tempnam($this->dir, '');
        // Compatibility for 2.x branch and sebastian/diff 2.x and 3.x.
        $base = self::preMergeAlter($base);
        $remote = self::preMergeAlter($remote);
        $local = self::preMergeAlter($local);
        try {
            $merged = $this->mergeFile($file, $base, $remote, $local);
            return self::postMergeAlter($merged);
        } catch (GitException $e) {
            // Get conflicts by reading from the file.
            $conflicts = [];
            $merged = [];
            self::getConflicts($file, $base, $remote, $local, $conflicts, $merged);
            $merged = implode("", $merged);
            $merged = self::postMergeAlter($merged);
            // Set the file to the merged one with the first text for conflicts.
            file_put_contents($file, $merged);
            $this->git->add($file);
            $this->git->commit('Resolve merge conflict.');
            throw new MergeException('A merge conflict has occurred.', $conflicts, $merged, 0, $e);
        }
    }

    /**
     * Merge three strings in a specified file.
     *
     * @param string $file
     *   The file name in the git repository to which the content is written.
     * @param string $base
     *   The common base text.
     * @param string $remote
     *   The first changed text.
     * @param string $local
     *   The second changed text
     *
     * @return string
     *   The merged text.
     */
    protected function mergeFile(string $file, string $base, string $remote, string $local) : string
    {
        file_put_contents($file, $base);
        $this->git->add($file);
        $this->git->commit('Add base.');

        if (!in_array('original', $this->git->getBranches()->all())) {
            $this->git->checkoutNewBranch('original');
        } else {
            $this->git->checkout('original');
            $this->git->rebase('master');
        }

        file_put_contents($file, $remote);
        $this->git->add($file);
        $this->git->commit('Add remote.');

        $this->git->checkout('master');

        file_put_contents($file, $local);
        $this->git->add($file);
        $this->git->commit('Add local.');

        $this->git->merge('original');
        return file_get_contents($file);
    }

    /**
     * Get the conflicts from a file which is left with merge conflicts.
     *
     * @param string $file
     *   The file name.
     * @param string $baseText
     *   The original text used for merging.
     * @param string $remoteText
     *   The first chaned text.
     * @param string $localText
     *   The second changed text.
     * @param MergeConflict[] $conflicts
     *   The merge conflicts will be apended to this array.
     * @param string[] $merged
     *   The merged text resolving conflicts by using the first set of changes.
     */
    protected static function getConflicts($file, $baseText, $remoteText, $localText, &$conflicts, &$merged)
    {
        $raw = new \ArrayObject(self::splitStringByLines(file_get_contents($file)));
        $lineIterator = $raw->getIterator();
        $state = 'unchanged';
        $conflictIndicator = [
            '<<<<<<< HEAD' => 'local',
            '||||||| merged common ancestors' => 'base',
            '=======' => 'remote',
            '>>>>>>> original' => 'end conflict',
        ];

        // Create hunks from the text diff.
        $differ = new Differ();
        $remoteDiff = Line::createArray($differ->diffToArray($baseText, $remoteText));
        $localDiff = Line::createArray($differ->diffToArray($baseText, $localText));

        $remote_hunks = new \ArrayObject(Hunk::createArray($remoteDiff));
        $local_hunks = new \ArrayObject(Hunk::createArray($localDiff));

        $remoteIterator = $remote_hunks->getIterator();
        $localIterator = $local_hunks->getIterator();

        $base = [];
        $remote = [];
        $local = [];
        $lineNumber = -1;
        $newLine = 0;
        $skipedLines = 0;
        $addingConflict = false;
        // Loop over all the lines in the file.
        while ($lineIterator->valid()) {
            $line = $lineIterator->current();
            if (array_key_exists(trim($line), $conflictIndicator)) {
                // Check for a line matching a conflict indicator.
                $state = $conflictIndicator[trim($line)];
                $skipedLines++;
                if ($state == 'end conflict') {
                    // We just treated a merge conflict.
                    $conflicts[] = new MergeConflict($base, $remote, $local, $lineNumber, $newLine);
                    if ($lineNumber == -1) {
                        $lineNumber = 0;
                    }
                    $lineNumber += count($base);
                    $newLine += count($remote);
                    $base = [];
                    $remote = [];
                    $local = [];
                    $remoteIterator->next();
                    $localIterator->next();

                    if ($addingConflict) {
                        // Advance the counter for conflicts with adding.
                        $lineNumber++;
                        $newLine++;
                        $addingConflict = false;
                    }
                    $state = 'unchanged';
                }
            } else {
                switch ($state) {
                    case 'local':
                        $local[] = $line;
                        $skipedLines++;
                        break;
                    case 'base':
                        $base[] = $line;
                        $skipedLines++;
                        if ($lineNumber == -1) {
                            $lineNumber = 0;
                        }
                        break;
                    case 'remote':
                        $remote[] = $line;
                        $merged[] = $line;
                        break;
                    case 'unchanged':
                        if ($lineNumber == -1) {
                            $lineNumber = 0;
                        }
                        $merged[] = $line;

                        /** @var Hunk $r */
                        $r = $remoteIterator->current();
                        /** @var Hunk $l */
                        $l = $localIterator->current();

                        if ($r == $l) {
                            // If they are the same, treat only one.
                            $localIterator->next();
                            $l = $localIterator->current();
                        }

                        // A hunk has been successfully merged, so we can just
                        // tally the lines added and removed and skip forward.
                        if ($r && $r->getStart() == $lineNumber) {
                            if (!$r->hasIntersection($l)) {
                                $lineNumber += count($r->getRemovedLines());
                                $newLine += count($r->getAddedLines());
                                $lineIterator->seek($newLine + $skipedLines - 1);
                                $remoteIterator->next();
                            } else {
                                // If the conflict occurs on added lines, the
                                // next line in the merge will deal with it.
                                if ($r->getType() == Hunk::ADDED && $l->getType() == Hunk::ADDED) {
                                    $addingConflict = true;
                                } else {
                                    $lineNumber++;
                                    $newLine++;
                                }
                            }
                        } elseif ($l && $l->getStart() == $lineNumber) {
                            if (!$l->hasIntersection($r)) {
                                $lineNumber += count($l->getRemovedLines());
                                $newLine += count($l->getAddedLines());
                                $lineIterator->seek($newLine + $skipedLines - 1);
                                $localIterator->next();
                            } else {
                                $lineNumber++;
                                $newLine++;
                            }
                        } else {
                            $lineNumber++;
                            $newLine++;
                        }
                        break;
                }
            }
            $lineIterator->next();
        }

        $rawBase = self::splitStringByLines($baseText);
        $lastConflict = end($conflicts);
        // Check if the last conflict was at the end of the text.
        if ($lastConflict->getBaseLine() + count($lastConflict->getBase()) == count($rawBase)) {
            // Fix the last lines of all the texts as we can not know from
            // the merged text if there was a new line at the end or not.
            $base = self::fixLastLine($lastConflict->getBase(), $rawBase);
            $remote = self::fixLastLine($lastConflict->getRemote(), self::splitStringByLines($remoteText));
            $local = self::fixLastLine($lastConflict->getLocal(), self::splitStringByLines($localText));

            $newConflict = new MergeConflict(
                $base,
                $remote,
                $local,
                $lastConflict->getBaseLine(),
                $lastConflict->getMergedLine()
            );
            $conflicts[key($conflicts)] = $newConflict;

            $lastMerged = end($merged);
            $lastRemote = end($remote);
            if ($lastMerged !== $lastRemote && rtrim($lastMerged) === $lastRemote) {
                $merged[key($merged)] = $lastRemote;
            }
        }
    }

    /**
     * @param array $lines
     * @param array $all
     *
     * @return array
     */
    protected static function fixLastLine(array $lines, array $all): array
    {
        $last = end($all);
        $lastLine = end($lines);
        if ($lastLine !== false && $last !== $lastLine && rtrim($lastLine) === $last) {
            $lines[key($lines)] = $last;
        }
        return $lines;
    }

    /**
     * Constructor, not setting anything up.
     *
     * @param \GitWrapper\GitWrapper $wrapper
     */
    public function __construct(GitWrapper $wrapper = null)
    {
        if (!$wrapper) {
            $wrapper = new GitWrapper();
        }
        $this->wrapper = $wrapper;
        $this->conflict = '';
        $this->git = null;
        $this->dir = null;
    }

    /**
     * Set up the git wrapper and the temporary directory.
     */
    protected function setup()
    {
        if (!$this->dir) {
            // Greate a temporary directory.
            $tempfile = tempnam(sys_get_temp_dir(), '');
            mkdir($tempfile . '.git');
            if (file_exists($tempfile)) {
                unlink($tempfile);
            }
            $this->dir = $tempfile . '.git';
            $this->git = $this->wrapper->init($this->dir);
        }
        if ($this->git) {
            $this->git->config('user.name', 'GitMerge');
            $this->git->config('user.email', 'gitmerge@php-merge.example.com');
            $this->git->config('merge.conflictStyle', 'diff3');
        }
    }

    /**
     * Clean the temporary directory used for merging.
     */
    protected function cleanup()
    {
        if (is_dir($this->dir)) {
            // Recursively delete all files and folders.
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->dir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($files as $fileinfo) {
                if ($fileinfo->isDir()) {
                    rmdir($fileinfo->getRealPath());
                } else {
                    unlink($fileinfo->getRealPath());
                }
            }
            rmdir($this->dir);
            unset($this->git);
        }
    }

    /**
     * Clean up the temporary git directory.
     */
    public function __destruct()
    {
        $this->cleanup();
    }
}
