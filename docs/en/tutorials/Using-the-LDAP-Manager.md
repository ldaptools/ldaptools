# Using the LdapManager Class
-----------------------------

* [The LDAP Query Builder](#getting-a-ldapquerybuilder-instance)
* [LDAP Object Repositories](#getting-a-repository-object-for-a-ldap-type)
* [Switching Domains](#switching-domains)
* [The LDAP Connection](#getting-the-ldapconnection)
* [Modifying LDAP Objects](#modifying-ldap-objects)
* [Deleting LDAP Objects](#deleting-ldap-objects)
* [Moving LDAP Objects](#moving-ldap-objects)
* [Restoring LDAP Objects](#restoring-ldap-objects)

The `LdapManager` provides an easy point of access into the different parts of this library after you have setup the
[configuration](../reference/Main-Configuration.md). You can use the `LdapManager` to generate LDAP queries for a
certain domain, get a "Repository" for a specific LDAP type in your schema, switch between domains when you have 
multiple defined in your configuration, and retrieve a `LdapConnection` object for the domain.

### Getting a LdapQueryBuilder Instance
---------------------------------------

You can retrieve a `LdapQueryBuilder` for a specific domain. For example, the below query will select all users with 
the first name 'John', last name starts with a 'S', and whose accounts are enabled.

```php
$query = $ldapManager->buildLdapQuery();

$users = $query
    ->select(['username', 'city', 'state', 'guid'])
    ->fromUsers()
    ->where(
        $query->filter()->eq('enabled', true)
        $query->filter()->eq('firstName', 'John'),
        $query->filter()->startsWith('lastName', 'S'),
    )
    ->getLdapQuery()
    ->getResult();
```

For more information on building LDAP queries, see [the docs for it](./Building-LDAP-Queries.md).

### Getting a Repository Object for a LDAP Type
-----------------------------------------------

A repository object lets you easily query specific attributes for a LDAP object type to retrieve either a single result
or many results. You can also define your own custom repository for a LDAP type to encapsulate an reuse your queries.
 
```php
use LdapTools\Object\LdapObjectType;

$repository = $ldap->getRepository(LdapObjectType::USER);

// Retrieve all users in this repository.
$users = $repository->findAll();

// Retrieve the user that has a specific GUID.
$user = $repository->findOneByGuid('29d46992-a5c4-4dc2-ac51-ac432db2a078');

// Retrieve the user with a specific username.
$user = $repository->findOneByUsername('jsmith');

// Retrieve all users with their city set as Seattle
$users = $repository->findByCity('Seattle');
```

### Switching Domains
---------------------

If you have multiple domains defined in your configuration, you can easily switch the contexts of your calls in the
`LdapManager` by using `switchDomain`:

```php
// Now calls to 'getRepository', 'buildLdapQuery', etc will return objects that execute in the context of this domain.
$query = $ldap->switchDomain('example.local')->buildLdapQuery();

// Will return 'example.local'
$ldap->getDomainContext();

// Switch back to the other domain...
$ldap->switchDomain('foo.bar');
```

### Getting The LdapConnection
------------------------------

The `LdapConnection` is what ultimately executes queries against LDAP. It encapsulates the PHP `ldap_*` functions into
an object oriented form. It has several functions that also may be useful on their own.
 
```php
use LdapTools\Operation\AuthenticationOperation;

$connection = $ldapManager->getConnection();
// Construct a LDAP authentication operation to run against the connection...
$operation = (new AuthenticationOperation())->setUsername('username')->setPassword('password');
// Run the authentication operation against the connection to get the response object...
$response = $connection->execute($operation);

if ($response->isAuthenticated()) {
    echo sprintf("Successfully authenticated %s.".PHP_EOL, $operation->getUsername());
} else {
    echo sprintf(
        "Failed to authenticate %s. (%s) %s.".PHP_EOL,
         $operation->getUsername(),
         $response->getErrorCode(),
         $response->getErrorMessage()
     );
}

// Retrieve an object containing the RootDSE for the domain
$rootDse = $connection->getRootDse();

var_dump($rootDse->toArray());
var_dump($rootDse->getDefaultNamingContext());
```

### Modifying LDAP Objects
--------------------------

Using the `LdapManager` class you can save changes to LDAP users you have searched for back to LDAP using the
`persist($ldapObject)` method.

```php
use LdapTools\Object\LdapObjectType;

$repository = $ldap->getRepository(LdapObjectType::USER);

// Retrieve the user that has a specific GUID.
$user = $repository->findOneByGuid('29d46992-a5c4-4dc2-ac51-ac432db2a078');

// Change some attribute value
$user->setCity('Milwaukee');

// Save the changes back to LDAP using the persist method...
try {
    $ldap->persist($user);
} catch (\Exception $e) {
    echo "Error saving object to LDAP: ".$e->getMessage();
}
```

For more information on modifying LDAP objects [see the docs for it](./Modifying-LDAP-Objects.md).

### Deleting LDAP Objects
--------------------------

Using the `LdapManager` class you can also remove an object from LDAP using the `delete($ldapObject)` method. To 
recursively delete a LDAP object (including anything beneath it) pass `true` to the second parameter. 

A simple deletion:

```php
use LdapTools\Object\LdapObjectType;

$repository = $ldap->getRepository(LdapObjectType::USER);

// Retrieve the user that has a specific GUID.
$user = $repository->findOneByGuid('29d46992-a5c4-4dc2-ac51-ac432db2a078');

// Delete the object from LDAP...
try {
    $ldap->delete($user);
} catch (\Exception $e) {
    echo "Error deleting object from LDAP: ".$e->getMessage();
}
```

Recursively deleting an OU and anything beneath it:

```php
use LdapTools\Object\LdapObjectType;

$repository = $ldap->getRepository(LdapObjectType::OU);

// Retrieve the user that has a specific GUID.
$ou = $repository->findOneByName('Consultants');

// Delete the OU, along with anything beneath it...
try {
    $ldap->delete($ou, true);
} catch (\Exception $e) {
    echo "Error deleting OU: ".$e->getMessage();
}
```

### Moving LDAP Objects
-----------------------

Using the `LdapManager` class you can move an object from one location to another in LDAP. The method for doing this is 
to call `move($ldapObject, $container)`. All this requires is you pass the existing LdapObject and then the new location
of the OU/container in standard DN form:

```php
use LdapTools\Object\LdapObjectType;

$repository = $ldap->getRepository(LdapObjectType::USER);

// Retrieve the user that has a specific GUID.
$user = $repository->findOneByGuid('29d46992-a5c4-4dc2-ac51-ac432db2a078');

// Move the object to a new OU...
try {
    $ldap->move($user, 'ou=Employees,dc=example,dc=local');
} catch (\Exception $e) {
    echo "Error moving object: ".$e->getMessage();
}
```

### Restoring LDAP Objects
-----------------------

Using the `LdapManager` class you can restore a deleted LDAP object. The method for doing this is `restore($ldapObject)`
However, this is currently only supported when you are using Active Directory. This requires that you first search for
the LDAP object you want to restore using the `LdapObjectType::DELETED` type then passing it to the restore method:

```php
use LdapTools\Object\LdapObjectType;

$repository = $ldap->getRepository(LdapObjectType::DELETED);

// Retrieve the deleted LDAP object by its original GUID
$ldapObject = $repository->findOneByGuid('29d46992-a5c4-4dc2-ac51-ac432db2a078');

// Restore the LDAP object to its original location
try {
    $ldap->restore($ldapObject);
} catch (\Exception $e) {
    echo "Unable to restore LDAP object: ".$e->getMessage();
}
```
