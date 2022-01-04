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

namespace PhpMerge;

use Symplify\GitWrapper\GitWrapper;
use Symplify\GitWrapper\Exception\GitException;
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
 */
final class GitMerge extends PhpMergeBase implements PhpMergeInterface
{

    /**
     * The git working directory.
     *
     * @var \Symplify\GitWrapper\GitWorkingCopy|null
     */
    protected $git;

    /**
     * The git wrapper to use for merging.
     *
     * @var \Symplify\GitWrapper\GitWrapper
     */
    protected $wrapper;

    /**
     * The temporary directory in which git can work.
     * @var string|null
     */
    protected $dir;

    /**
     * The text of the last conflict
     * @var string
     */
    protected $conflict;

    /**
     * Constructor, not setting anything up.
     *
     * @param \Symplify\GitWrapper\GitWrapper|null $wrapper
     */
    public function __construct(GitWrapper $wrapper = null)
    {
        if (!$wrapper) {
            $wrapper = new GitWrapper('git');
        }
        $this->wrapper = $wrapper;
        $this->conflict = '';
        $this->git = null;
        $this->dir = null;
    }

    /**
     * Clean up the temporary git directory.
     */
    public function __destruct()
    {
        $this->cleanup();
    }

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
        try {
            return $this->mergeFile($file, $base, $remote, $local);
        } catch (GitException $e) {
            // Get conflicts by reading from the file.
            $conflicts = [];
            $merged = [];
            self::getConflicts($file, $base, $remote, $local, $conflicts, $merged);
            $merged = implode("", $merged);
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
        $content = file_get_contents($file);
        $raw = new \ArrayObject(self::splitStringByLines(file_get_contents($file)));
        $lineIterator = $raw->getIterator();
        $state = 'unchanged';
        $conflictIndicator = [
            '<<<<<<<' => 'local',
            '|||||||' => 'base',
            '=======' => 'remote',
            '>>>>>>>' => 'end conflict',
        ];

        // Create hunks from the text diff.
        $differ = new Differ();
        $remoteDiff = Line::createArray($differ->diffToArray($baseText, $remoteText));
        $localDiff = Line::createArray($differ->diffToArray($baseText, $localText));

        $remoteHunks = new \ArrayObject(Hunk::createArray($remoteDiff));
        $localHunks = new \ArrayObject(Hunk::createArray($localDiff));

        $remoteIterator = $remoteHunks->getIterator();
        $localIterator = $localHunks->getIterator();

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
            $gitKey = substr(trim($line), 0, 7);
            if (array_key_exists($gitKey, $conflictIndicator)) {
                // Check for a line matching a conflict indicator.
                $state = $conflictIndicator[$gitKey];
                $skipedLines++;
                if ('end conflict' === $state) {
                    // We just treated a merge conflict.
                    $conflicts[] = new MergeConflict($base, $remote, $local, $lineNumber, $newLine);
                    if (-1 === $lineNumber) {
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
                        if (-1 === $lineNumber) {
                            $lineNumber = 0;
                        }
                        break;
                    case 'remote':
                        $remote[] = $line;
                        $merged[] = $line;
                        break;
                    case 'unchanged':
                        if (-1 === $lineNumber) {
                            $lineNumber = 0;
                        }
                        $merged[] = $line;

                        /** @var Hunk|null $r */
                        $r = $remoteIterator->current();
                        /** @var Hunk|null $l */
                        $l = $localIterator->current();

                        if ($r == $l) {
                            // If they are the same, treat only one.
                            $localIterator->next();
                            $l = $localIterator->current();
                        }

                        // A hunk has been successfully merged, so we can just
                        // tally the lines added and removed and skip forward.
                        if (!is_null($r) && $r->getStart() == $lineNumber) {
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
                        } elseif (!is_null($l) && $l->getStart() == $lineNumber) {
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
        if (false !== $lastLine && $last !== $lastLine && rtrim($lastLine) === $last) {
            $lines[key($lines)] = $last;
        }

        return $lines;
    }

    /**
     * Set up the git wrapper and the temporary directory.
     */
    protected function setup()
    {
        if (!$this->dir) {
            // Greate a temporary directory.
            $tempfile = tempnam(sys_get_temp_dir(), '');
            mkdir($tempfile.'.git');
            if (file_exists($tempfile)) {
                unlink($tempfile);
            }
            $this->dir = $tempfile.'.git';
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
        if (isset($this->dir) && is_dir($this->dir)) {
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
}
