# Using the LdapManager Class
-----------------------------

The `LdapManager` provides an easy point of access into the different parts of this library after you have setup the
[configuration](../reference/main-configuration.md). You can use the `LdapManager` to generate LDAP queries for a
certain domain, get a "Repository" for a specific LDAP type in your schema, switch between domains when you have 
multiple defined in your configuration, and retrieve a `LdapConnection` object for the domain.

### Getting a LdapQueryBuilder Instance
---------------------------------------

You can retrieve a `LdapQueryBuilder` for a specific domain. For example, the below query will select all users with 
the first name 'John', last name starts with a 'S', and whose accounts are not disabled.

```php
$query = $ldapManager->buildLdapQuery();

$users = $query->select(['username', 'city', 'state', 'guid'])
    ->fromUsers()
    ->where(
        $query->filter->eq('firstName', 'John'),
        $query->filter->startsWith('lastName', 'S'),
        // 'accountIsDisabled' is an Active Directory specific filter method. Negate a statement using 'not'.
        $query->filter->not($query->filter->accountIsDisabled())
    )
    ->getLdapQuery()
    ->execute();
```

### Getting a Repository Object for a LDAP Type
-----------------------------------------------

A repository object lets you easily query specific attributes for a LDAP object type to retrieve either a single result
or many results. You can also define your own custom repository for a LDAP type to encapsulate an reuse your queries.
 
```php
use LdapTools\Object\LdapObjectTypes;

$repository = $ldapManager->getRepository(LdapObjectTypes::USER);

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
$ldapManager->switchDomain('example.local');
```

### Getting The LdapConnection
------------------------------

The `LdapConnection` is what ultimately executes queries against LDAP. It encapsulates the PHP `ldap_*` functions into
an object oriented form. It has several functions that also may be useful on their own.
 
```php
$connection = $ldapManager->getConnection();

// Attempt to authenticate a user by username/password combination and get the result as a bool
if ($connection->authenticate('username','password')) {
    echo "Successfully authenticated $username!".PHP_EOL;
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
use LdapTools\Object\LdapObjectTypes;

$repository = $ldapManager->getRepository(LdapObjectTypes::USER);

// Retrieve the user that has a specific GUID.
$user = $repository->findOneByGuid('29d46992-a5c4-4dc2-ac51-ac432db2a078');

// Change some attribute value
$user->setCity('Milwaukee');

// Save the changes back to LDAP using the persist method...
try {
    $ldapManager->persist($user);
} catch (\Exception $e) {
    echo "Error saving object to LDAP: ".$e->getMessage();
}
```

### Deleting LDAP Objects
--------------------------

Using the `LdapManager` class you can also remove an object from LDAP using the `delete($ldapObject)` method.

`persist($ldapObject)` method.

```php
use LdapTools\Object\LdapObjectTypes;

$repository = $ldapManager->getRepository(LdapObjectTypes::USER);

// Retrieve the user that has a specific GUID.
$user = $repository->findOneByGuid('29d46992-a5c4-4dc2-ac51-ac432db2a078');

// Delete the object from LDAP...
try {
    $ldapManager->delete($user);
} catch (\Exception $e) {
    echo "Error deleting object from LDAP: ".$e->getMessage();
}
```
