<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Connection\AD;

use LdapTools\Enums\AD\ResponseCode;
use LdapTools\Connection\LastErrorStrategy;

/**
 * Try to retrieve a more detailed error message based on the specific AD response code.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class ADLastErrorStrategy extends LastErrorStrategy
{
    /**
     * {@inheritdoc}
     */
    public function getLastErrorMessage()
    {
        $extendedError = $this->getExtendedErrorNumber();

        if (ResponseCode::hasMessageForError($extendedError)) {
            $message = ResponseCode::getMessageForError($extendedError);
        } else {
            $message = parent::getLastErrorMessage();
        }

        return $message;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedErrorNumber()
    {
        $errorNumber = 0;
        $extendedError = $this->getDiagnosticMessage();

        if (!empty($extendedError) && preg_match('/, data (\d+),?/', $extendedError, $matches)) {
            $errorNumber = hexdec(intval($matches[1]));
        }

        return $errorNumber;
    }
}
