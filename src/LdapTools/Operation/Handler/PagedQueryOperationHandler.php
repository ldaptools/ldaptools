<?php

namespace LdapTools\Operation\Handler;

use LdapTools\Connection\LdapConnection;
use LdapTools\Connection\PageControl;
use LdapTools\Exception\LdapConnectionException;
use LdapTools\Operation\LdapOperationInterface;
use LdapTools\Operation\QueryOperation;

class PagedQueryOperationHandler implements OperationHandlerInterface  {
    /**
     * Internal paging control data
     */
    protected $pagingControlList = [];

    use OperationHandlerTrait {
        setOperationDefaults as parentSetDefaults;
    }

    public function __construct() {}

    /**
     * {@inheritdoc}
     */
    public function execute(LdapOperationInterface $operation)
    {

        if ($this->removePagingOperationIfFinished($operation)) {
            return [];
        }

        $allEntries = [];

        /** @var QueryOperation $operation */
        $this->paging($operation)['pageControl']->setIsEnabled($this->shouldUsePaging($operation));
        if (!$this->paging($operation)['pageControl']->isActive()) {
            $this->paging($operation)['pageControl']->start($operation->getPageSize(), $operation->getSizeLimit());
        }
        $this->paging($operation)['pageControl']->next();

        $result = @call_user_func(
            $operation->getLdapFunction(),
            $this->paging($operation)['connection']->getResource(),
            ...$operation->getArguments()
        );
        $allEntries = $this->processSearchResult($this->paging($operation)['connection'], $result, $allEntries);

        $this->paging($operation)['pageControl']->update($result);

        if (!$this->paging($operation)['pageControl']->isActive()) {
            $this->paging($operation)['pageControl']->end();
            $this->markPagingOperationFinished($operation);
        }
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
     * @param LdapConnection $connection
     * @param resource $result
     * @param array $allEntries
     * @return array
     * @throws LdapConnectionException
     */
    protected function processSearchResult(LdapConnection $connection, $result, array $allEntries)
    {
        if (!$result) {
            throw new LdapConnectionException(sprintf(
                'LDAP search failed. Diagnostic message: "%s"',
                $this->connection->getDiagnosticMessage()
            ));
        }

        $entries = @ldap_get_entries($connection->getResource(), $result);
        if (!$entries) {
            return $allEntries;
        }
        $allEntries['count'] = isset($allEntries['count']) ? $allEntries['count'] + $entries['count'] : $entries['count'];
        unset($entries['count']);

        return array_merge($allEntries, $entries);
    }

    /**
     * Get a key for the operation. This key will be used to store pagination data for the operation
     * such as the PageControl and LdapConnection object.
     *
     * @param LdapOperationInterface $operation the operation to generate the key for
     * @return string the generated key
     */
    protected function getPagingKey(LdapOperationInterface $operation)
    {
        $keyArray = [$operation->getServer(), $operation->getLdapFunction()] + $operation->getArguments();
        return serialize($keyArray);
    }

    /**
     * Returns paging data for the operation. This data is reused if the operation is the same.
     *
     * @param LdapOperationInterface $operation the operation that needs pagination
     * @return array containing the PageControl and LdapConnection for the operation such as
     * ['pageControl' => PageControl, 'connection' => LdapConnection]
     */
    protected function paging(LdapOperationInterface $operation)
    {
        $pagingKey = $this->getPagingKey($operation);
        if (!isset($this->pagingControlList[$pagingKey])) {
            $clonedConnection = clone $this->connection;
            $clonedConnection->close();
            $clonedConnection->connect();
            $this->pagingControlList[$pagingKey] = [
                'pageControl' => new PageControl($clonedConnection),
                'connection' => $clonedConnection
                ];
        }
        return $this->pagingControlList[$pagingKey];
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

    /**
     * Mark the pagination as finished for the operation. This will set the 'pageControl' as false
     * but keep the connection in order to close it later.
     *
     * @param LdapOperationInterface the operation that will be marked as finished
     */
    protected function markPagingOperationFinished(LdapOperationInterface $operation)
    {
        $pagingKey = $this->getPagingKey($operation);
        $this->pagingControlList[$pagingKey]['pageControl'] = false;
    }

    /**
     * Remove the paging data if the operation has finished. This will close the connection if
     * needed. If the operation hasn't finished, this method won't do anything
     *
     * @param LdapOperationInterface $operation the finished operation marked with the
     * 'markPagingOperationFinished' method. You can pass unfinished operations, but it won't have
     * any effect
     * @return bool true if the data has been removed, false otherwise (if the operation hasn't
     * finished, it will return false since the data is still there)
     */
    protected function removePagingOperationIfFinished(LdapOperationInterface $operation)
    {
        $pagingKey = $this->getPagingKey($operation);
        if (isset($this->pagingControlList[$pagingKey]) && $this->pagingControlList[$pagingKey]['pageControl'] === false) {
            $this->pagingControlList[$pagingKey]['connection']->close();
            unset($this->pagingControlList[$pagingKey]);
            return true;
        }
        return false;
    }
}
