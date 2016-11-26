# AD User Modification
----------------------

* [User Account Properties](#user-account-properties)
* [Group Membership](#group-membership)
* [Log-On-To Workstations List](#user-log-on-to-workstations-list)
* [Account Expiration Date](#account-expiration-date)
* [Manager Modification](#manager-modification)

Modifying an Active Directory user is really no different than modifying any other LDAP object, but there are a few
things to note. For example, using a few simple statements you can modify many of the user's properties typically seen
in the "Account" tab in the "AD Users and Computers" tool:

```php
use LdapTools\Object\LdapObjectType;

//...

// First get the user object via a repository.
$repository = $ldapManager->getRepository(LdapObjectType::USER);
$user = $repository->findOneByUsername('chad');

// Make sure the user account is set to enabled.
$user->setEnabled(true);
// Set their password to never expire.
$user->setPasswordNeverExpires(true);

try {
    $ldapManager->persist($user);
} catch (\Exception $e) {
    echo "Error modifying user! ".$e->getMessage();
}
```

With the above statement you have just the user account to be enabled and set the password to never expire.

## User Account Properties

This table contains many useful AD attributes you can toggle with a simple `true` or `false` value like above.

| Property Name  | Description |
| --------------- | -------------- |
| disabled | Whether or not the account is disabled. |
| enabled | Whether or not the account is enabled. |
| passwordIsReversible | Legacy AD setting. Should NOT be used. |
| passwordMustChange | Set that the user's password must change on the next login. |
| passwordNeverExpires | Set the user's password to never expire. |
| smartCardRequired | Set that a smart card is required for interactive login. |
| trustedForAllDelegation | Trust the user for delegation to any service (Kerberos). |
| trustedForAnyAuthDelegation | Delegate using any authentication protocol (When selected for delegation to specific services only). |

## Group Membership

Group membership can be modified directly using the `groups` attribute on a user. You can reference a group using its
name, SID, GUID, DN, or a `LdapObject`.

```php
// First get the user object via a query.
$user = $ldapManager->buildLdapQuery()
    ->select('groups')
    ->fromUsers()
    ->where(['username' => 'Chad'])
    ->getLdapQuery()
    ->getSingleResult();

// Add a few groups by name, with the last one being by GUID
$user->addGroups('Employees', 'VPN Users', '270db4d0-249d-46a7-9cc5-eb695d9af9ac');

// Remove a group by a SID
$user->removeGroups('S-1-5-21-1004336348-1177238915-682003330-512');

// Reset the current groups. This will remove any groups they are currently a member of
$user->resetGroups();

// Add a group from the result of a separate LDAP query...
$group = $ldapManager->buildLdapQuery()
    ->fromGroups()
    ->where(['name' => 'IT Stuff'])
    ->getLdapQuery()
    ->getSingleResult();
$user->addGroups($group);

// Save the changes back to LDAP...
try {
    $ldapManager->persist($user);
} catch (\Exception $e) {
    echo "Error modifying groups: ".$e->getMessage();
}
```

## User Log On To Workstations List 

To easily modify the workstations that an account can log into, you can use the `logonWorkstations` attribute. This
attribute functions like an array and maps to to the "Log on To..." section of an account.

```php

// A LdapObject as the result of a search. Set the workstations allowed...
$user->setLogonWorkstations(['PC01', 'PC02', 'PC03']);

// Add only one workstation...
$user->addLogonWorkstations('PC04');

// Remove one of the workstations...
$user->removeLogonWorkstations('PC01');
```

## Account Expiration Date

To modify the date at which an account will expire, which will prevent the user from logging in past that time, you can 
use the `accountExpirationDate` attribute. This attribute accepts either a bool `false` (the account never expires) or
 a PHP `\DateTime` object specifying the date at which the account should expire.
 
```php

// A LdapObject as the result of a search. Set the account to never expire.
$user->setAccountExpirationDate(false);

// Instead, set the account to expire sometime in the future.
$user->setAccountExpirationDate(new \DateTime('2228-3-22'));
```

## Manager Modification

You can set the `manager` attribute by using several values: A string GUID, string SID, a distinguished name, username,
or a `LdapObject`:

```php
// First get the user object via a query.
$user = $ldapManager->buildLdapQuery()
    ->select('manager')
    ->fromUsers()
    ->where(['username' => 'Chad'])
    ->getLdapQuery()
    ->getSingleResult();

// Set the manager via username
$user->setManager('Tim');
// Set the manager via a SID
$user->setManager('S-1-5-21-1004336348-1177238915-682003330-512');
// Set the manager via a GUID
$user->setManager('270db4d0-249d-46a7-9cc5-eb695d9af9ac');
// Set the manager via a DN
$user->setManager('CN=Tim,OU=Employees,DC=example,DC=local');

// Set the manager as a result of an LDAP object for a different query
$manager = $ldapManager->buildLdapQuery()
    ->select()
    ->fromUsers()
    ->where(['lastName' => 'Smith', 'office' => 'Head Office'])
    ->getLdapQuery()
    ->getSingleResult();
$user->setManager($manager);

// All of the above will ultimately produce the same result.
try {
    $ldapManager->persist($user);
} catch (\Exception $e) {
    echo "Error changing the manager: ".$e->getMessage();
}
```
