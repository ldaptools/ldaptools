# Events
--------

* [Adding Event Listeners](#adding-event-listeners)
* [Adding Event Subscribers](#adding-event-subscribers)
* [Event Names](#the-event-names)
* [The LDAP Object Creation Event](#the-ldap-object-creation-event)
* [The LDAP Object Deletion and Modification Events](#the-ldap-object-deletion-and-modification-events)
* [The LDAP Object Restore Event](#the-ldap-object-restore-event)
* [The LDAP Object Schema Event](#the-ldap-object-schema-event)
* [The LDAP Authentication Event](#the-ldap-authentication-event)
* [The LDAP Operation Event](#the-ldap-operation-event)
* [Using a Custom Event Dispatcher](#using-a-customspecific-event-dispatcher)

There are many events you can hook into to extend, and take action on, many common tasks (LDAP deletion, creation, modification, etc).
This is done by using a event dispatcher system (the `symfony/event-dispatcher` by default).
 
## Adding Event Listeners
-------------------------

To take action on a specific event you can add a listener that will fire when an event is triggered:

```php
use LdapTools\Event\Event;
use LdapTools\Event\LdapObjectEvent;

// Using the LdapManager instance add an event that will get fired before modification of a LDAP object.
$ldap->getEventDispatcher()->addListener(Event::LDAP_OBJECT_BEFORE_MODIFY, function(LdapObjectEvent $event) {
    if ($event->getLdapObject()->hasFirstName('Chad')) {
        $event->getLdapObject()->setFirstName('foo');
    }
});
```

## Adding Event Subscribers
---------------------------

You can also add an event subscriber to respond to events. This allows you to encapsulate your listeners within the 
context of a single class. All event subscribers must implement `LdapTools\Event\EventSubscriberInterface`, which needs
a single method called `getSubscribedEvents()` that should return an associated array containing keys of an event
name and values that correspond to the method in the class they should call.

For example, first define a class for your subscriber:

```php
use LdapTools\Event\EventSubscriberInterface;
use LdapTools\Event\Event;
use LdapTools\Event\LdapObjectEvent;
use LdapTools\Object\LdapObjectType;

class UserSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            Event::LDAP_OBJECT_BEFORE_DELETE => 'beforeDelete',
            Event::LDAP_OBJECT_BEFORE_MODIFY => 'beforeModify',
        ];
    }
    
    public function beforeDelete(LdapObjectEvent $event)
    {
        $ldapObject = $event->getLdapObject();
        
        if ($ldapObject->getType() == LdapObjectType::USER) {
            // Do some custom stuff before deletion...
        }
    }
    
    public function beforeModify(LdapObjectEvent $event)
    {
        $ldapObject = $event->getLdapObject();
        
        if ($ldapObject->getType() == LdapObjectType::USER) {
            // Do some custom stuff before modification...
        }
    }
}
```

Then add that subscriber to the event dispatcher in LdapTools:

```php
// Using the LdapManager instance add the event subscriber you created above.
$ldap->getEventDispatcher()->addSubscriber(new UserSubscriber());
```

## The Event Names
------------------

All of the event names are defined as constants in the `LdapTools\Event\Event` class. You can use those constants to
add events by name easier. Below is a table listing of the events and when they are triggered. Please note: The Constants
are located in `LdapTools\Event\Event` and the "Event Used" is the event class passed to the listener. The event classes
are located in the `LdapTools\Event` namespace.

| Event Name  | Constant | Event Used | Description |
| --------------- | -------------- | ---------- | ---------- |
| ldap.object.before_modify | `LDAP_OBJECT_BEFORE_MODIFY` | `LdapObjectEvent` | Triggered before an object is modified in LDAP. Only triggered when using the `persist()` method of the `LdapManager`. |
| ldap.object.after_modify | `LDAP_OBJECT_AFTER_MODIFY` | `LdapObjectEvent` | Triggered after an object is modified in LDAP. Only triggered when using the `persist()` method of the `LdapManager`. |
| ldap.object.before_delete | `LDAP_OBJECT_BEFORE_DELETE` | `LdapObjectEvent` | Triggered before an object is deleted from LDAP. Only triggered when using the `delete()` method of the `LdapManager`. |
| ldap.object.after_delete | `LDAP_OBJECT_AFTER_DELETE` | `LdapObjectEvent` | Triggered after an object is deleted from LDAP. Only triggered when using the `delete()` method of the `LdapManager`. |
| ldap.object.before_create | `LDAP_OBJECT_BEFORE_CREATE` | `LdapObjectCreationEvent` | Triggered before an object is created in LDAP. Only triggered when using the `createLdapObject()` methods of the `LdapManager`. |
| ldap.object.after_create | `LDAP_OBJECT_AFTER_CREATE` | `LdapObjectCreationEvent` | Triggered after an object is created in LDAP. Only triggered when using the `createLdapObject()` methods of the `LdapManager`. |
| ldap.object.before_move | `LDAP_OBJECT_BEFORE_MOVE` | `LdapObjectMoveEvent` | Triggered before an object is moved in LDAP. Only triggered when using the `move()` method of the `LdapManager`. |
| ldap.object.after_move | `LDAP_OBJECT_AFTER_MOVE` | `LdapObjectMoveEvent` | Triggered after an object is moved in LDAP. Only triggered when using the `move()` method of the `LdapManager`. |
| ldap.object.before_restore | `LDAP_OBJECT_BEFORE_RESTORE` | `LdapObjectRestoreEvent` | Triggered before an object is restored in LDAP. Only triggered when using the `restore()` method of the `LdapManager`. |
| ldap.object.after_restore | `LDAP_OBJECT_AFTER_RESTORE` | `LdapObjectRestoreEvent` | Triggered after an object is restored in LDAP. Only triggered when using the `restore()` method of the `LdapManager`. |
| ldap.schema.load | `LDAP_SCHEMA_LOAD` | `LdapObjectSchemaEvent` | Triggered when a LDAP object type schema is parsed, loaded, and before it gets cached. This allows you to modify the schema without creating your own file. |
| ldap.authentication.before | `LDAP_AUTHENTICATION_BEFORE` | `LdapAuthenticationEvent` | Triggered before an LDAP authentication operation. Allows you to get the operation details before it is sent. |
| ldap.authentication.after | `LDAP_AUTHENTICATION_AFTER` | `LdapAuthenticationEvent` | Triggered after an LDAP authentication operation. Allows you to get the result and any error messages/codes. |
| ldap.operation.execute.before | `LDAP_OPERATION_EXECUTE_BEFORE` | `LdapOperationEvent` | Triggered before any LDAP operation is executed. Allows getting the operation and connection prior to execution. |
| ldap.operation.execute.after | `LDAP_OPERATION_EXECUTE_AFTER` | `LdapOperationEvent` | Triggered after any LDAP operation is executed. Allows getting the operation and connection after execution. |

## The LDAP Object Creation Event
---------------------------------

The LDAP object creation events functions slightly different than the rest. The event object has setters for the `LDAP_OBJECT_BEFORE_CREATE`
event so you can modify the container/attributes/DN before they are sent to LDAP. For example:
 
```php
use LdapTools\Event\Event;
use LdapTools\Event\LdapObjectCreationEvent;
 
$ldap->getEventDispatcher()->addListener(Event::LDAP_OBJECT_BEFORE_CREATE, function(LdapObjectCreationEvent $event) {
    $attributes = $event->getData();
    $container = $event->getContainer();
    $dn = $event->getDn();
    $type = $event->getType();
     
    if ($type === 'user' && !isset($attributes['title'])) {
        $attributes['title'] = "Pizza Maker";
        $event->setAttributes($attributes);
    }
     
    // Can also explicitly set the DN or container here too...
    // $event->setDn($dn);
    // $event->setContainer($container)
});
```
 
## The LDAP Object Move Event
-----------------------------

The LDAP object move event has setters for `LDAP_OBJECT_BEFORE_MOVE` so you can modify the container/OU before the 
object is actually moved. You can also use the event's `getContainer()` method to check where the move was destined for.
For example:
 
```php
use LdapTools\Event\Event;
use LdapTools\Event\LdapObjectMoveEvent;
 
$ldap->getEventDispatcher()->addListener(Event::LDAP_OBJECT_BEFORE_MOVE, function(LdapObjectMoveEvent $event) {
    $user = $event->getLdapObject();
    $container = $event->getContainer(); // Check where the move is going to put them if you want
     
    // Check the user object and change the location that the move will place them
    if ($user->firstName == 'Joe') {
        $event->setContainer('ou=disabled,dc=example,dc=com');
    }
});
```
 
## The LDAP Object Deletion and Modification Events
---------------------------------------------------
 
Both the deletion and modification events let you retrieve the LDAP object being processed and add your own custom logic:
  
```php
use LdapTools\Event\Event;
use LdapTools\Event\LdapObjectEvent;
  
// Check some stuff before the changes are actually saved to LDAP...
$ldap->getEventDispatcher()->addListener(Event::LDAP_OBJECT_BEFORE_MODIFY, function(LdapObjectEvent $event) {
    $user = $event->getLdapObject();
    // ...
});
```

The same `getLdapObject()` method used above is valid for deletion events as well.

## The LDAP Object Restore Event
-----------------------------

The LDAP object restore event has setters for `LDAP_OBJECT_BEFORE_RESTORE` so you can modify the container/OU before the 
object is actually restored. You can also use the event's `getContainer()` method to check where the restored object is
set to go. However, it may be null if no location was explicitly defined. You can also use the `getLdapObject()` method
of the event to check the LDAP object for a `lastKnownLocation` value. For example:
 
```php
use LdapTools\Event\Event;
use LdapTools\Event\LdapObjectRestoreEvent;
 
$ldap->getEventDispatcher()->addListener(Event::LDAP_OBJECT_BEFORE_RESTORE, function(LdapObjectRestoreEvent $event) {
    $ldapObject = $event->getLdapObject();
    $container = $event->getContainer();
     
    if (!$container && $ldapObject->has('lastKnownLocation')) {
        echo "Location: ".$ldapObject->get('lastKnownLocation');
        // Do some other stuff...
    }
});
```

## The LDAP Object Schema Event
-------------------------------

When you hook into the LDAP Object Schema event you are given the ability to directly modify whatever schema object
is being loaded before it is actually used. Using this you can directly modify many settings without creating your
own schema file: attribute mappings, default attributes to select, default container for objects on creation, etc:

```php
use LdapTools\Event\Event;
use LdapTools\Event\LdapObjectSchemaEvent;
use LdapTools\Object\LdapObjectType;
 
$ldap->getEventDispatcher()->addListener(Event::LDAP_SCHEMA_LOAD, function(LdapObjectSchemaEvent $event) {
    $schema = $event->getLdapObjectSchema();

    // Only modify the 'user' schema type, ignore the others for this listener...
    if ($schema->getObjectType() !== LdapObjectType::USER) {
        return;
    }
     
    // Have your own custom LDAP Object Repository class? Set it using the full class name.
    $schema->setRepository('\Acme\Demo\UserRepository');
     
    // Want to get some additional default attributes selected on queries?
    $select = $schema->getAttributesToSelect();
    $select[] = 'upn';
    $select[] = 'groups';
    $schema->setAttributesToSelect($select);
     
    // Set these users to always go to a default OU when you create them...
    $schema->setDefaultContainer("OU=Employees,DC=example,DC=local");
});
```

## The LDAP Authentication Event
--------------------------------

The LDAP authentication event allows you to retrieve the operation details using `getOperation()`. You can also retrieve
the LDAP response to the authentication operation using the `getResponse()` method. That will only return a response in 
the `ldap.authentication.after` event.
 
```php
use LdapTools\Event\Event;
use LdapTools\Event\LdapAuthenticationEvent;
 
$ldap->getEventDispatcher()->addListener(Event::LDAP_AUTHENTICATION_BEFORE, function(LdapAuthenticationEvent $event) {
    $operation = $event->getOperation();
    
    // The setters for both the username/password can be called as well to modify the operation...
    echo $operation->getUsername(); // The username to be authenticated.
    echo $operation->getPassword(); // The password for the username. 
});

$ldap->getEventDispatcher()->addListener(Event::LDAP_AUTHENTICATION_AFTER, function(LdapAuthenticationEvent $event) {
    $operation = $event->getOperation();
    $response = $event->getResponse();
    
    if (!$response->isAuthenticated()) {
        echo "User '".$operation->getUsername()."' failed to login:".$response->getErrorMessage();
    }
});
```

## The LDAP Operation Event
--------------------------------

The LDAP operation event lets you get the operation object with `getOperation()` and the LDAP connection object by
calling `getConnection`. This allows you to take custom action against any LDAP operation before and after it is 
executed (add, delete, modify, query, etc). You can also modify parts of the operation object before it is actually 
executed.
 
```php
use LdapTools\Event\Event;
use LdapTools\Event\LdapOperationEvent;
use LdapTools\Operation\DeleteOperation;
 
$ldap->getEventDispatcher()->addListener(Event::LDAP_OPERATION_EXECUTE_BEFORE, function(LdapOperationEvent $event) {
    $operation = $event->getOperation();
    $connection = $event->getConnection();
    
    if ($operation instanceof DeleteOperation) {
        // ...
    }
});
``` 
 
## Using a Custom/Specific Event Dispatcher
-------------------------------------------

To use a specific Event Dispatcher (which must implement `\LdapTools\Event\EventDispatcherInterface`) you must set it in
the configuration before constructing the LdapManager class:

```php
use LdapTools\Event\SymfonyEventDispatcher;
use LdapTools\LdapManager;
use LdapTools\Configuration;

# Load your overall config
$config = new Configuration();

# ... add/set domain configuration, load from YML, etc...

# Add the event dispatcher to the config
$dispatcher = new SymfonyEventDispatcher();
$config->setEventDispatcher($dispatcher);

$ldap = new LdapManager($config);
```

If one is not explicitly defined it will instantiate a default event dispatcher (`\LdapTools\Event\SymfonyEventDispatcher`).
