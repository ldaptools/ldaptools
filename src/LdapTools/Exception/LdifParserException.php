<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Exception;

/**
 * A LDIF parser exception.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class LdifParserException extends Exception
{
    /**
     * @param string $message
     * @param null|string $line
     * @param null|int $lineNumber
     */
    public function __construct($message, $line = null, $lineNumber = null)
    {
        $message = $this->buildMessage($message, $line, $lineNumber);
        parent::__construct($message, 0, null);
    }

    /**
     * @param string $message
     * @param string|null $line
     * @param int|null $lineNumber
     * @return string
     */
    protected function buildMessage($message, $line, $lineNumber)
    {
        if (!is_null($lineNumber)) {
            $message .= sprintf(' on line number %s', $lineNumber);
        }
        if (!is_null($line)) {
            $message .= sprintf(' near "%s"', $line);
        }

        return $message;
    }
}
