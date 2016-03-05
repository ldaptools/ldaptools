<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Ldif;

use LdapTools\Exception\InvalidArgumentException;

/**
 * Common methods/properties used to construct LDIF entries.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
trait LdifStringBuilderTrait
{
    /**
     * @var string[] Any comments associated with the entry.
     */
    protected $comments = [];

    /**
     * @var int The max length for a line before it is folded (if line folding is enabled).
     */
    protected $maxLineLength = 76;

    /**
     * @var bool Whether or not a value should be folded (continued on the next line) if it goes past $maxLineLength
     */
    protected $lineFolding = false;

    /**
     * @var string The line ending to use.
     */
    protected $lineEnding = Ldif::LINE_ENDING['WINDOWS'];

    /**
     * Add a comment to be associated with this entry.
     *
     * @param string ...$comments
     * @return $this
     */
    public function addComment(...$comments)
    {
        foreach ($comments as $comment) {
            $this->comments[] = $comment;
        }

        return $this;
    }

    /**
     * Get the comments for this entry.
     *
     * @return string[]
     */
    public function getComments()
    {
        return $this->comments;
    }

    /**
     * Set the line ending to be used. See the Ldif::LINE_ENDING constant for values.
     *
     * @param string $lineEnding
     * @return $this
     */
    public function setLineEnding($lineEnding)
    {
        if (!in_array($lineEnding, Ldif::LINE_ENDING)) {
            throw new InvalidArgumentException('The line ending specified is invalid');
        }
        $this->lineEnding = $lineEnding;

        return $this;
    }

    /**
     * Get the line ending that will be used.
     *
     * @return string
     */
    public function getLineEnding()
    {
        return $this->lineEnding;
    }

    /**
     * Set whether or not lines exceeding a certain length should be folded (continued on the next line)
     *
     * @param bool $lineFolding
     * @return $this
     */
    public function setLineFolding($lineFolding)
    {
        $this->lineFolding = (bool) $lineFolding;

        return $this;
    }

    /**
     * Get whether or not lines exceeding a certain length should be folded (continued on the next line)
     *
     * @return bool
     */
    public function getLineFolding()
    {
        return $this->lineFolding;
    }

    /**
     * Set the max length for a line before the value is folded (continued on the next line).
     *
     * @param int $length
     * @return $this
     */
    public function setMaxLineLength($length)
    {
        $this->maxLineLength = $length;

        return $this;
    }

    /**
     * Get the max length for a line before the value is folded (continued on the next line).
     *
     * @return int
     */
    public function getMaxLineLength()
    {
        return $this->maxLineLength;
    }

    /**
     * Add any specified comments to the generated LDIF.
     *
     * @param string $ldif
     * @return string
     */
    protected function addCommentsToString($ldif)
    {
        foreach ($this->comments as $comment) {
            $ldif .= Ldif::COMMENT.' '.$this->getValueForLine($comment).$this->lineEnding;
        }

        return $ldif;
    }

    /**
     * Construct a single line of the LDIF for a given directive and value.
     *
     * @param string $directive
     * @param string $value
     * @return string
     */
    protected function getLdifLine($directive, $value)
    {
        $separator = Ldif::KEY_VALUE_SEPARATOR;

        // Per the RFC, any value starting with a space should be base64 encoded
        if ((substr($value, 0, strlen(' ')) === ' ')) {
            $separator .= Ldif::KEY_VALUE_SEPARATOR;
            $value = base64_encode($value);
        }

        return $directive.$separator.' '.$this->getValueForLine($value).$this->lineEnding;
    }

    /**
     * Gets the value for the line while taking into account any line folding set.
     *
     * @param $value
     * @return string
     */
    protected function getValueForLine($value)
    {
        /**
         * Is this the correct way to do line folding? If a folded line starts/ends with a space should the value,
         * and this every line, be base64 encoded? Reading the RFC this does not seem clear.
         */
        if ($this->lineFolding) {
            $value = implode(
                $this->lineEnding." ",
                str_split($value, $this->maxLineLength)
            );
        }

        return $value;
    }
}
