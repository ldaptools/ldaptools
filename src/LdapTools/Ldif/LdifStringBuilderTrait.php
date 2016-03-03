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
     * Add any specified comments to the generated LDIF.
     *
     * @param string $ldif
     * @return string
     */
    protected function addCommentsToString($ldif)
    {
        foreach ($this->comments as $comment) {
            $ldif .= Ldif::COMMENT.' '.$comment.$this->lineEnding;
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

        return $directive.$separator.' '.$value.$this->lineEnding;
    }
}
