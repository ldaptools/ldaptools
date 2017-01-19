<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Object;

use LdapTools\AttributeConverter\AttributeConverterInterface;
use LdapTools\Connection\LdapConnectionInterface;
use LdapTools\Event\Event;
use LdapTools\Event\EventDispatcherInterface;
use LdapTools\Event\LdapObjectCreationEvent;
use LdapTools\Exception\InvalidArgumentException;
use LdapTools\Factory\LdapObjectSchemaFactory;
use LdapTools\Hydrator\OperationHydrator;
use LdapTools\Operation\AddOperation;
use LdapTools\Resolver\ParameterResolver;
use LdapTools\Schema\LdapObjectSchema;

/**
 * Allows for easy creation of LDAP objects.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class LdapObjectCreator
{
    /**
     * @var LdapObjectSchemaFactory
     */
    protected $schemaFactory;

    /**
     * @var LdapConnectionInterface
     */
    protected $connection;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var LdapObjectSchema
     */
    protected $schema;

    /**
     * @var array
     */
    protected $attributes = [];

    /**
     * @var string
     */
    protected $container = '';

    /**
     * @var array Any explicitly set parameter values are stored here.
     */
    protected $parameters = [];

    /**
     * @var string An explicitly set distinguished name.
     */
    protected $dn = '';

    /**
     * @var string A specific server to execute the LDAP object creation against.
     */
    protected $server;

    /**
     * @var OperationHydrator
     */
    protected $hydrator;

    /**
     * @param LdapConnectionInterface $connection
     * @param LdapObjectSchemaFactory $schemaFactory
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(LdapConnectionInterface $connection, LdapObjectSchemaFactory $schemaFactory, EventDispatcherInterface $dispatcher)
    {
        $this->connection = $connection;
        $this->schemaFactory = $schemaFactory;
        $this->dispatcher = $dispatcher;
        $this->hydrator = new OperationHydrator();
    }

    /**
     * Specify the object type to create. Either by its string name type from the schema of the LdapObjectSchema.
     *
     * @param string|LdapObjectSchema $type
     * @return $this
     */
    public function create($type)
    {
        if (!is_string($type) && !($type instanceof LdapObjectSchema)) {
            throw new InvalidArgumentException(
                'You must either pass the schema object type as a string to this method, or pass the schema types '
                . 'LdapObjectSchema to this method.'
            );
        }
        if (!($type instanceof LdapObjectSchema)) {
            $type = $this->schemaFactory->get($this->connection->getConfig()->getSchemaName(), $type);
        }
        $this->schema = $type;
        $this->container = $type->getDefaultContainer();

        return $this;
    }

    /**
     * Shorthand method for creating a generic user type.
     */
    public function createUser()
    {
        $this->create(LdapObjectType::USER);

        return $this;
    }

    /**
     * Shorthand method for creating a group LDAP object.
     */
    public function createGroup()
    {
        $this->create(LdapObjectType::GROUP);

        return $this;
    }

    /**
     * Shorthand method for creating a contact LDAP object.
     */
    public function createContact()
    {
        $this->create(LdapObjectType::CONTACT);

        return $this;
    }

    /**
     * Shorthand method for creating a computer LDAP object.
     */
    public function createComputer()
    {
        $this->create(LdapObjectType::COMPUTER);

        return $this;
    }

    /**
     * Shorthand method for creating an OU LDAP object.
     */
    public function createOU()
    {
        $this->create(LdapObjectType::OU);

        return $this;
    }

    /**
     * Sets the attributes the object will be created with.
     *
     * @param array $attributes
     * @return $this
     */
    public function with(array $attributes)
    {
        $this->attributes = $attributes;

        return $this;
    }

    /**
     * Sets the OU/container the object will be created in.
     *
     * @param $container
     * @return $this
     */
    public function in($container)
    {
        $this->container = $container;

        return $this;
    }

    /**
     * Set an explicit LDAP server to execute against.
     *
     * @param string $server
     * @return $this
     */
    public function setServer($server)
    {
        $this->server = $server;

        return $this;
    }


    /**
     * Get an explicit LDAP server to execute against, if any is set.
     *
     * @return string|null
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * Explicitly set the DN to use when adding it to LDAP.
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
     * Set a value for a specific placeholder in the schema, or in any values you added (ie. Anything enclosed within
     * percentage signs such as %placeholder%).
     *
     * @param string $parameter
     * @param mixed $value
     * @return $this
     */
    public function setParameter($parameter, $value)
    {
        $this->parameters[$parameter] = $value;

        return $this;
    }

    /**
     * Add the object with the selected attributes into LDAP.
     */
    public function execute()
    {
        $this->triggerBeforeCreationEvent();
        $operation = $this->getAddOperation()->setServer($this->server);
        $this->connection->execute($operation);
        $this->triggerAfterCreationEvent($operation);
    }

    /**
     * Get the add operation and take care of the hydration process.
     *
     * @return AddOperation
     */
    protected function getAddOperation()
    {
        $operation = new AddOperation($this->dn, $this->attributes);
        $operation->setLocation($this->container);

        foreach ($this->parameters as $parameter => $value) {
            $this->hydrator->setParameter($parameter, $value);
        }
        $this->hydrator->setLdapObjectSchema($this->schema);
        $this->hydrator->setLdapConnection($this->connection);
        $this->hydrator->setOperationType(AttributeConverterInterface::TYPE_CREATE);

        return $this->hydrator->hydrateToLdap($operation);
    }

    /**
     * Trigger a LDAP object before creation event.
     */
    protected function triggerBeforeCreationEvent()
    {
        $event = new LdapObjectCreationEvent(Event::LDAP_OBJECT_BEFORE_CREATE, $this->schema ? $this->schema->getObjectType() : null);
        $event->setData($this->attributes);
        $event->setContainer($this->container);
        $event->setDn($this->dn);

        $this->dispatcher->dispatch($event);

        $this->attributes = $event->getData();
        $this->container = $event->getContainer();
        $this->dn = $event->getDn();
    }

    /**
     * Trigger a LDAP object after creation event.
     *
     * @param AddOperation $operation
     */
    protected function triggerAfterCreationEvent(AddOperation $operation)
    {
        $event = new LdapObjectCreationEvent(Event::LDAP_OBJECT_AFTER_CREATE, $this->schema ? $this->schema->getObjectType() : null);
        $event->setData((new ParameterResolver($this->attributes, $this->hydrator->getParameters()))->resolve());
        $event->setContainer($operation->getLocation());
        $event->setDn($operation->getDn());

        $this->dispatcher->dispatch($event);
    }
}
