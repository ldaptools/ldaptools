# Building LDAP Queries

The `LdapQueryBuilder` class provides an easy object oriented method of producing LDAP filters of any complexity. Those
familiar with Doctrine's QueryBuilder will find this syntax easy to adapt to, as it is pretty much the same. This class
takes care of escaping all values passed to it when generating the filter.

* [LdapQueryBuilder Methods](#ldapquerybuilder-methods)
* [Filter Method Shorcuts](#filter-method-shortcuts)

### Generating LDAP Filters Without LdapManager

This class is most easily used in the context of the `LdapManager`, but it is also possible to use on its own if your 
only desire is to generate LDAP filters.

```php
use LdapTools\Query\LdapQueryBuilder;

$lqb = new LdapQueryBuilder();

$filter = $lqb->select('givenName', 'sn', 'l')
    ->where('objectClass','user')
    ->andWhere($lqb->filter->like('sAMAccountName','*smith'))
    ->getLdapFilter();
    
echo "LDAP Filter: ".$filter.PHP_EOL;
```

### Generating Queries When Using the LdapManager

When you call `createLdapQuery` in the `LdapManager` you will get an instance of the `LdapQueryBuilder` class that knows
all the information about the schema of your domain, and a `LdapConnection` capable of executing the query. With this
information it can do a lot of the heavy lifting to allow it to easily generate any LDAP filter.

```php
$lqb = $ldapManager->createLdapQuery();

// When no attributes are specifically selected, it will pull a default set defined in the schema.
$users = $lqb->select()
    ->fromUsers()
    ->Where(['state' => 'Wisconsin'])
    ->getLdapQuery()
    ->execute();
    
foreach ($users as $user) {
    foreach ($user as $attribute => $value) {
        echo $attribute.' => '.$value.PHP_EOL;
    }
}
```

### LdapQueryBuilder Methods

This class provides many methods that simplify the process of creating complex LDAP filters. The following is a list of
the methods and their general use.

###### `select($attributes)`
---

The `select` method allows you to choose specifically which attributes you would like to return from the query. Simply
pass an array of attribute names to it that you would like. In the absence of anything passed to it, it will select the
default set of attributes for the query as defined for the type in the schema.

Attribute names are looked for in the schema to see if they map to specific LDAP attributes.
```php
$lqb->select(['firstName', 'city', 'state', 'sid']);

// Attribute names will always be returned in the case you enter it in, irrespective of how LDAP returns the data.
$lqb->select(['FirstName', 'City', 'State', 'SID']);
```
 
If you want the raw data to be returned from LDAP you can select LDAP attribute names explicitly. You can also include 
schema names at the same time. **NOTE:** Attributes selected by their LDAP attribute name will **NOT** have attribute 
conversion done.
```php
// Will return 'objectSid' AND 'sid'
$lqb->select(['givenName', 'l', 'objectSid', 'sid']);
```

###### `from($ldapType)`
---

The `from` method requires an argument for the LDAP type. This type must be defined in your LDAP schema. Common types
that are in the schema by default include: `user`, `group`, `contact`, `computer`. These types are defined as constants
in the `\LdapTools\Object\LdapObjectTypes` class. Using this method makes the query aware of the attribute name mapping
and converters defined for the type.

```php
use LdapTools\Object\LdapObjectType;

// Search for users
$lqb->from(LdapObjectType::USER);

// Search for computers
$lqb->from(LdapObjectType::COMPUTER);
```

###### `fromUsers()`
---

A convenience shortcut of the the `from` method to select from LDAP `user` types.

```php
// Search for users
$lqb->fromUsers();
```

###### `fromGroups()`
---

A convenience shortcut of the the `from` method to select from LDAP `group` types.

```php
// Search for groups
$lqb->fromGroups();
```

###### `where(...$statements)`
---

This method encapsulates its arguments into a logical 'AND' statement. You can either pass a simple array of attributes
and values that must be met, or any number of filter operator statements.

```php
// Pass a simple array of attributes => values. These attributes must be equal to these values.
$lqb->where(['firstName' => 'Timmy', 'state' => 'Wyoming]);

// Pass filter operator statements instead
$lqb->where($lqb->filter->gte('created', new \DateTime('5-4-2000'));

// A more complex series of statements
$lqb->where(
    $lqb->filter->neq('firstName', 'Jimbo'),
    $lqb->filter->bOr($lqb->filter->eq('lastName', 'Dodgson'), $lqb->filter->eq('lastName', 'Venkman'))
);
```

###### `andWhere(...$statements)`
---

This method is the same as the `where` method. It encapsulates its arguments into a logical 'AND' statement. Statements
passed will be added to the same logical 'AND' statement that the `where` method created.

###### `orWhere(...$statements)`
---

This method is the same as the `where` method, but it will instead encapsulate any passed arguments into a logical 'OR'
statement.

###### `add(...$statements)`
---

This is a low-level method that will take any object that is an instance of a `BaseOperator` and add it to the query.
The shorthand `$lqb->filter()` methods is what does the heavy-lifting for creating the various operator objects. You
should not need to call this method explicitly, but it is included if you need it.

###### `setBaseDn($baseDn)`
---

This method sets the base DN (distinguished name) for the query. That means that any LDAP object at or below this point
in the directory will be queried for. This will default to whatever you set in the domain configuration for the
`base_dn` value.

```php
$lqb->setBaseDn('OU=Employees,OU=Users,DC=example,DC=com')
```

###### `setScopeSubTree()`
---

'subtree' is the default search scope for the query and should not need to be called explicitly. This method sets the 
LDAP search scope recursively from the point of the base DN onwards.
 
###### `setScopeOneLevel()`
---

This method sets the LDAP search scope to one level at the point of the base DN. This is equivalent to a non-recursive
 listing of the contents of a folder directory. The search will not recurse any further than the level of the base DN.
 
###### `setScopeBase()`
---

This method sets the LDAP search scope to the base level. This is used to retrieve the contents of a single entry, is
likely most commonly used to retrieve  the RootDSE of a domain.

Example usage to return the RootDSE for a domain:

```php
$rootDse = $lqb->where($lqb->filter->present('objectClass'))
                ->setBaseDn('')
                ->setScopeBase()
                ->getLdapQuery()
                ->execute();
```

###### `setPageSize($size)`
---

This methods sets the paging size for the query. It will default to whatever value you set in your configuration. The
default when no value is explicitly set is 1000.

```php
$lqb->setPageSize(500);
```

###### `getLdapFilter()`
---

Gets the LDAP filter, as a string, that the query would produce.

```php
$filter = $lqb->getLdapFilter();
```

###### `getLdapQuery()`
---

Retrieves an instance of the `LdapQuery` object that you can then call the `execute` method on to get your results. The
`LdapQuery` object has the filter, page size, base DN, scope, etc that you set in the builder and takes care of
converting the LDAP results array using a hydration process. It returns an easier to use associative array of the
 LDAP entries.
 
```php
$results = $lqb->getLdapQuery()->execute();
```

### Filter Method Shortcuts

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
$lqb->where($lqb->filter->or(
    $lqb->filter->eq('firstName', 'Bill'),
    $lqb->filter->eq('firstName', 'Egon')
));
```

When you call `filter()` you are just calling a method on the `\LdapTools\Query\Builder\FilterBuilder` class. The full 
list and description of available methods is below.

###### `aeq($attribute, $value)`
---

Creates an "approximately-equal-to" comparison between the attribute and the value. The results are dependent on the
LDAP specific implementation of this operator. But it will typically function like a "sounds like" comparison: 
`(attribute~=value)`

###### `eq($attribute, $value)`
---

Creates an "equal-to" comparison between the attribute and the value: `(attribute=value)`

###### `neq($attribute, $value)`
---

Creates a "not-equal-to" comparison between the attribute and the value. This is equivalent to wrapping a
 `eq($attribute, $value)` within a 'NOT' statement: `(!(attribute=value))`

###### `leq($attribute, $value)`
---

Creates a "less-than-or-equal-to" comparison between the attribute and the value: `(attribute<=value)`

###### `geq($attribute, $value)`
---

Creates a "greater-than-or-equal-to" comparison between the attribute and the value: `(attribute>=value)`

###### `bitwiseAnd($attribute, $value)`
---

Creates a bitwise 'AND' comparison between the attribute and the value: `(attribute:1.2.840.113556.1.4.803:=value)`

###### `bitwiseOr($attribute, $value)`
---

Creates a bitwise 'OR' comparison between the attribute and the value: `(attribute:1.2.840.113556.1.4.804:=value)`

###### `startsWith($attribute, $value)`
---

Creates a "equal-to" comparison with a wildcard after the value: `(attribute=value*)`

###### `endsWith($attribute, $value)`
---

Creates a "equal-to" comparison with a wildcard before the value: `(attribute=*value)`

###### `contains($attribute, $value)`
---

Creates a "equal-to" comparison with a wildcards at each end of the value: `(attribute=*value*)`

###### `like($attribute, $value)`
---

Creates a "equal-to" comparison that will not escape any wildcards you use in the value: `(attribute=v*a*l*u*e)`.

###### `present($attribute)`
---

Creates a "equal-to" comparison with a single wildcard as the value. Returns any entry with this attribute populated: 
`(attribute=*)`

###### `notPresent($attribute)`
---

Creates a negated form of the `present($attribute)` method. Returns any entry that does not contain the attribute: 
`(!(attribute=*))`

###### `bAnd(...$statements)`
---

Creates a logical 'AND' statement against all other operators passed to it: `(&((attribute=value)(attribute=value)))`

###### `bOr(...$statements)`
---

Creates a logical 'OR' statement against all other operators passed to it: `(|((attribute=value)(attribute=value)))`

###### `bNot($statements)`
---

Creates a logical 'NOT' statement against whatever other statement you pass it it: `(!(attribute=value))`

### Active Directory Filter Method Shortcuts

If you're using a `LdapConnection` that has a LDAP type set as `ad`, then when you call `filter()` you will also have
additional filter method shortcuts that are specific to Active Directory:

###### `accountIsDisabled()`
---

Checks for disabled accounts. Creates a bitwise 'AND' comparison against the `userAccountControl` attribute.

###### `accountIsLocked()`
---

Check for locked accounts. Creates a "greater-than-or-equal-to" comparison against the `lockoutTime` attribute.

###### `passwordMustChange()`
---

Checks for accounts that must change their password on the next login. Creates an "equal-to" comparison against the
`pwdLastSet` attribute.

###### `passwordNeverExpires()`
---

Checks for accounts that have passwords that are set to never expire. Creates a bitwise 'AND' comparison against the 
`userAccountControl` attribute.

###### `hasMemberRecursively($userDn)`
---

Recursively checks a groups for a member specified by their full distinguished name. Creates a matching rule comparison 
using the OID `IN_CHAIN` against the groups `member` attribute.

###### `isRecursivelyMemberOf($groupDn)`
---

Recursively checks a user's group membership for a group specified by its full distinguished name. Creates a matching 
rule comparison using the OID `IN_CHAIN` against the users `memberOf` attribute.
