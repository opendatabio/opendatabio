<?php
/*
OpenDataBio Modified version of the LatexToUnicodeProcessor in

namespace RenanBr\BibTexParser\Processor;

To allow only author and title to be unicode processed from latex or error may abound

 */

namespace App\Models;

use RenanBr\BibTexParser;
use RenanBr\BibTexParser\Processor;
use RenanBr\BibTexParser\Processor\TagCoverageTrait;

use Pandoc\Pandoc;
use Pandoc\PandocException;
use RenanBr\BibTexParser\Exception\ProcessorException;

/**
 * Translates LaTeX texts to unicode.
 */
class BibLatexTitleToUnicode
{
    use TagCoverageTrait;

    /** @var Pandoc|null */
    private $pandoc;

    /**
     * @return array
     */
    public function __invoke(array $entry)
    {
        $covered = $this->getCoveredTags(array_keys($entry));
        foreach ($covered as $tag) {
            // Translate string
            if ('author'== $tag || 'title' == $tag) {
              if (\is_string($entry[$tag])) {
                  $entry[$tag] = $this->decode($entry[$tag]);
                  continue;
                }

                // Translate array
                if (\is_array($entry[$tag])) {
                  array_walk_recursive($entry[$tag], function (&$text) {
                      if (\is_string($text)) {
                          $text = $this->decode($text);
                        }
                      });
                  }
            }
        }

        return $entry;
    }

    /**
     * @param mixed $text
     *
     * @return string
     */
    private function decode($text)
    {
        try {
            if (!$this->pandoc) {
                $this->pandoc = new Pandoc();
            }

            return $this->pandoc->runWith($text, [
                'from' => 'latex',
                'to' => 'plain',
            ]);
        } catch (PandocException $exception) {
            throw new ProcessorException(sprintf('Error while processing LaTeX to Unicode: %s', $exception->getMessage()), 0, $exception);
        }
    }
}
