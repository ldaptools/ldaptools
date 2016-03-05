<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Ldif\Entry;

use LdapTools\Connection\LdapAwareInterface;
use LdapTools\Connection\LdapConnection;
use LdapTools\Connection\LdapControl;
use LdapTools\Hydrator\OperationHydrator;
use LdapTools\Ldif\Ldif;
use LdapTools\Ldif\LdifStringBuilderTrait;
use LdapTools\Operation\LdapOperationInterface;
use LdapTools\Schema\SchemaAwareInterface;

/**
 * Common LDIF entry functions and properties.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
trait LdifEntryTrait
{
    use LdifStringBuilderTrait;

    /**
     * @var string The DN represented by this LDIF entry.
     */
    protected $dn;

    /**
     * @var LdapControl[]
     */
    protected $controls = [];

    /**
     * @var string The changetype for the entry.
     */
    protected $changeType;

    /**
     * @var null|string The LDAP object type this entry is based off of.
     */
    protected $type;

    /**
     * Get the DN for the LDIF entry.
     *
     * @return string
     */
    public function getDn()
    {
        return $this->dn;
    }

    /**
     * Set the DN for the LDIF entry.
     *
     * @param string $dn
     * @return $this
     */
    public function setDn($dn)
    {
        $this->dn = $dn;

        return $this;
    }

    /**
     * Add a LDAP control that should be used when processing this entry.
     *
     * @param LdapControl $control
     * @return $this
     */
    public function addControl(LdapControl $control)
    {
        $this->controls[] = $control;

        return $this;
    }

    /**
     * Get the controls for this entry.
     *
     * @return LdapControl[]
     */
    public function getControls()
    {
        return $this->controls;
    }

    /**
     * Set the LDAP object type this entry should represent. This is a string from the schema for the domain, such as
     * 'user', 'group', 'contact', etc. If this is not null then the schema definition is used when transforming the
     * entry to a string/operation.
     *
     * @param string|null $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get the LDAP object type this entry represents. See 'setType()' for more information.
     *
     * @return null|string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Get the base start of the string for a LDIF entry (comments, DN, changetype, controls).
     *
     * @param string|null $dn
     * @return string
     */
    protected function getCommonString($dn = null)
    {
        $dn = $dn ?: $this->dn;
        $ldif = $this->addCommentsToString('');
        $ldif .= $this->getLdifLine(Ldif::DIRECTIVE_DN, $dn);
        $ldif = $this->addControlsToString($ldif);
        $ldif .= $this->getLdifLine(Ldif::DIRECTIVE_CHANGETYPE, $this->changeType);

        return $ldif;
    }

    /**
     * Add any LDAP controls to the specified LDIF.
     *
     * @param string $ldif
     * @return string
     */
    protected function addControlsToString($ldif)
    {
        foreach ($this->controls as $control) {
            $value = $control->getOid().' '.$control->getCriticality();
            if (!is_null($control->getValue())) {
                $value .= ' '.$control->getValue();
            }
            $ldif .= $this->getLdifLine(Ldif::DIRECTIVE_CONTROL, $value);
        }

        return $ldif;
    }

    /**
     * @param OperationHydrator $hydrator
     * @param LdapOperationInterface $operation
     * @return LdapOperationInterface
     */
    protected function hydrateOperation(OperationHydrator $hydrator, LdapOperationInterface $operation)
    {
        if ($this instanceof SchemaAwareInterface) {
            $hydrator->setLdapObjectSchema($this->getLdapObjectSchema());
        }
        if ($this instanceof LdapAwareInterface) {
            $hydrator->setLdapConnection($this->getLdapConnection());
        }

        return $hydrator->hydrateToLdap($operation);
    }

    /**
     * Determine if we might need to work around the unusual formatting for unicodePwd. This is pretty much the only
     * attribute that will ever need a special case for conversion for LDIF creation.
     *
     * @todo How to get around this? Implementing another attribute conversion type for just a single attribute seems silly.
     * @link https://support.microsoft.com/en-us/kb/263991
     * @return bool
     */
    protected function isUnicodePwdHackNeeded()
    {
        if (!(isset($this->connection) && $this->connection->getConfig()->getLdapType() == LdapConnection::TYPE_AD && isset($this->schema))) {
            return false;
        }

        return $this->schema->hasNamesMappedToAttribute('unicodePwd');
    }
}
