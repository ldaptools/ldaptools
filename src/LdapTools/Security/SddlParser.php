<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Security;

use LdapTools\Connection\LdapConnectionInterface;
use LdapTools\Exception\SddlParserException;
use LdapTools\Query\LdapQueryBuilder;
use LdapTools\Security\Ace\Ace;
use LdapTools\Security\Ace\AceFlags;
use LdapTools\Security\Ace\AceObjectFlags;
use LdapTools\Security\Ace\AceRights;
use LdapTools\Security\Ace\AceType;
use LdapTools\Security\Acl\Dacl;
use LdapTools\Security\Acl\Sacl;
use LdapTools\Utilities\LdapUtilities;

/**
 * Parses a SDDL string to the Security Descriptor it represents.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class SddlParser
{
    /**
     * Matches the basic SDDL format. Does not match the individual SDDL structures (owner, group, dacl, sacl).
     */
    const MATCH_SDDL = '/^O:([\d\w-]+?)G:([\d\w-]+)((D|S):(?![SD]:)(.*?))((D|S):(?![SD]:)(.*))?$/i';

    /**
     * Matches an SDDL string that represents an ACE.
     */
    const MATCH_ACE = '/^([A-Z]{1,2});([A-Z]+)?;([A-Z]+)?;([A-Z0-9-]+)?;([A-Z0-9-]+)?;(S-[\d-]+|[A-Z]{1,2})$/i';

    /**
     * @var LdapConnectionInterface|null
     */
    protected $connection;

    /**
     * @var SID This is the domain SID that can be used to form other well known SIDs.
     */
    protected $domainSid;

    /**
     * @var SID This is the root domain SID that can be used to form other well known SIDs in the root domain.
     */
    protected $rootDomainSid;

    /**
     * @param LdapConnectionInterface|null $connection
     */
    public function __construct(LdapConnectionInterface $connection = null)
    {
        $this->connection = $connection;
    }

    /**
     * Given a full SDDL string, parse it and return the SecurityDescriptor object that it represents.
     *
     * @param string $sddl
     * @return SecurityDescriptor
     * @throws SddlParserException
     */
    public function parse($sddl)
    {
        if (!preg_match(self::MATCH_SDDL, (string) $sddl, $matches)) {
            throw new SddlParserException('The SDDL string is not valid.');
        }

        $sd = (new SecurityDescriptor())
            ->setOwner($this->getSid($matches[1], 'owner'))
            ->setGroup($this->getSid($matches[2], 'group'));

        $this->parseAcl(strtoupper($matches[4]), $matches[5], $sd);
        if (isset($matches[7])) {
            $this->parseAcl(strtoupper($matches[7]), $matches[8], $sd);
        }

        return $sd;
    }

    /**
     * @param string $aclType
     * @param string $aclString
     * @param SecurityDescriptor $sd
     * @throws SddlParserException
     */
    protected function parseAcl($aclType, $aclString, SecurityDescriptor $sd)
    {
        $acl = $aclType === 'D' ? new Dacl() : new Sacl();

        if (preg_match('/^([A-Za-z]+)\(/', $aclString, $ctrlFlags)) {
            $this->parseControlFlags($ctrlFlags[1], $aclType, $sd);
        }

        preg_match_all("/\((.*?[^\)])\)/", $aclString, $aces);
        foreach ($aces[1] as $ace) {
            if (!preg_match(self::MATCH_ACE, $ace)) {
                throw new SddlParserException(sprintf(
                    'The ACE with value "%s" is not valid.',
                    $ace
                ));
            }
            $acl->addAce($this->parseAce($ace));
        }

        if ($acl instanceof Dacl && $sd->getDacl() || $acl instanceof Sacl && $sd->getSacl()) {
            throw new SddlParserException(sprintf(
                'The %sACL cannot be set more than once in the SDDL string.',
                $aclType
            ));
        }

        if ($acl instanceof Dacl) {
            $sd->setDacl($acl);
        } else {
            $sd->setSacl($acl);
        }
    }

    /**
     * @param string $sddl
     * @return Ace
     */
    protected function parseAce($sddl)
    {
        preg_match(self::MATCH_ACE, $sddl, $matches);

        $type = new AceType($matches[1]);
        if (!empty($matches[2])) {
            $flags = new AceFlags($this->getSddlFlagValue($matches[2], AceFlags::SHORT_NAME, 'flag'));
        } else {
            $flags = new AceFlags();
        }
        $rights = new AceRights($this->getSddlFlagValue($matches[3], AceRights::SHORT_NAME, 'right'));

        $objectType = empty($matches[4]) ? null : new GUID($matches[4]);
        $inheritedObjectType = empty($matches[5]) ? null : new GUID($matches[5]);
        $sid = $this->getSid($matches[6], 'ACE trustee');

        return (new Ace($type))
            ->setFlags($flags)
            ->setRights($rights)
            ->setObjectType($objectType)
            ->setInheritedObjectType($inheritedObjectType)
            ->setTrustee($sid);
    }

    /**
     * @param string $ctrlFlags
     * @param string $aclType
     * @param SecurityDescriptor $sd
     * @throws SddlParserException
     */
    protected function parseControlFlags($ctrlFlags, $aclType, SecurityDescriptor $sd)
    {
        $flags = [
            'P' => $aclType.'ACL_PROTECTED',
            'AR' => $aclType.'ACL_AUTO_INHERIT_REQ',
            'AI' => $aclType.'ACL_AUTO_INHERIT',
        ];

        foreach ($flags as $flag => $name) {
            if (strpos($ctrlFlags, $flag) !== false) {
                $sd->getControlFlags()->add(ControlFlags::FLAG[$name]);
                $ctrlFlags = str_replace($flag, '', $ctrlFlags);
            }
        }

        if (!empty($ctrlFlags)) {
            throw new SddlParserException(sprintf(
                'The control flag(s) "%s" passed to the %sACL are not recognized.',
                $ctrlFlags,
                $aclType
            ));
        }
    }

    /**
     * @param string $sddl
     * @param array $possibleFlags
     * @param string $name
     * @return int
     * @throws SddlParserException
     */
    protected function getSddlFlagValue($sddl, array $possibleFlags, $name)
    {
        $flags = 0;

        foreach (str_split($sddl, 2) as $flag) {
            if (!array_key_exists(strtoupper($flag), $possibleFlags)) {
                throw new SddlParserException(sprintf(
                    'The ACE %s "%s" is not valid. Valid flags are: %s',
                    $name,
                    $flag,
                    implode(', ', array_keys($possibleFlags))
                ));
            }
            $flags += $possibleFlags[strtoupper($flag)];
        }

        return $flags;
    }

    /**
     * @param string $sid
     * @param string $type
     * @return SID
     * @throws SddlParserException
     */
    protected function getSid($sid, $type)
    {
        $sid = strtoupper($sid);

        // This is a SID short name, or explicit SID, that requires no domain SID lookup...
        if (array_key_exists($sid, SID::SHORT_NAME) || LdapUtilities::isValidSid($sid)) {
            $sid = new SID($sid);
        // This is a SID that requires a domain SID or root domain SID lookup...
        } elseif (array_key_exists($sid, SID::SHORT_NAME_DOMAIN) || array_key_exists($sid, SID::SHORT_NAME_ROOT_DOMAIN)) {
            $sid = $this->getWellKnownDomainSid($sid, array_key_exists($sid, SID::SHORT_NAME_ROOT_DOMAIN));
        } else {
            throw new SddlParserException(sprintf(
                'The value "%s" is not a valid SID for the %s.',
                $sid,
                $type
            ));
        }

        return $sid;
    }

    /**
     * @param string $sid
     * @param bool $isRoot
     * @return SID
     */
    protected function getWellKnownDomainSid($sid, $isRoot)
    {
        $sid = $isRoot ? SID::SHORT_NAME_ROOT_DOMAIN[$sid] : SID::SHORT_NAME_DOMAIN[$sid];
        $domainSubAuth = implode('-', array_slice($this->getDomainSid($isRoot)->getSubAuthorities(), 1));

        return new SID(str_replace('{domainsid}', $domainSubAuth, $sid));
    }

    /**
     * @param bool $isRoot
     * @return SID
     */
    protected function getDomainSid($isRoot)
    {
        if (!$isRoot && $this->domainSid) {
            return $this->domainSid;
        } elseif ($isRoot && $this->rootDomainSid) {
            return $this->rootDomainSid;
        }

        $baseDn = $isRoot ? 'rootDomainNamingContext' : 'defaultNamingContext';
        $domainSid = (new LdapQueryBuilder($this->connection))
            ->setBaseDn($this->connection->getRootDse()->get($baseDn))
            ->select('objectSid')
            ->where(['objectClass' => 'domain'])
            ->andWhere(['objectClass' => 'domainDns'])
            ->setSizeLimit(1)
            ->getLdapQuery()
            ->getSingleScalarResult();
        $sid = new SID($domainSid);

        if ($isRoot) {
            $this->rootDomainSid = $sid;
        } else {
            $this->domainSid = $sid;
        }

        return $sid;
    }
}
