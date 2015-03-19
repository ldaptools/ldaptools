# Creating LDAP Objects
-----------------------

The `LdapObjectCreator` class provides an easy method for creating LDAP objects. It can accessed directly from the LDAP
Manager and provides shortcuts and helpers for creating Users, Groups, Contacts, and Computers using the default schema.
It also supports setting parameters within attribute values to avoid repetition.

To get an instance of the object creator class and create some objects:

```php
$ldapObject = $ldapManager->createLdapObject();

// Creating a user account (enabled by default)
$ldapObject->createUser()
    ->in('cn=Users,dc=example,dc=local')
    ->with(['username' => 'jsmith', 'password' => '12345'])
    ->execute();

// Create a typical AD global security group...
$ldapObject->createGroup()
    ->in('dc=example,dc=local')
    ->with(['name' => 'Generic Security Group'])
    ->execute();

// Creates a contact user...
$ldapObject->createContact()
    ->in('dc=example,dc=local')
    ->with(['name' => 'Some Guy', 'emailAddress' => 'SomeGuy@SomeDomain.com'])
    ->execute();

// Creates a computer object...
$ldapObject->createComputer()
    ->in('dc=example,dc=local')
    ->with(['name' => 'MYWOKRSTATION'])
    ->execute();
    
// Creates an OU object...
$ldapObject->createOU()
    ->in('dc=example,dc=local')
    ->with(['name' => 'Employees'])
    ->execute();
```

## Using Parameters in Attribute Values
---------------------------------------

When you create a generic `user`, `group`, `contact`, etc there are very few values you need to explicitly set for them
to be created. Part of the reason for this is that the schema is leveraging parameters to fill in values for the rest of
the required attributes. You can use parameters to fill in attribute values based off other attributes, or any value you
want. For instance:

```php
$object->createContact()
    ->in('dc=awesome,dc=local')
    ->with([
        'name' => '%firstname% %lastname%',
        'firstName' => 'Some',
        'lastName' => 'Guy',
        'emailAddress' => '%firstname%.%lastname%@%somedomain%'
    ])
    ->setParameter('somedomain','foo.bar')
    ->execute();
```

This contains 3 parameters. All parameters start and end with a percentage symbol. The `%firstname%` and `%lastname%`
 parameters will populate their value with their corresponding attribute name. However, `%somedomain%` does not 
correspond to a known attribute, so the parameter must be defined. It will be filled in with `foo.bar` as defined.

There is also a special parameter called `%_domainname_%` when creating LDAP objects that will resolve to the fully
qualified domain name of the connection in the current context (ie. `example.com`).

## LdapObjectCreator Methods

This class provides a few methods to make it easier to create LDAP objects.

------------------------
#### in($container)

The `in` method specifies the container/OU you want to place the LDAP object. It should be a string with the common LDAP
distinguished name form (ie. `ou=users,dc=mydomain,dc=com`).

You can also specify a default location all objects of a certain type by defining the `default_container` directive in 
your schema (see [the schema configuration reference](../reference/Schema-Configuration.md)). If you define that can omit this method.

------------------------
#### with($attributes)

This specifies the attributes and values you would like for the object to have once created in LDAP. All attribute values are
converted and renamed to their proper LDAP form based on your schema.

------------------------
#### setDn($container)

This method allows you to explicitly state the distinguished name for the object you are creating. Typically you should
not have to call this. The DN is determined automatically based off the `name` attribute in your schema, and the
location you specified with the `in($container)` method.

------------------------
#### setParameter($name, $value)

This allows you to set any parameter you want that you can later use within an attribute value to have it resolve to the
parameter value. See the full explanation of parameters near the start of this document.

------------------------
#### createUser()

This specifies that the resulting object should be a user LDAP object type.

------------------------
#### createGroup()

This specifies that the resulting object should be a group LDAP object type.

------------------------
#### createOU()

This specifies that the resulting object should be a OU LDAP object type.

------------------------
#### createComputer()

This specifies that the resulting object should be a computer LDAP object type.

------------------------
#### createContact()

This specifies that the resulting object should be a contact LDAP object type.

------------------------
#### create($type)

This allows you to manually specify a LDAP object type from the schema that you would like to create. This is the method
that all the other shorthand `create*` methods actually call. 

------------------------
#### execute()

Takes all of your set attributes, parameters, OU location, etc and adds the object to LDAP. If an issue is encountered
while adding to LDAP it will throw an exception...

```php
use LdapTools\Exception\LdapConnectionException;

//...

$ldapObject = $ldapManager->createLdapObject();

// Creating a user account (enabled by default)
try {
    $object->createUser()
        ->in('cn=Users,dc=example,dc=local')
        ->with(['username' => 'jsmith', 'password' => '12345'])
        ->execute();
} catch (LdapConnectionException $e) {
    echo "Failed to add user!".PHP_EOL;
    echo $e->getMessage().PHP_EOL;
}
```
