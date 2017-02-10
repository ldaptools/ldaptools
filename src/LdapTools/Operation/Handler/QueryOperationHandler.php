<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Operation\Handler;

use LdapTools\Connection\PageControl;
use LdapTools\Exception\LdapConnectionException;
use LdapTools\Operation\LdapOperationInterface;
use LdapTools\Operation\QueryOperation;

/**
 * Handles LDAP query operations.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class QueryOperationHandler implements OperationHandlerInterface
{
    use OperationHandlerTrait {
        setOperationDefaults as parentSetDefaults;
    }

    /**
     * @var PageControl
     */
    protected $paging;

    /**
     * @param PageControl|null $paging
     */
    public function __construct(PageControl $paging = null)
    {
        $this->paging = $paging;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LdapOperationInterface $operation)
    {
        $allEntries = [];

        /** @var QueryOperation $operation */
        $this->paging()->setIsEnabled($this->shouldUsePaging($operation));
        $this->paging()->start($operation->getPageSize(), $operation->getSizeLimit());
        do {
            $this->paging()->next();

            $result = @call_user_func(
                $operation->getLdapFunction(),
                $this->connection->getResource(),
                ...$operation->getArguments()
            );
            $allEntries = $this->processSearchResult($result, $allEntries);

            $this->paging()->update($result);
        } while ($this->paging()->isActive());
        $this->paging()->end();
        @ldap_free_result($result);

        return $allEntries;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(LdapOperationInterface $operation)
    {
        return $operation instanceof QueryOperation;
    }

    /**
     * Gets the base DN for a search based off of the config and then the RootDSE.
     *
     * @return string
     * @throws LdapConnectionException
     */
    protected function getBaseDn()
    {
        if (!empty($this->connection->getConfig()->getBaseDn())) {
            $baseDn = $this->connection->getConfig()->getBaseDn();
        } elseif ($this->connection->getRootDse()->has('defaultNamingContext')) {
            $baseDn = $this->connection->getRootDse()->get('defaultNamingContext');
        } elseif ($this->connection->getRootDse()->has('namingContexts')) {
            $baseDn =  $this->connection->getRootDse()->get('namingContexts')[0];
        } else {
            throw new LdapConnectionException('The base DN is not defined and could not be found in the RootDSE.');
        }

        return $baseDn;
    }

    /**
     * {@inheritdoc}
     */
    public function setOperationDefaults(LdapOperationInterface $operation)
    {
        /** @var QueryOperation $operation */
        if (is_null($operation->getPageSize())) {
            $operation->setPageSize($this->connection->getConfig()->getPageSize());
        }
        if (is_null($operation->getBaseDn())) {
            $operation->setBaseDn($this->getBaseDn());
        }
        if (is_null($operation->getUsePaging())) {
            $operation->setUsePaging($this->connection->getConfig()->getUsePaging());
        }
        $this->parentSetDefaults($operation);
    }

    /**
     * Process a LDAP search result and merge it with the existing entries if possible.
     *
     * @param resource $result
     * @param array $allEntries
     * @return array
     * @throws LdapConnectionException
     */
    protected function processSearchResult($result, array $allEntries)
    {
        if (!$result) {
            throw new LdapConnectionException(sprintf(
                'LDAP search failed. Diagnostic message: "%s"',
                $this->connection->getDiagnosticMessage()
            ));
        }

        $entries = @ldap_get_entries($this->connection->getResource(), $result);
        if (!$entries) {
            return $allEntries;
        }
        $allEntries['count'] = isset($allEntries['count']) ? $allEntries['count'] + $entries['count'] : $entries['count'];
        unset($entries['count']);

        return array_merge($allEntries, $entries);
    }

    /**
     * @return PageControl
     */
    protected function paging()
    {
        if (!$this->paging) {
            $this->paging = new PageControl($this->connection);
        }

        return $this->paging;
    }

    /**
     * Based on the query operation, determine whether paging should be used.
     *
     * @param QueryOperation $operation
     * @return bool
     */
    protected function shouldUsePaging(QueryOperation $operation)
    {
        return ($operation->getUsePaging() && $operation->getScope() != QueryOperation::SCOPE['BASE']);
    }
}
