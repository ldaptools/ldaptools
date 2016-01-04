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
use LdapTools\Factory\LdapObjectSchemaFactory;
use LdapTools\Factory\HydratorFactory;
use LdapTools\Operation\AddOperation;
use LdapTools\Resolver\ParameterResolver;
use LdapTools\Schema\LdapObjectSchema;
use LdapTools\Utilities\LdapUtilities;

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
     * @var HydratorFactory
     */
    protected $hydratorFactory;

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
     * @param LdapConnectionInterface $connection
     * @param LdapObjectSchemaFactory $schemaFactory
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(LdapConnectionInterface $connection, LdapObjectSchemaFactory $schemaFactory, EventDispatcherInterface $dispatcher)
    {
        $this->connection = $connection;
        $this->schemaFactory = $schemaFactory;
        $this->dispatcher = $dispatcher;
        $this->hydratorFactory = new HydratorFactory();
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
            throw new \InvalidArgumentException(
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
        $hydrator = $this->hydratorFactory->get(HydratorFactory::TO_ARRAY);
        foreach ($this->getAllParameters() as $parameter => $value) {
            $hydrator->setParameter($parameter, $value);
        }

        if (!empty($this->schema)) {
            $hydrator->setLdapObjectSchemas($this->schema);
        }
        $hydrator->setLdapConnection($this->connection);
        $hydrator->setOperationType(AttributeConverterInterface::TYPE_CREATE);
        $attributes = $hydrator->hydrateToLdap($this->attributes);

        $dn = $this->getDnToUse($attributes);
        $this->connection->execute((new AddOperation($dn, $attributes))->setServer($this->server));
        $this->triggerAfterCreationEvent($dn);
    }

    /**
     * Builds the DN based off of the "name" attribute. The name attribute should be mapped to the "cn" attribute in
     * pretty much all cases except for creating an OU object. Then the "name" attribute should be mapped to "ou".
     *
     * @param array $attributes
     * @return string
     */
    protected function getDnToUse(array $attributes)
    {
        // If the DN was explicitly set, just return it.
        if ($this->dn) {
            return $this->dn;
        } elseif (!$this->schema) {
            throw new \LogicException("You must explicitly set the DN or specify a schema type.");
        } elseif (!$this->schema->hasAttribute('name')) {
            throw new \LogicException(
                'To create an object you must specify the name attribute in the schema. That attribute should typically'
                .' map to the "cn" attribute, as it will use that as the base of the distinguished name.'
            );
        } elseif (empty($this->container)) {
            throw new \LogicException('You must specify a container or OU to place this LDAP object in.');
        }
        $attribute = $this->schema->getAttributeToLdap('name');

        return $attribute.'='.LdapUtilities::escapeValue($attributes[$attribute], null, LDAP_ESCAPE_DN).','.$this->getContainerValue();
    }

    /**
     * Merges the explicitly set parameters with the default ones.
     *
     * @return array
     */
    protected function getAllParameters()
    {
        $parameters = $this->parameters;
        $parameters['_domainname_'] = $this->connection->getConfig()->getDomainName();

        $rootDse = $this->connection->getRootDse();
        // Would this ever not be true? I'm unable to find any RFCs specifically regarding Root DSE structure.
        if ($rootDse->has('defaultNamingContext')) {
            $parameters['_defaultnamingcontext_'] = $rootDse->get('defaultNamingContext');
        }

        return $parameters;
    }

    /**
     * Get the container to use while resolving any parameters it might have.
     *
     * @return string
     */
    protected function getContainerValue()
    {
        $resolver = new ParameterResolver(['container' => $this->container], $this->getAllParameters());

        return $resolver->resolve()['container'];
    }

    /**
     * Trigger a LDAP object before creation event.
     */
    protected function triggerBeforeCreationEvent()
    {
        $event = new LdapObjectCreationEvent(Event::LDAP_OBJECT_BEFORE_CREATE);
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
     * @param string $dn The final DN of the created object.
     */
    protected function triggerAfterCreationEvent($dn)
    {
        $event = new LdapObjectCreationEvent(Event::LDAP_OBJECT_AFTER_CREATE);
        $event->setData((new ParameterResolver($this->attributes, $this->getAllParameters()))->resolve());
        $event->setContainer($this->getContainerValue());
        $event->setDn($dn);

        $this->dispatcher->dispatch($event);
    }
}
