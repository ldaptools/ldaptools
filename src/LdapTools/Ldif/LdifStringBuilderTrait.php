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
     * Add a comment to be associated with this entry.
     *
     * @param string $comment
     * @return $this
     */
    public function addComment($comment)
    {
        $this->comments[] = $comment;

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
     * Add any specified comments to the generated LDIF.
     *
     * @param string $ldif
     * @return string
     */
    protected function addCommentsToString($ldif)
    {
        foreach ($this->comments as $comment) {
            $ldif .= Ldif::COMMENT.' '.$comment.Ldif::ENTRY_SEPARATOR;
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

        return $directive.$separator.' '.$value.Ldif::ENTRY_SEPARATOR;
    }
}
