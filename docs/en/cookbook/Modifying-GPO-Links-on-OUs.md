# Modifying GPO Links on OUs
------------------------------

When working with Active Directory OUs you may want to add, remove, or display GPOs that are linked to them. This can be
accomplished through the use of the `gpoLinks` attribute for the OU type. This attribute can be leveraged on OU creation
or modification to easily set what GPOs should be linked at that OU level. The attribute value functions as an array of 
`\LdapTools\Utilities\GPOLink` objects that full describe the GPO that is linked, and whether or not the GPO is enforced
and/or enabled. The order of the array of `GPOLink` objects returned represents the link order in AD starting from order
1 onwards.

## Creating an OU With a Linked GPO

For example, assume you would like to create an OU named "Employees" that should have 2 GPOs linked to it named 
"Employee Policy" and "Require Screensaver":

```php
use LdapTools\Utilities\GPOLink;

$ldapObject = $ldap->createLdapObject();

try {
    $ldapObject->createOU()
        ->in('dc=example,dc=local')
        ->with(['name' => 'Employees', 'gpoLinks' => [
            new GPOLink('Employee Policy'), 
            new GPOLink('Require Screensaver')
         ]])
        ->execute();
} catch (LdapConnectionException $e) {
    echo "Failed to create OU - ".$e->getMessage();
}
```

## Modifying GPOs on an Existing OU

Assume you have an existing OU and would like to remove or add a GPO link from it:

```php
use LdapTools\Object\LdapObjectType;
use LdapTools\Utilities\GPOLink;

//...

$repository = $ldap->getRepository(LdapObjectType::OU);

// gpoLinks are not retrieved by default, so make sure to select them.
$repository->setAttributes(['name', 'gpoLinks']);
$ou = $repository->findOneByName('My Special OU');

// Check for, and then remove, the name of an existing GPO if it is linked...
foreach ($ou->getGpoLinks() as $gpoLink) {
    if ($gpoLink->getGpo()->getName() == 'Require Screensaver') {
        $ou->removeGpoLinks($gpoLink);
    }
}

// Add a new GPO link to the OU that is set to be enforced...
$link = (new GPOLink('Restricted Access'))->setIsEnforced(true);
$ou->addGpoLinks($link);

// Now actually save the changes back to LDAP
try {
    $ldap->persist($ou);
} catch (LdapConnectionException $e) {
    echo "Failed to modify OU - ".$e->getMessage();
}
```

## Searching for OUs with Specific GPO Links

You can also use GPOLink objects in queries to check if a link exists on an OU. You can even get as granular as setting
whether the GPOLink is enforced/enabled or not.

```php
use LdapTools\Utilities\GPOLink;

$query = $ldap->buildLdapQuery()->fromOU();

// Find OUs with a link for a GPO named "Some GPO"...
$result = $query->select('name')
    ->where($query->filter()->contains('gpoLinks', new GPOLink('Some GPO')))
    ->getLdapQuery()
    ->getResult();

foreach ($result as $ou) {
    echo "OU: ".$ou->name;
}
```

## Using GPOLink Objects

The `\LdapTools\Utilities\GPOLink` class is used to represent a GPO linked to an OU. It has several helper methods for 
getting all of the details regarding the link.

A GPOLink can be constructed use a name, GUID, DN, or LdapObject:

```php
use LdapTools\Utilities\GPOLink;

// Create a link for a GPO named "Server Baseline"...
$link1 = new GPOLink('Server Baseline");

// Create a link for a specific string GUID that represents a GPO...
$link2 = new GPOLink('968c00d8-f755-4e34-b7e6-586fd60ff5de');

// Query for a GPO then create the link based off the result...
$gpo = $ldap->buildLdapQuery()->where([
        'objectClass' => 'groupPolicyContainer',
        'displayName' => 'Server Exceptions',
    ])
    ->getLdapQuery()
    ->getSingleResult();
$link3 = new GPOLink($gpo);

// Set the second link to be disabled...
$link2->setIsEnabled(false);

// Set the first link to be enforced...
$link1->setIsEnforced(true);

// Query for an OU to apply the GPO links to...
$ou = $ldap->buildLdapQuery()->fromOU()->where(['name' => 'Servers'])
    ->getLdapQuery()
    ->getSingleResult();

// The order in which you add the links is the order they end up in for processing...
$ou->set('gpoLinks', $link1, link2, $link3);
$ldap->persist($ou);
```

When searching for OUs you can inspect their GPO links to determine information about them:

```php
// Query for the GPO links for an OU...
$ou = $ldap->buildLdapQuery()
    ->fromOU()
    ->select('gpoLinks')
    ->where(['name' => 'Servers'])
    ->getLdapQuery()
    ->getSingleResult();

foreach ($ou->get('gpoLinks') as $gpoLink) {
    // The GPO will be the LdapObject representation of the linked GPO... 
    $gpo = $gpoLink->getGpo();
    
    echo "GPO Name: ".$gpo->get('name');
    echo "GPO GUID: ".$gpo->get('guid');
    echo "GPO DN: ".$gpo->get('dn');
    
    // You can check specifically if the link is being enforced...
    if ($gpoLink->getIsEnforced()) {
        echo "The link ".$gpo->get('name')." is being enforced.";
    }
    
    // You can check specifically if the link is actually enabled/disabled...
    if (!$gpoLink->getIsEnabled()) {
        echo "The link ".$gpo->get('name')." is currently disabled.";
    }
}
```

In the above example, if during the loop you want to modify any of the GPOLink objects you should set all of the GPO
links back to the OU and then persist it back to LDAP:

```php
// Query for the GPO links for an OU...
$ou = $ldap->buildLdapQuery()
    ->fromOU()
    ->select('gpoLinks')
    ->where(['name' => 'Servers'])
    ->getLdapQuery()
    ->getSingleResult();

// Lets enable any disabled links...
foreach ($ou->get('gpoLinks') as $gpoLink) {
    if (!$gpoLink->getIsEnabled()) {
        $gpoLink->setIsEnabled(true);
    }
}

// Replace the existing ones with the changes...
$ou->set('gpoLinks', $ou->get('gpoLinks'));
$ldap->persist($ou);
```