<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\AttributeConverter;

use LdapTools\Exception\EmptyResultException;
use LdapTools\Query\GroupTypeFlags;
use LdapTools\Query\LdapQueryBuilder;

/**
 * Given the primaryGroupID (The RID), convert it to the readable group name or convert a group name back into its RID.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class ConvertPrimaryGroup implements AttributeConverterInterface
{
    use AttributeConverterTrait;

    /**
     * {@inheritdoc}
     */
    public function fromLdap($value)
    {
        if (!$this->getLdapConnection()) {
            return $value;
        }
        /**
         * This is a rather expensive operation just to get a group name that will probably either always be the same
         * for all users in a domain or is very, very unlikely to ever change. Perhaps find a way to cache this or speed
         * it up?
         */
        $query = new LdapQueryBuilder($this->getLdapConnection());
        $userSid = $query->select('objectSid')->where(['distinguishedName' => $this->getDn()])->getLdapQuery()->getSingleScalarResult();
        $groupSid = preg_replace('/\d+$/', $value, (new ConvertWindowsSid())->fromLdap($userSid));

        return (new LdapQueryBuilder($this->getLdapConnection()))
            ->select('cn')
            ->where(['objectSid' => (new ConvertWindowsSid())->toLdap($groupSid)])
            ->getLdapQuery()
            ->getSingleScalarResult();
    }

    /**
     * {@inheritdoc}
     */
    public function toLdap($value)
    {
        if (!$this->getLdapConnection()) {
            return $value;
        }
        $groupSid = $this->validateAndGetGroupSID($value);

        return end(explode('-', (new ConvertWindowsSid())->fromLdap($groupSid)));
    }

    /**
     * Make sure that the group exists and that the user is already a member of it. If not, at least give an informative
     * message.
     *
     * @param string $name The group name.
     * @return string The text SID of the group.
     */
    protected function validateAndGetGroupSID($name)
    {
        $query = new LdapQueryBuilder($this->getLdapConnection());
        $query->select('objectSid')->where(['objectClass' => 'group', 'cn' => $name]);

        // Only validate group group membership on modification.
        if ($this->getOperationType() == AttributeConverterInterface::TYPE_MODIFY) {
            $query->andWhere(['member' => $this->getDn()]);
        }
        try {
            return $query->andWhere($query->filter()->bitwiseAnd('groupType', GroupTypeFlags::SECURITY_ENABLED))
                 ->getLdapQuery()
                 ->getSingleScalarResult();
        } catch (EmptyResultException $e) {
            throw new \InvalidArgumentException(sprintf(
                'Either the group "%s" doesn\'t exist, the user with DN "%s" is not a member of the group, the group'
                .' is not a security group, or this group is already their primary group.',
                $name,
                $this->getDn()
            ));
        }
    }
}
