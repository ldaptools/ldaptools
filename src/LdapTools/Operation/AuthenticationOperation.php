<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Operation;

use LdapTools\Exception\LdapBindException;
use LdapTools\Utilities\LdapUtilities;

/**
 * Represents an authentication operation against LDAP.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class AuthenticationOperation implements LdapOperationInterface
{
    use LdapOperationTrait;

    /**
     * @var array
     */
    protected $properties = [
        'username' => null,
        'password' => null,
        'isAnonymousBind' => false,
        'switchToCredentials' => false,
    ];

    /**
     * @param null|string $username
     * @param null|string $password
     */
    public function __construct($username = null, $password = null)
    {
        $this->properties['username'] = $username;
        $this->properties['password'] = $password;
    }

    /**
     * Set the username that will be used in the authentication operation.
     *
     * @param string $username
     * @return $this
     */
    public function setUsername($username)
    {
        $this->properties['username'] = $username;

        return $this;
    }

    /**
     * Get the username that will be used in the authentication operation.
     *
     * @return string|null
     */
    public function getUsername()
    {
        return $this->properties['username'];
    }

    /**
     * Set the password that will be used for the authentication operation.
     *
     * @param string $password
     * @return $this
     */
    public function setPassword($password)
    {
        $this->properties['password'] = $password;

        return $this;
    }

    /**
     * Get the password that will be used for the authentication operation.
     *
     * @return string|null
     */
    public function getPassword()
    {
        return $this->properties['password'];
    }

    /**
     * Set whether this authentication attempt should be an anonymous bind.
     *
     * @param bool $anonymous
     * @return $this
     */
    public function setIsAnonymousBind($anonymous)
    {
        $this->properties['isAnonymousBind'] = (bool) $anonymous;

        return $this;
    }

    /**
     * Get whether this authentication attempt should be an anonymous bind.
     *
     * @return bool
     */
    public function getIsAnonymousBind()
    {
        return $this->properties['isAnonymousBind'];
    }

    /**
     * Set whether the connection should switch to be bound under the context of the credentials given by this object
     * when this operation is executed.
     *
     * @param bool $switchToCredentials
     * @return $this
     */
    public function setSwitchToCredentials($switchToCredentials)
    {
        $this->properties['switchToCredentials'] = $switchToCredentials;

        return $this;
    }

    /**
     * Get whether the connection should switch to be bound under the context of the credentials given by this object
     * when this operation is executed.
     *
     * @return bool
     */
    public function getSwitchToCredentials()
    {
        return $this->properties['switchToCredentials'];
    }

    /**
     * {@inheritdoc}
     */
    public function getLdapFunction()
    {
        return 'ldap_bind';
    }

    /**
     * {@inheritdoc}
     */
    public function getArguments()
    {
        $this->validateArguments();

        return [
            $this->properties['username'],
            $this->properties['password'],
            $this->properties['isAnonymousBind'],
            $this->server,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getLogArray()
    {
        // By default we probably shouldn't expose password info to the logger.
        // Though it is still available via the getPassword() method if needed.
        return $this->mergeLogDefaults([
            'Username' => $this->properties['username'],
            'Password' => LdapUtilities::MASK_PASSWORD,
            'Anonymous' => var_export($this->properties['isAnonymousBind'], true),
            'Switch to Credentials' => var_export($this->properties['switchToCredentials'], true),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Authentication';
    }

    /**
     * Validates that the arguments given don't have any issues.
     *
     * @throws LdapBindException
     */
    protected function validateArguments()
    {
        if ($this->getIsAnonymousBind()) {
            return;
        }
        if (empty($this->properties['username']) || empty($this->properties['password'])) {
            throw new LdapBindException("You must specify a username and password.");
        }
    }
}
