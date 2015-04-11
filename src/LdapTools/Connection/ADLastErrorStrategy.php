<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Connection;

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

        if (array_key_exists($extendedError, ADResponseCodes::RESPONSE_MESSAGE)) {
            $message = ADResponseCodes::RESPONSE_MESSAGE[$extendedError];
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
        ldap_get_option($this->connection, self::DIAGNOSTIC_MESSAGE_OPT, $extendedError);

        $errorNumber = 0;
        if (!empty($extendedError)) {
            $errorNumber = explode(',', $extendedError);
            if (!isset($errorNumber[2])) {
                return 0;
            };
            $errorNumber = explode(' ', $errorNumber[2]);
            if (!isset($errorNumber[2])) {
                return 0;
            };
            $errorNumber = hexdec(intval($errorNumber[2]));
        }

        return $errorNumber;
    }
}
