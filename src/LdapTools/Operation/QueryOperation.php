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

use LdapTools\Cache\CacheItem;
use LdapTools\Exception\LdapQueryException;
use LdapTools\Query\OperatorCollection;

/**
 * Represents an operation to query LDAP and return a result set.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class QueryOperation implements LdapOperationInterface, CacheableOperationInterface
{
    use LdapOperationTrait, CacheableOperationTrait;

    /**
     * Scope name to LDAP function mappings.
     */
    const SCOPE = [
        'SUBTREE' => 'subtree',
        'ONELEVEL' => 'onelevel',
        'BASE' => 'base',
    ];

    /**
     * @var array
     */
    protected $functionMap = [
        self::SCOPE['SUBTREE'] => 'ldap_search',
        self::SCOPE['ONELEVEL'] => 'ldap_list',
        self::SCOPE['BASE'] => 'ldap_read',
    ];

    /**
     * @var array
     */
    protected $properties = [
        'baseDn' => null,
        'filter' => null,
        'attributes' => [],
        'pageSize' => null,
        'usePaging' => null,
        'scope' => self::SCOPE['SUBTREE'],
        'sizeLimit' => 0,
    ];

    /**
     * @param string|OperatorCollection $filter
     * @param array $attributes
     */
    public function __construct($filter, array $attributes = [])
    {
        $this->properties['filter'] = $filter;
        $this->properties['attributes'] = $attributes;
        $this->properties['scope'] = self::SCOPE['SUBTREE'];
    }

    /**
     * Get the base DN for the LDAP query operation.
     *
     * @return string
     */
    public function getBaseDn()
    {
        return $this->properties['baseDn'];
    }

    /**
     * Get the filter for the LDAP query operation.
     *
     * @return string|OperatorCollection
     */
    public function getFilter()
    {
        return $this->properties['filter'];
    }

    /**
     * Get the page size used for the LDAP query operation.
     *
     * @return int|null
     */
    public function getPageSize()
    {
        return $this->properties['pageSize'];
    }

    /**
     * Get the scope type for the query operation.
     *
     * @return null|string
     */
    public function getScope()
    {
        return $this->properties['scope'];
    }

    /**
     * Get the attributes to be selected by the query operation.
     *
     * @return array|null
     */
    public function getAttributes()
    {
        return $this->properties['attributes'];
    }

    /**
     * Get whether or not paging should be used for the query operation.
     *
     * @return bool|null
     */
    public function getUsePaging()
    {
        return $this->properties['usePaging'];
    }

    /**
     * Set the scope for the LDAP query operation.
     *
     * @param $scope
     * @return $this
     * @throws LdapQueryException
     */
    public function setScope($scope)
    {
        if (!in_array($scope, self::SCOPE)) {
            throw new LdapQueryException(sprintf(
                'Scope type "%s" is invalid. See the QueryOperation::SCOPE[] constant for valid types.', $scope
            ));
        }
        $this->properties['scope'] = $scope;

        return $this;
    }

    /**
     * Set the base DN for the LDAP query operation.
     *
     * @param string $baseDn
     * @return $this
     */
    public function setBaseDn($baseDn)
    {
        $this->properties['baseDn'] = $baseDn;

        return $this;
    }

    /**
     * Set the LDAP filter for the query operation.
     *
     * @param string|OperatorCollection $filter
     * @return $this
     */
    public function setFilter($filter)
    {
        $this->properties['filter'] = $filter;

        return $this;
    }

    /**
     * Set the page size used by the LDAP query operation.
     *
     * @param int $pageSize
     * @return $this
     */
    public function setPageSize($pageSize)
    {
        $this->properties['pageSize'] = $pageSize;

        return $this;
    }

    /**
     * Set the attributes to be selected for the query operation.
     *
     * @param array $attributes
     * @return $this;
     */
    public function setAttributes(array $attributes)
    {
        $this->properties['attributes'] = $attributes;

        return $this;
    }

    /**
     * Set whether or not paging should be used for the query operation.
     *
     * @param bool $paging
     * @return $this
     */
    public function setUsePaging($paging)
    {
        $this->properties['usePaging'] = $paging;

        return $this;
    }

    /**
     * Set the size limit for the number of entries returned from LDAP. When set to 0 this means no limit.
     *
     * @param int $sizeLimit
     * @return $this
     */
    public function setSizeLimit($sizeLimit)
    {
        $this->properties['sizeLimit'] = $sizeLimit;

        return $this;
    }

    /**
     * Get the size limit for the number of entries returned from LDAP. When set to 0 this means no limit.
     *
     * @return int
     */
    public function getSizeLimit()
    {
        return $this->properties['sizeLimit'];
    }

    /**
     * {@inheritdoc}
     */
    public function getArguments()
    {
        $args = [
            $this->properties['baseDn'],
            $this->getLdapFilter(),
            $this->properties['attributes'],
            0,
        ];
        if (empty($args[1])) {
            throw new LdapQueryException('The filter for the LDAP query cannot be empty.');
        }
        
        if ($this->properties['sizeLimit'] && !$this->properties['usePaging']) {
            array_push($args, $this->properties['sizeLimit']);
        }
        
        return $args;
    }

    /**
     * {@inheritdoc}
     */
    public function getLdapFunction()
    {
        return $this->functionMap[$this->properties['scope']];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Query';
    }

    /**
     * {@inheritdoc}
     */
    public function getLogArray()
    {
        return $this->mergeLogDefaults([
            'Filter' => $this->getLdapFilter(),
            'Base DN' => $this->properties['baseDn'],
            'Attributes' => implode(',', $this->properties['attributes']),
            'Scope' => $this->properties['scope'],
            'Use Paging' => var_export($this->properties['usePaging'], true),
            'Page Size' => $this->properties['pageSize'],
            'Size Limit' => $this->properties['sizeLimit'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheKey()
    {
        $key = CacheItem::TYPE['OPERATION_RESULT'];
        foreach ($this->controls as $control) {
            $key .= implode('', $control->toArray());
        }
        $key .= $this->server
            .$this->properties['baseDn']
            .$this->getLdapFilter()
            .$this->properties['usePaging']
            .$this->properties['pageSize']
            .$this->properties['sizeLimit']
            .$this->properties['scope']
            .implode('', $this->properties['attributes']);

        return md5($key);
    }

    /**
     * Make sure to clone an OperatorCollection instance.
     */
    public function __clone()
    {
        if ($this->properties['filter'] instanceof OperatorCollection) {
            $this->properties['filter'] = clone $this->properties['filter'];
        }
    }
    
    /**
     * @return string
     */
    protected function getLdapFilter()
    {
        $filter = $this->properties['filter'];
        if ($filter instanceof OperatorCollection) {
            $filter = $filter->toLdapFilter();
        }
        
        return $filter;
    }
}
