# Modifying GPO Links on OUs
------------------------------

When working with Active Directory OUs you may want to add, remove, or display GPOs that are linked to them. This can be
accomplished through the use of the `gpoLinks` attribute for the OU type. This attribute can be leveraged on OU creation
or modification to easily set what GPOs should be linked at that OU level. The attribute value functions as an array of 
GPO names that are linked to the OU.

## Creating an OU With a Linked GPO

For example, assume you would like to create an OU named "Employees" that should have 2 GPOs linked to it named 
"Employee Policy" and "Require Screensaver":

```php

$ldapObject = $ldap->createLdapObject();

try {
    $ldapObject->createOU()
        ->in('dc=example,dc=local')
        ->with(['name' => 'Employees', 'gpoLinks' => ['Employee Policy', 'Require Screensaver']])
        ->execute();
} catch (LdapConnectionException $e) {
    echo "Failed to create OU - ".$e->getMessage();
}
```

## Modifying GPOs on an Existing OU

Assume you have an existing OU and would like to remove or add a GPO link from it:

```php
use LdapTools\Object\LdapObjectType;

//...

$repository = $ldap->getRepository(LdapObjectType::OU);

// gpoLinks are not retrieved by default, so make sure to select them.
$repository->setAttributes(['name', 'gpoLinks']);
$ou = $repository->findOneByName('My Special OU');

// Check for, and then remove, the name of an existing GPO if it is linked...
if ($ou->hasGpoLinks('Require Screensaver')) {
    $ou->removeGpoLinks('Require Screensaver');
}

// Add a new GPO link to the OU...
$ou->addGpoLinks('Restricted Access');

// Now actually save the changes back to LDAP
try {
    $ldap->persist($ou);
} catch (LdapConnectionException $e) {
    echo "Failed to modify OU - ".$e->getMessage();
}
```
