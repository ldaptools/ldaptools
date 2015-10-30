# Events
--------

There are many events you can hook into to extend, and take action on, many common tasks (LDAP deletion, creation, modification, etc).
This is done by using a event dispatcher system (the `symfony/event-dispatcher` by default).
 
## Adding Event Listeners
-------------------------

To take action on a specific event you can add a listener that will fire when an event is triggered:

```php
use LdapTools\Event\Event;
use LdapTools\Event\LdapObjectEvent;

// Using the LdapManager instance add an event that will get fired before modification of a LDAP object.
$ldap->getEventDispatcher()->addListener(Event::TYPE_LDAP_OBJECT_BEFORE_MODIFY, function(LdapObjectEvent $event) {
    if ($event->getLdapObject()->hasFirstName('Chad')) {
        $event->getLdapObject()->setFirstName('foo');
    }
});
```

## Adding Event Subscribers
---------------------------

You can also add an event subscriber to respond to events. This allows you to encapsulate your listeners within the 
context of a single class. All event subscribers must implement `LdapTools\Event\EventSubscriberInterface`, which is
simple a single method called `getSubscribedEvents()` that should return an associated array containing keys of an event
name and values that correspond to the method in the class they should call.

For example, first define a class for your subscriber:

```php
use LdapTools\Event\EventSubscriberInterface;
use LdapTools\Event\Event;
use LdapTools\Event\LdapObjectEvent;
use LdapTools\LdapObjectType;

class UserSubscriber implements EventSubscriberInterface
{
    public function getSubscribedEvents()
    {
        return [
            Event::TYPE_LDAP_OBJECT_BEFORE_DELETE => 'beforeDelete',
            Event::TYPE_LDAP_OBJECT_BEFORE_MODIFY => 'beforeModify',
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
| ldap.schema.load | `LDAP_SCHEMA_LOAD` | `LdapObjectSchemaEvent` | Triggered when a LDAP object type schema is parsed, loaded, and before it gets cached. This allows you to modify the schema without creating your own file. |

## The LDAP Object Creation Event
---------------------------------

The LDAP object creation events functions slightly different than the rest. The event object has setters for the `LDAP_OBJECT_BEFORE_CREATE`
event so you can modify the container/attributes/DN before they are sent to LDAP. For example:
 
 ```php
 use LdapTools\Event\Event;
 use LdapTools\Event\LdapObjectCreationEvent;
 
 $ldap->getEventDispatcher()->addListener(Event::TYPE_LDAP_OBJECT_BEFORE_CREATE, function(LdapObjectCreationEvent $event) {
     $attributes = $event->getData();
     $container = $event->getContainer();
     $dn = $event->getDn();
     
     if (!isset($attributes['title'])) {
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
 
 $ldap->getEventDispatcher()->addListener(Event::TYPE_LDAP_OBJECT_BEFORE_MOVE, function(LdapObjectMoveEvent $event) {
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
  $ldap->getEventDispatcher()->addListener(Event::TYPE_LDAP_OBJECT_BEFORE_MODIFY, function(LdapObjectEvent $event) {
      $user = $event->getLdapObject();
      // ...
  });
  ```

The same `getLdapObject()` method used above is valid for deletion events as well.

## The LDAP Object Schema Event
-------------------------------

When you hook into the LDAP Object Schema event you are given the ability to directly modify whatever schema object
is being loaded before it is actually used. Using this you can directly modify many settings without creating your
own schema file: attribute mappings, default attributes to select, default container for objects on creation, etc:

 ```php
 use LdapTools\Event\Event;
 use LdapTools\Event\LdapObjectSchemaEvent;
 use LdapTools\Object\LdapObjectType;
 
 $ldap->getEventDispatcher()->addListener(Event::TYPE_LDAP_SCHEMA_LOAD, function(LdapObjectSchemaEvent $event) {
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
 