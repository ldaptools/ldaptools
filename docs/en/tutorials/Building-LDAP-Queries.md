# Building LDAP Queries
-----------------------

The `LdapQueryBuilder` class provides an easy object oriented method of producing LDAP filters of any complexity. Those
familiar with Doctrine's QueryBuilder will find this syntax easy to adapt to, as it is pretty much the same. This class
takes care of escaping all values passed to it when generating the filter.

* [LdapQueryBuilder Methods](#ldapquerybuilder-methods)
* [Filter Method Shortcuts](#filter-method-shortcuts)
* [Using Aliases](#using-aliases)
* [Retrieving Query Results](#ldapquery-methods-to-retrieve-ldap-results)
* [Caching Queries](#caching-queries)

## Generating LDAP Filters Without LdapManager
-----------------------

This class is most easily used in the context of the `LdapManager`, but it is also possible to use on its own if your 
only desire is to generate LDAP filters.

```php
use LdapTools\Query\LdapQueryBuilder;

$lqb = new LdapQueryBuilder();

$filter = $lqb->select('givenName', 'sn', 'l')
    ->where(['objectClass' => 'user'])
    ->andWhere($lqb->filter()->like('sAMAccountName','*smith'))
    ->toLdapFilter();
    
echo "LDAP Filter: ".$filter.PHP_EOL;
```

## Generating Queries When Using the LdapManager
-----------------------

When you call `buildLdapQuery` in the `LdapManager` you will get an instance of the `LdapQueryBuilder` class that knows
all the information about the schema of your domain, and a `LdapConnection` capable of executing the query. With this
information it can do a lot of the heavy lifting to allow it to easily generate any LDAP filter.

```php
$lqb = $ldapManager->buildLdapQuery();

// When no attributes are specifically selected, it will pull a default set defined in the schema.
$users = $lqb->fromUsers()
    ->where(['state' => 'Wisconsin'])
    ->getLdapQuery()
    ->getResult();
    
foreach ($users as $user) {
    foreach ($user->toArray() as $attribute => $value) {
        echo $attribute.' => '.$value.PHP_EOL;
    }
}
```

## LdapQueryBuilder Methods

This class provides many methods that simplify the process of creating complex LDAP filters. The following is a list of
the methods and their general use.

------------------------
#### select($attributes)

The `select` method allows you to choose specifically which attributes you would like to return from the query. Simply
pass an array of attribute names to it that you would like, or a single attribute as a string. In the absence of 
anything passed to it, it will select the default set of attributes for the query as defined for the type in the schema.

To retrieve all attributes defined in the schema you can pass a single wildcard `*` as a selected attribute. In the
absence of a schema doing that will also select all LDAP attributes. To select all LDAP attributes and all schema
attributes for a LDAP object you can pass a double wildcard `**` as a selected attribute.

Attribute names are looked for in the schema to see if they map to specific LDAP attributes.
```php
$lqb->select(['firstName', 'city', 'state', 'sid']);

// Select only a single attribute
$lqb->select('guid');

// Attribute names will always be returned in the case you enter it in, irrespective of how LDAP returns the data.
$lqb->select(['FirstName', 'City', 'State', 'SID']);

// Select all attributes defined in the schema for a LDAP object
$lqb->select('*');

// Select all attributes both in the schema and from LDAP for an object
$lqb->select('**');
```
 
If you want the raw data to be returned from LDAP you can select LDAP attribute names explicitly. You can also include 
schema names at the same time. Attributes selected by their LDAP attribute name will **NOT** have attribute 
conversion done.

```php
// Will return 'objectSid' AND 'sid'
$lqb->select(['givenName', 'l', 'objectSid', 'sid']);
```

------------------------
#### from($ldapType, $alias = null)

The `from` method requires an argument for the LDAP type. This type must be defined in your LDAP schema. Common types
that are in the schema by default include: `user`, `group`, `contact`, `computer`, `ou`. These types are defined as constants
in the `\LdapTools\Object\LdapObjectType` class. Using this method makes the query aware of the attribute name mapping
and converters defined for the type.

```php
use LdapTools\Object\LdapObjectType;

// Search for users
$lqb->from(LdapObjectType::USER);

// Search for computers
$lqb->from(LdapObjectType::COMPUTER);

// Search for users and assign an alias to it
$lqb->from(LdapObjectType::USER, 'u');
```

You can also call the `from()` method dynamically for schema types you have defined:

```php
// Selects from the 'ExchangeServer' schema type.
$lqb->fromExchangeServer();

// Selects from the 'container' schema type, and assigns it an alias of 'c'.
$lqb->fromContainer('c');
```

------------------------
#### fromUsers($alias = null)

A convenience shortcut of the `from` method to select from LDAP `user` types. Optionally pass a string alias name.

```php
// Search for users
$lqb->fromUsers();
```

------------------------
#### fromGroups($alias = null)

A convenience shortcut of the `from` method to select from LDAP `group` types. Optionally pass a string alias name.

```php
// Search for groups
$lqb->fromGroups();
```

------------------------
#### fromOUs($alias = null)

A convenience shortcut of the `from` method to select from LDAP `ou` types. Optionally pass a string alias name.

```php
// Search for OUs
$lqb->fromOUs();
```

------------------------
#### where(...$statements)

This method encapsulates its arguments into a logical 'AND' statement. You can either pass a simple array of attributes
and values that must be met, or any number of filter operator statements.

```php
// Pass a simple array of attributes => values. These attributes must be equal to these values.
$lqb->where(['firstName' => 'Timmy', 'state' => 'Wyoming']);

// Pass filter operator statements instead. It will take care of attribute value conversions as well.
$lqb->where($lqb->filter()->gte('created', new \DateTime('5-4-2000'));

// A more complex series of statements
$lqb->where(
    $lqb->filter()->neq('firstName', 'Jimbo'),
    $lqb->filter()->bOr(
        $lqb->filter()->eq('lastName', 'Dodgson'), 
        $lqb->filter()->eq('lastName', 'Venkman')
    )
);
```

------------------------
#### andWhere(...$statements)

This method is the same as the `where` method. It encapsulates its arguments into a logical 'AND' statement. Statements
passed will be added to the same logical 'AND' statement that the `where` method created.

```php
// This adds to the existing and statement...
$lqb->andWhere(['lastName' => 'Smith']);
```

------------------------
#### orWhere(...$statements)

This method is the same as the `where` method, but it will instead encapsulate any passed arguments into a logical 'OR'
statement.

```php
// This creates a separate OR statement...
$lqb->orWhere(['department' => 'IT', 'department' => 'Marketing']);
```

------------------------
#### add(...$statements)

This is a low-level method that will take any object that is an instance of a `BaseOperator` and add it to the query.
The shorthand `$lqb->filter()` methods is what does the heavy-lifting for creating the various operator objects. You
should not need to call this method explicitly, but it is included if you need it.

```php
// Add some operators directly to the query
$lqb->add($lqb->filter()->startsWith('name', 'srv'), $lqb->filter()->notPresent('description'));
```

------------------------
#### setServer($server)

This lets you set the LDAP server that the query will run against when executed. After the query finishes executing the
connection switches back to the LDAP server it was originally connected to.

```php
// Query a specific LDAP server
$lqb->setServer('dc3.example.local');
```

------------------------
#### setBaseDn($baseDn)

This method sets the base DN (distinguished name) for the query. That means that any LDAP object at or below this point
in the directory will be queried for. This will default first to a `base_dn` set in the schema for the object type you
 are searching for and if that is not set it will default to whatever you set in the domain configuration for the
`base_dn` value.

```php
$lqb->setBaseDn('OU=Employees,OU=Users,DC=example,DC=com')
```

------------------------
#### setScopeSubTree()

'subtree' is the default search scope for the query and should not need to be called explicitly. This method sets the 
LDAP search scope recursively from the point of the base DN onwards.
 
------------------------ 
#### setScopeOneLevel()

This method sets the LDAP search scope to one level at the point of the base DN. This is equivalent to a non-recursive
 listing of the contents of a folder directory. The search will not recurse any further than the level of the base DN.
 
------------------------ 
#### setScopeBase()

This method sets the LDAP search scope to the base level. This is used to retrieve the contents of a single entry, and 
is most commonly used to retrieve the RootDSE of a domain.

Example usage to return the RootDSE for a domain:

```php
// NOTE: You can also call the getRootDse() method on the connection object to get the RootDSE...
$rootDse = $lqb->where($lqb->filter()->present('objectClass'))
                ->setBaseDn('')
                ->setScopeBase()
                ->getLdapQuery()
                ->getSingleResult();
```

------------------------
#### setScope($scope)

Explicitly set the scope for the query using the `QueryOperation::SCOPE` constant. The available options are:
`QueryOperation::SCOPE['SUBTREE']`, `QueryOperation::SCOPE['ONELEVEL']`, `QueryOperation::SCOPE['BASE']`

```php
use LdapTools\Operation\QueryOperation;

$lqb->setScope(QueryOperation::SCOPE['ONELEVEL']);
```

------------------------ 
#### orderBy($attribute, $direction = 'ASC')

This method sets the attribute to order the results by in either ascending (default) or descending order. Calling this 
overwrites any already set orderBy statements. To stack multiple order statements call `addOrderBy($attribute)`.

```php
// Order results by last name (ascending).
$users = $lqb->fromUsers()
    ->where(['firstName' => 'John'])
    ->orderBy('lastName')
    ->getLdapQuery()
    ->getResult();
```

By default the results are ordered in a case-insensitive manner. The order results using a case-sensitive manner use the
`setIsCaseSensitiveSort()` method of the `LdapQuery` class:

```php
// Order results in a case-sensitive manner.
$users = $lqb->fromUsers()
    ->where(['firstName' => 'John'])
    ->orderBy('lastName')
    ->getLdapQuery()
    ->setIsCaseSensitiveSort(true)
    ->getResult();
```

------------------------ 
#### addOrderBy($attribute, $direction = 'ASC')

This method works the same as `orderBy($attribute)`, only calling this one will not overwrite already declared order-by 
statements. Call this when you want to order by multiple attributes.

```php
// Order results by last name (descending) and first name (ascending).
$users = $lqb->fromUsers()
    ->where(['state' => 'Wisconsin'])
    ->orderBy('lastName', 'DESC')
    ->addOrderBy('firstName', 'ASC')
    ->getResult();
```

------------------------
#### setSizeLimit($size)

This methods sets the size limit for the amount of results returned from LDAP for the query.

```php
$lqb->setSizeLimit(10);
```

------------------------
#### setPageSize($size)

This methods sets the paging size for the query. It will default to whatever value you set in your configuration. The
default when no value is explicitly set is 1000.

```php
$lqb->setPageSize(500);
```

------------------------
#### setUsePaging($usePaging)

This methods lets you set whether or not paging should be used for the query. This overrides whatever is set in the
domain configuration. If this is not set, then whatever is set in the domain configuration is used.

```php
$lqb->setUsePaging(false);
```

------------------------
#### toLdapFilter()

Gets the LDAP filter, as a string, that the query would produce.

```php
$filter = $lqb->toLdapFilter();
```

## Using Aliases

When you want to search for multiple object types you can assign them specific aliases to refer to them in your filter.
This makes it easy to get all the results you need with a single query:

```php
use LdapTools\Object\LdapObjectType;

$query = $ldap->buildLdapQuery();

// Get all users with a department that starts with IT and groups that contain 'admin' in their description.
// The resulting objects are ordered by their name (for both users and groups).
$results = $query
    ->fromUsers('u')
    ->fromGroups('g')
    ->where($query->filter()->startsWith('u.department', 'IT'))
    ->andWhere($query->filter()->contains('g.description', 'admin'))
    ->sortBy('name')
    ->getLdapQuery()
    ->getResult();

foreach ($results as $result) {
    if ($result->isType(LdapObjectType::USER)) {
        echo "User: ".$result->getName();
    } else {
        echo "Group: ".$result->getName();
    }
}

// Select all OUs and Containers at the root of the domain. Order them by name with OUs first, then containers.
$results = $ldap->buildLdapQuery()
    ->from(LdapObjectType::OU, 'u')
    ->from(LdapObjectType::CONTAINER, 'c')
    ->addOrderBy('u.name', 'ASC')
    ->addOrderBy('c.name', 'ASC')
    ->setScopeOneLevel()
    ->getLdapQuery()
    ->getResult();
```

You can reference an alias in the query builder anywhere that you would reference a specific attribute (select,
orderBy, where/andWhere/or/orWhere statements, etc). The only rule that applies for alias names is that they can only
be alphanumeric (but can also contain underscores).

## LdapQuery Methods to Retrieve LDAP Results
-----------------------

There are a few ways to retrieve LDAP results after you have a query built. How you retrieve the results depends upon
what type of data you're looking for. To start to retrieve results you need to first get a `LdapQuery` instance by using
the `getLdapQuery()` method.

The `getLdapQuery()` method retrieves an instance of the `LdapQuery` object that you can then call methods on to get 
your results. The `LdapQuery` object has the filter, page size, base DN, scope, etc that you set in the builder and 
takes care of converting the LDAP results array using a hydration process. It returns an easier to use set of objects. 
Or you can have it return a simple set of arrays with the attributes and values.
 
```php

// By default the results will be a collection of LdapUser objects you can iterate over...
$results = $lqb->getLdapQuery()->getResult();

foreach ($results as $result) {
    echo $result->getEmailAddress();
}

// If you just want simple arrays returned you can specify that
$results = $lqb->getLdapQuery()->getArrayResult();

foreach ($results as $result) {
    foreach ($result as $attribute => $value) {
        echo "$attribute => $value";
    }
}

```

#### execute($hydrationType = HydratorFactory::TO_OBJECT)
------------------------

This `LdapQuery` method executes the LDAP filter with the options you have set and returns the results as either a set
objects (this is the default) or as an array (use the hydration type `HydratorFactory::TO_ARRAY`). See previous example 
for full usage.

#### getResult($hydrationType = HydratorFactory::TO_OBJECT)
------------------------

This is an alias for the `execute()` method. It will return a `LdapObjectCollection` by default, or an array of LDAP
entries if specified as `getResult(HydratorFactory::TO_ARRAY)`.

#### getArrayResult()
------------------------

This functions the same as the `getResult()` method, but it will always return the LDAP entries as an array instead of a
collection of objects. This is identical to calling `getResult(HydratorFactory::TO_ARRAY)`.

#### getSingleResult($hydrationType = HydratorFactory::TO_OBJECT)
------------------------

This `LdapQuery` method will retrieve a single result from LDAP. So instead of a collection of objects or arrays you
will be given a single result you can immediately begin to work with.

```php
$lqb = $ldap->buildLdapQuery();

// Retrieve a single LdapObject from a query...
$user = $lqb->fromUsers()
    ->Where(['username' => 'chad'])
    ->getLdapQuery()
    ->getSingleResult();

echo "DN : ".$user->getDn();
```

If an empty result set is returned from LDAP then it will throw a `\LdapTools\Exception\EmptyResultException`. If more
than one result is returned from LDAP then it will throw a `\LdapTools\Exception\MultiResultException`. Additionally, 
you may pass an explicit hydration type to this method if you wish to get the result as a single array of attributes and
values.

#### getOneOrNullResult($hydrationType = HydratorFactory::TO_OBJECT)
------------------------

The behavior of this method is very similar to `getSingleResult()`, but if no results are found for the query it will
return `null` instead of throwing an exception. However, it will still throw an exception in the case that more than one
result is returned from LDAP.

```php
$lqb = $ldap->buildLdapQuery();

// Retrieve a single LdapObject from a query, or a null result if it doesn't exist...
$user = $lqb->fromUsers()
    ->Where(['username' => 'john'])
    ->getLdapQuery()
    ->getOneOrNullResult();

// Could be null, so check first...
if ($user) {
    echo "DN : ".$user->getDn();
}
```

#### getSingleScalarResult()
------------------------

Using this method you can get the value of a single attribute from the query. If the LDAP object or attribute does not
exist then it will throw an exception.

```php
$lqb = $ldap->buildLdapQuery();

// Retrieve the GUID string of a specific AD user...
$guid = $lqb->select('guid')
    ->fromUsers()
    ->Where(['username' => 'chad'])
    ->getLdapQuery()
    ->getSingleScalarResult();

echo "GUID : ".$guid;
```

#### getSingleScalarOrNullResult()
------------------------

The behavior of this method is very similar to `getSingleScalarResult()`, but if the attribute is not found/set
for the LDAP object it will return `null` instead of throwing an exception. However, it will still throw an exception
in the case that more than one result is returned from LDAP or if the LDAP object does not exist.

```php
$lqb = $ldap->buildLdapQuery();

// Retrieve the title of a specific AD user...
$title = $lqb->select('title')
    ->fromUsers()
    ->Where(['username' => 'chad'])
    ->getLdapQuery()
    ->getSingleScalarOrNullResult();

// Check if the attribute actually had a value first
$title = $title ?: 'Unknown';

echo $title;
```

## Caching Queries
------------------------

If you have enabled/set a caching a method in your configuration, you can use several different options to cache queries
going to LDAP. This could save considerable time, as the raw LDAP results will be fetched from the cache for the query
operation.

```php
// Retrieve all of the users...
$allUsers = $ldap
    ->fromUsers()
    ->getLdapQuery()
    // Grab the results from the cache, or cache the result if it does not exist.
    ->useCache(true)
    // Expire the cached result in one day (accepts any \DateTimeInterface object...
    ->expireCacheAt((new \DateTime())->modify('+1 day'))
    ->getResult();
```

On the first run of the above query it will grab the results from LDAP, then store it in the cache with an expiration 1
day from now. To retrieve the results from the cache you need to run the same query with `useCache(true)`.

------------------------
#### useCache($useCache = false)

Set whether or not the cache should be used for the query. This controls both retrieval and storage of the result in the
cache. You must set this if you want to retrieve an already cached result from the cache.

------------------------
#### expireCacheAt(\DateTimeInterface $time = null)

Set this to force the cache to expire at a specific time. This can be any `\DateTimeInterface` object. To never expire
the cache item set it to `null`.

------------------------
#### executeOnCacheMiss($executeOnCacheMiss = true)

Set whether or not the query should execute if `useCache()` was set to `true` and the result was not already in the cache.
If this is set to `false` and the result is not in the cache then a `CacheMissException` will be thrown. By default this
is set to true, so the item is not in the cache the operation will re-run and re-cache the result.

------------------------
#### invalidateCache($invalidateCache = false)

Set whether or not to delete a cached result for the query (if it exists in the cache). You do not have to set `useCache()`
for this to be triggered. However, you can use it in conjunction with `useCache(true)` to force a refresh of an already
cached item.

```php
// Retrieve all of the users...
$allUsers = $ldap
    ->fromUsers()
    ->getLdapQuery()
    // Force any existing cache item to be removed first...
    ->invalidateCache(true)
    ->useCache(true)
    ->expireCacheAt((new \DateTime())->modify('+1 day'))
    ->getResult();
```

## Filter Method Shortcuts
------------------------

The `filter()` method of the `LdapQueryBuilder` returns a helper class that provides many shortcut methods for creating
the LDAP operator classes within the `\LdapTools\Query\Operator` namespace. This way you do not have to manually
construct the operators by doing:

```php
use \LdapTools\Query\Operator\bOr;
use \LdapTools\Query\Operator\Comparison;

// ...
$lqb->where(new bOr(
    new Comparison('firstName', Comparison::EQ, 'Bill'),
    new Comparison('firstName', Comparison::EQ, 'Egon')
));
```

Instead you can write:

```php
$lqb->where($lqb->filter()->or(
    $lqb->filter()->eq('firstName', 'Bill'),
    $lqb->filter()->eq('firstName', 'Egon')
));
```

When you call `filter()` you are just calling a method on the `\LdapTools\Query\Builder\FilterBuilder` class. The full 
list and description of available methods is below.

------------------------
#### aeq($attribute, $value)

Creates an "approximately-equal-to" comparison between the attribute and the value. The results are dependent on the
LDAP specific implementation of this operator. But it will typically function as a "sounds like" comparison: 
`(attribute~=value)`

```php
$lqb->filter()->aeq('firstName', 'Sue');
```

------------------------
#### eq($attribute, $value)

Creates an "equal-to" comparison between the attribute and the value: `(attribute=value)`

```php
$lqb->filter()->eq('lastName', 'Sikorra');
```

------------------------
#### neq($attribute, $value)

Creates a "not-equal-to" comparison between the attribute and the value. This is equivalent to wrapping a
 `eq($attribute, $value)` within a 'NOT' statement: `(!(attribute=value))`

```php
$lqb->filter()->neq('department', 'Purchasing');
```

------------------------
#### lt($attribute, $value)

Creates a "less-than" comparison between the attribute and the value. Since an actual '<' operator does not exist in 
LDAP, this is a combination of a greater-than-or-equal-to operator along with a check if the attribute is set/present.
This is encapsulated within a logical 'AND' operator: `(&(!(attribute>=value))(attribute=*))`

```php
$lqb->filter()->lt('badPasswordCount', 2);
```

------------------------
#### leq($attribute, $value)

Creates a "less-than-or-equal-to" comparison between the attribute and the value: `(attribute<=value)`

```php
$lqb->filter()->leq('badPasswordCount', 1);
```

------------------------
#### gt($attribute, $value)

Creates a "greater-than" comparison between the attribute and the value. Since an actual '>' operator does not exist in 
LDAP, this is a combination of a less-than-or-equal-to operator along with a check if the attribute is set/present.
This is encapsulated within a logical 'AND' operator: `(&(!(attribute<=value))(attribute=*))`

```php
$lqb->filter()->gt('created', new \DateTime('01-20-2013'));
```

------------------------
#### geq($attribute, $value)

Creates a "greater-than-or-equal-to" comparison between the attribute and the value: `(attribute>=value)`

```php
$lqb->filter()->geq('badPasswordCount', 3);
```

------------------------
#### match($attribute, $rule, $value, $dnFlag = false)

Creates an extensible match against an attribute or dn: `(attribute:caseExactMatch:=value)`

```php
$lqb->filter()->match('name', 'caseExactMatch', 'Chad');
```

------------------------
#### matchDn($attribute, $value)

Creates an extensible match with the DN flag. This can help in searching multiple OUs: `(ou:dn:=Sales)`

**Note**: AD Does not support this aspect of the extensible match.

```php
$lqb->filter()->matchDn('ou', 'Sales');
```

------------------------
#### in($attribute, array $values)

Check if an attribute value matches any of the values in the list of values provided. This is a shortcut for a multiple
OR condition: `(|(id=1)(id=2)(id=3)(id=4)(id=5))`

```php
$lqb->filter()->in('id', [1, 2, 3, 4, 5]);
```

------------------------
#### bitwiseAnd($attribute, $value)

Creates a bitwise 'AND' comparison between the attribute and the value: `(attribute:1.2.840.113556.1.4.803:=value)`

```php
use LdapTools\Query\UserAccountControlFlags;

$lqb->filter()->bitwiseAnd('userAccountControl', UserAccountControlFlags::DISABLED);
```

------------------------
#### bitwiseOr($attribute, $value)

Creates a bitwise 'OR' comparison between the attribute and the value: `(attribute:1.2.840.113556.1.4.804:=value)`

```php
use LdapTools\Query\GroupTypeFlags;

$lqb->filter()->bitwiseOr('groupType', GroupTypeFlags::UNIVERSAL_GROUP);
```

------------------------
#### startsWith($attribute, $value)

Creates a "equal-to" comparison with a wildcard after the value: `(attribute=value*)`

```php
$lqb->filter()->startsWith('department', 'IT');
```

------------------------
#### endsWith($attribute, $value)

Creates a "equal-to" comparison with a wildcard before the value: `(attribute=*value)`

```php
$lqb->filter()->endsWith('description', 'service');
```

------------------------
#### contains($attribute, $value)

Creates a "equal-to" comparison with a wildcards at each end of the value: `(attribute=*value*)`

```php
$lqb->filter()->contains('name', 'admin');
```

------------------------
#### like($attribute, $value)

Creates a "equal-to" comparison that will not escape any wildcards you use in the value: `(attribute=v*a*l*u*e)`.

```php
$lqb->filter()->like('description', '*Some*thing*');
```

------------------------
#### present($attribute)

Creates a "equal-to" comparison with a single wildcard as the value. Returns any entry with this attribute populated: 
`(attribute=*)`

```php
$lqb->filter()->present('mail');
```

------------------------
#### notPresent($attribute)

Creates a negated form of the `present($attribute)` method. Returns any entry that does not contain the attribute: 
`(!(attribute=*))`

```php
$lqb->filter()->notPresent('department');
```

------------------------
#### bAnd(...$statements)

Creates a logical 'AND' statement against all other operators passed to it: `(&((attribute=value)(attribute=value)))`

```php
$lqb->filter()->bAnd(
    $lqb->filter()->eq('department', 'IT'), 
    $lqb->filter()->startsWith('firstName', 'Tim')
);
```

------------------------
#### bOr(...$statements)

Creates a logical 'OR' statement against all other operators passed to it: `(|((attribute=value)(attribute=value)))`

```php
$lqb->filter()->bOr(
    $lqb->filter()->eq('department', 'IT'), 
    $lqb->filter()->eq('department', 'Purchasing')
);
```

------------------------
#### bNot($statements)

Creates a logical 'NOT' statement against whatever other statement you pass it it: `(!(attribute=value))`

```php
$lqb->filter()->bNot($lqb->filter()->eq('department', 'IT'));
```

## Active Directory Filter Method Shortcuts

If you're using a `LdapConnection` that has a LDAP type set as `ad`, then when you call `filter()` you will also have
additional filter method shortcuts that are specific to Active Directory:

------------------------
#### hasMemberRecursively($member, $attribute = 'members')
---

Recursively checks groups for a specific member. The `$member` parameter can be any of the following:
 
 * A username. This must be unique! If a computer and user share the same name then an exception will be thrown.
 * The GUID of an object (ie. a value like `bee66f2f-bcf7-4905-b65b-2f36d5008f1e`)
 * The SID of an object (ie. a value like `S-1-5-21-1004336348-1177238915-682003330-512`)
 * The full distinguished name of an object.
 * A `LdapObject` as the result of another query.
 
This creates a matching rule comparison using the OID `IN_CHAIN` against the groups `members` attribute by default. If
you have a custom attribute, or some other attribute you would like to run it against, you must pass it as the second
argument.

```php
$username = 'chad';

// Query by a username to get all of their groups recursively...
$groups = $ldap->buildLdapQuery()
    ->fromGroups()
    ->where($query->filter()->hasMemberRecursively($username))
    ->getLdapQuery()
    ->getResult();
```

------------------------
#### isRecursivelyMemberOf($group)

Recursively checks an object's group membership for a group. The `$group` parameter can be any of the following:

* The name of a group.
* The GUID of a group (ie. a value like `bee66f2f-bcf7-4905-b65b-2f36d5008f1e`)
* The SID of a group (ie. a value like `S-1-5-21-1004336348-1177238915-682003330-512`)
* The full distinguished name of a group.
* A `LdapObject` as the result of another query.

This creates a matching rule comparison using the OID `IN_CHAIN` against the users `groups` attribute.

```php
// Query by a group name...
$query = $ldap->buildLdapQuery();
$users = $query->select()
    ->fromUsers()
    ->where($query->filter()->isRecursivelyMemberOf('Employees'))
    ->getLdapQuery()
    ->getResult();

// If you are not targeting a specific set of objects from the schema, then you must
// pass 'false' as the second argument and specify a full DN. Otherwise this method
// will attempt to use the 'groups' attribute from the schema by default.
$ldapObjects = $ldap->buildLdapQuery()
    ->select('description')
    ->where(['cn' => 'foo'])
    ->andWhere($query->filter()->isRecursivelyMemberOf('CN=Foo,DC=foo,DC=bar', false))
    ->getLdapQuery()
    ->getResult();
```

------------------------
#### mailEnabled()
---

Performs a simple check to determine whether an LDAP object is mail-enabled (ie. can receive email from Exchange).
