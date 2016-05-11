# Common LDAP Queries
-----------------------

These LDAP queries all assume you are using an LdapManager instance (represented by `$ldap`) built from a configuration
described [in the docs](../reference/Main-Configuration.md). This leverages the `LdapQueryBuilder` class which makes many
of these queries very easy.

### All Users, OUs, Groups, or Computers

```php
# All users
$users = $ldap->buildLdapQuery()->fromUsers()->getLdapQuery()->getResult();

# All groups
$groups = $ldap->buildLdapQuery()->fromGroups()->getLdapQuery()->getResult();

# All OUs
$ous = $ldap->buildLdapQuery()->fromOUs()->getLdapQuery()->getResult();

# All computers
$computers = $ldap->buildLdapQuery()->fromComputers()->getLdapQuery()->getResult();

# All contacts
$contacts = $ldap->buildLdapQuery()->fromContacts()->getLdapQuery()->getResult();

# All deleted objects (AD only)
$deleted = $ldap->buildLdapQuery()->fromDeleted()->getLdapQuery()->getResult();
```

### Users Created After a Certain Date

```php
$query = $ldap->buildLdapQuery();

// The 'gte' filter creates a 'greater-than-or-equal-to' comparison
$users = $query->fromUsers()
    ->where($query->filter()->gte('created', new \DateTime('2004-06-20')))
    ->getLdapQuery()
    ->getResult();
```

### Groups That Start With a Certain String

```php
$query = $ldap->buildLdapQuery();

$groups = $query->fromGroups()
    ->where($query->filter()->startsWith('name', 'Admin'))
    ->getLdapQuery()
    ->getResult();
```

### User Accounts With a Description Containing a Certain String

```php
$query = $ldap->buildLdapQuery();

$users = $query->fromUsers()
    ->where($query->filter()->contains('description', 'service'))
    ->getLdapQuery()
    ->getResult();
```

------------------------------------
## Active Directory Specific Queries

The following are queries that are specific to Active Directory, as they use specific attributes or methods that are
only supported there.

### All Groups a User Belongs to Recursively

```php
$query = $ldap->buildLdapQuery();

// The $username can be a typical AD username, DN, GUID, or SID.
$groups = $query->fromGroups()
    ->where($query->filter()->hasMemberRecursively($username))
    ->getLdapQuery()
    ->getResult();
```

### All Users that Belong to a Group Recursively

```php
$query = $ldap->buildLdapQuery();

// The $group can be a typical AD group name, DN, GUID, or SID.
$users = $query->fromUsers()
    ->where($query->filter()->isRecursivelyMemberOf($group))
    ->getLdapQuery()
    ->getResult();
```

### All Disabled User Accounts

```php
$users = $ldap->buildLdapQuery()
    ->fromUsers()
    ->where(['disabled' => true])
    ->getLdapQuery()
    ->getResult();
```

### All Locked User Accounts

```php
$users = $ldap->buildLdapQuery()
    ->fromUsers()
    ->where(['locked' => true])
    ->getLdapQuery()
    ->getResult();
```

### All Active User Accounts with Exchange Mailboxes

```php
$query = $ldap->buildLdapQuery();

$users = $query->fromUsers()
    ->where(['enabled' => true])
    ->andWhere($query->filter()->mailEnabled())
    ->getLdapQuery()
    ->getResult();
```

### All Active User Accounts With Passwords That Must Change on Next Login

```php
$users = $ldap->buildLdapQuery()
    ->fromUsers()
    ->where(['enabled' => true, 'passwordMustChange' => true])
    ->getLdapQuery()
    ->getResult();
```

### All Security Enabled Groups With No Members

```php
$query = $ldap->buildLdapQuery();

$groups = $query->fromGroups()
    ->where(['typeSecurity' => true])
    ->andWhere($query->filter()->notPresent('members'))
    ->getLdapQuery()
    ->getResult();
```

### User Accounts With Passwords That Do Not Expire

```php
$users = $ldap->buildLdapQuery()
    ->fromUsers()
    ->where(['passwordNeverExpires' => true])
    ->getLdapQuery()
    ->getResult();
```

### User Accounts That Have Bad Password Attempts

```php
$query = $ldap->buildLdapQuery();

$users = $query->fromUsers()
    ->where($query->filter()->gte('badPasswordCount', 1))
    ->getLdapQuery()
    ->getResult();
```

### User Accounts With Hidden Mailboxes Sorted By Last Name

```php
$users = $ldap->buildLdapQuery()
    ->fromUsers()
    ->where(['exchangeHideFromGAL' => true])
    ->orderBy('lastName')
    ->getLdapQuery()
    ->getResult();
```
