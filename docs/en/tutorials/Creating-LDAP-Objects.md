# Creating LDAP Objects
-----------------------

* [Using Parameters](#using-parameters-in-attribute-values)
* [Creator Methods](#ldapobjectcreator-methods)

The `LdapObjectCreator` class provides an easy method for creating LDAP objects. It can accessed directly from the LDAP
Manager and provides shortcuts and helpers for creating Users, Groups, Contacts, and Computers using the default schema.
It also supports setting parameters within attribute values to avoid repetition.

To get an instance of the object creator class and create some objects:

```php
use LdapTools\Object\LdapObjectType;

// Optionally pass an object type directly when getting the creator instance...
$ldap->createLdapObject(LdapObjectType::USER)
    ->with(['username' => 'foo', 'password' => 'bar'])
    ->in('dc=example,dc=local')
    ->execute();

$ldapObject = $ldap->createLdapObject();

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
    
// Create an object by passing the schema type name to the create method...
$ldapObject->create('user')
    ->in('dc=example,dc=local')
    ->with(['username' => 'Foo', 'password' => 'correct-horse-battery-staple'])
    ->execute();

```

## Using Parameters in Attribute Values
---------------------------------------

When you create a generic `user`, `group`, `contact`, etc there are very few values you need to explicitly set for them
to be created. Part of the reason for this is that the schema is leveraging parameters to fill in values for the rest of
the required attributes. You can use parameters to fill in attribute values based off other attributes, or any value you
want. For instance:

```php
$ldapObject = $ldap->createLdapObject();

$ldapObject->createContact()
    ->in('%OUPath%,%_defaultnamingcontext_%')
    ->with([
        'name' => '%firstname% %lastname%',
        'firstName' => 'Some',
        'lastName' => 'Guy',
        'emailAddress' => '%firstname%.%lastname%@%somedomain%'
    ])
    ->setParameter('somedomain','foo.bar')
    ->setParameter('OUPath','OU=Sales,OU=Departments')
    ->execute();
```

This contains several parameters. All parameters start and end with a percentage symbol. The `%firstname%` and `%lastname%`
parameters will populate their value with their corresponding attribute name. However, `%somedomain%` and `%OUPath%` do
not correspond to a known attribute, so the parameter must be defined. It will be filled in with `foo.bar` and
`OU=Sales,OU=Departments`, respectively.

There is also a special parameter for `%_domainname_%` and `%_defaultnamingcontext_%` when creating LDAP objects. The 
`%_domainname_%` will resolve to the fully qualified domain name of the connection in the current context (ie. 
`example.com`). The `%_defaultnamingcontext_%` will resolve the the base distinguished name of the domain (ie. 
`dc=example,dc=com`).

## LdapObjectCreator Methods

This class provides a few methods to make it easier to create LDAP objects.

------------------------
#### in($container)

The `in` method specifies the container/OU you want to place the LDAP object. It should be a string with the common LDAP
distinguished name form (ie. `ou=users,dc=mydomain,dc=com`).

You can also specify a default location all objects of a certain type by defining the `default_container` directive in 
your schema (see [the schema configuration reference](../reference/Schema-Configuration.md)). If you define that can omit this method.
You can also place parameters in this value that will be resolved upon creation (ie. `OU=%department%,%EmployeeOU%,DC=example,DC=com`).
You can use LDAP object attributes for parameters in this string as well.

------------------------
#### with($attributes)

This specifies the attributes and values you would like for the object to have once created in LDAP. All attribute values are
converted and renamed to their proper LDAP form based on your schema. To check the name mappings and expected value types
see the [default schema attributes](../reference/Default-Schema-Attributes.md) documentation.

------------------------
#### setDn($container)

This method allows you to explicitly set the distinguished name for the object you are creating. Typically you should
not have to call this. The DN is determined automatically based off the `name` attribute in your schema, and the
location you specified with the `in($container)` method.

------------------------
#### setParameter($name, $value)

This allows you to set any parameter you want that you can later use within an attribute value to have it resolve to the
parameter value. See the full explanation of parameters near the start of this document. If you simply want to use the
value of a separate attribute name then there is no reason to set it here, as all attribute names you define values in
are also available as parameters automatically.

------------------------
#### createUser()

This specifies that the resulting object should be a user LDAP object type.

------------------------
#### createGroup()

This specifies that the resulting object should be a group LDAP object type.

------------------------
#### createOU()

This specifies that the resulting object should be an OU LDAP object type.

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
#### setServer($server)

This lets you set the LDAP server that the LDAP object will be initially created on. This switches the connections LDAP
server during execution and then switches back to the LDAP server you were originally connected to afterwards.

------------------------
#### execute()

Takes all of your set attributes, parameters, OU location, etc and adds the object to LDAP. If an issue is encountered
while adding to LDAP it will throw an exception...

```php
use LdapTools\Exception\LdapConnectionException;

//...

$ldapObject = $ldapManager->createLdapObject();

// Creating a user account (enabled by default) with a few group memberships
try {
    $object->createUser()
        ->in('cn=Users,dc=example,dc=local')
        ->with(['username' => 'jsmith', 'password' => '12345', 'groups' => ['Employees', 'IT Staff', 'VPN Users']])
        ->execute();
} catch (LdapConnectionException $e) {
    echo "Failed to add user!".PHP_EOL;
    echo $e->getMessage().PHP_EOL;
}
```
