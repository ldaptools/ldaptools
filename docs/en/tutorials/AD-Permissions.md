# Active Directory Permissions
------------------------------

* [Querying and Viewing Permissions](#querying-and-viewing-permissions)
* [Modifying Existing Permissions](#modifying-existing-permissions)
* [Security Descriptor Methods](#security-descriptor-methods)
* [DACL and SACL Methods](#dacl-and-sacl-methods)
* [ACE Methods](#ace-methods)
* [Parsing SDDL](#parsing-sddl)

Active Directory permissions are stored in each object in the directory in an attribute called `ntSecurityDescriptor`.
The format of that value is a binary blob. However, if you decode that value it reveals all the Access Control Entries
(ACEs) that make up the Discretionary Access Control List (DACL).

This library provides several helper classes for parsing all of the ACEs assigned to an LDAP object. Not only do you
have the ability to view all of the ACEs, but you can also add/remove/modify ACEs and send the changes back to LDAP to 
change AD permissions for an object.

## Querying and Viewing Permissions

When you query for permissions there are a few rules to keep in mind:

* You must send a LDAP control with the SD Flags value to retrieve permissions as a non-admin account.
* When you query for permissions you need to disable paging, otherwise it will not return any results.

So to query and retrieve the permissions for a specific account, do something like the following:

```php
use LdapTools\Connection\LdapControl;
use LdapTools\Connection\LdapControlType;
use LdapTools\Security\SecurityDescriptor;
use LdapTools\Security\Ace\AceRights;
use LdapTools\Security\SID;

// ...

// This tells the DC that when we request/process the 'ntSecurityDescriptor' we will exclude the SACL.
// Without this the attribute will not be returned from AD (unless you're using a domain admin account)
$sdControl = new LdapControl(LdapControlType::SD_FLAGS_CONTROL, true, LdapControl::berEncodeInt(7));
$ldap->getConnection()->setControl($sdControl);

$user = $ldap->buildLdapQuery()
    ->fromUsers()
    ->where(['username' => 'SomeUser'])
    // The ntSecurityDescriptor is what describes permissions against that object...
    ->select('ntSecurityDescriptor')
    // Paging must be set to false, otherwise it interfers with the SD Flags control we set...
    ->setUsePaging(false)
    ->getLdapQuery()
    ->getSingleResult();

// Instantiate a new Security Descriptor class that decodes and represents all of the AD permissions...
$sd = new SecurityDescriptor($user->get('ntSecurityDescriptor'));

// The DACL is what contains all of your ACEs, so loop through it.
// We will check specifically if this user cannot change their own password.
foreach ($sd->getDacl()->getAces() as $ace) {
   // Ignore any ACEs that allow access
   if ($ace->isAllowAce()) {
       continue;
   }
   // This will check if the user is setup so they cannot change their own password...
   if ((string) $ace->getTrustee() === SID::SHORT_NAME['PS'] && (string) $ace->getObjectType() === AceRights::EXTENDED['CHANGE_PASSWORD']) {
       echo "User cannot change their password.".PHP_EOL;
   }
}
```

## Modifying Existing Permissions

Working off the same example as above, after you determined that a user is set so they cannot change their password,
you can switch it back so they can by removing one ACE and flipping the type of another. The following will assume you
are already working with the Security Descriptor object queried above:

```php
// The DACL is what contains all of your ACEs, so loop through it
// We will check specifically if this user cannot change their own password
foreach ($sd->getDacl()->getAces() as $ace) {
   // Ignore any ACEs that allow access
   if ($ace->isAllowAce()) {
       continue;
   }
   // This will get the ACE that applies to the user themselves ('PS' is the SID short name for 'Principal Self')
   if ((string) $ace->getTrustee() === SID::SHORT_NAME['PS'] && (string) $ace->getObjectType() === AceRights::EXTENDED['CHANGE_PASSWORD']) {
       $sd->getDacl()->removeAce($ace);
   }
   // This flips the ACE type to allow for the 'WD' SID ('WD' is the SID short name for "Everyone").
   if ((string) $ace->getTrustee() === SID::SHORT_NAME['WD'] && (string) $ace->getObjectType() === AceRights::EXTENDED['CHANGE_PASSWORD']) {
       $ace->setType('A');
   }
}

// Now set the new Security Descriptor value and send it back to LDAP...
$user->set('ntSecurityDescriptor', $sd->toBinary());
$ldap->persist($user);

```

## Security Descriptor Methods

The Security Descriptor object is what represents the complete permissions for an LDAP object. It contains the SACL,
DACL, owner, and group. It can be instantiated with its binary representation, or with nothing then explicitly use the
methods below to describe it.

------------------------
#### getDacl()

Get the Discretionary Access Control List for the Security Descriptor. This is what contains all of the permission ACEs
assigned to the LDAP object.

```php
$aces = $sd->getDacl()->getAces();
```

------------------------
#### setDacl(Dacl $dacl = null)

Set the Discretionary Access Control List for the security Descriptor. This must be the DACL object.
 
```php
use LdapTools\Security\Acl\Dacl;

$sd->setDacl(new Dacl());
```

------------------------
#### getSacl()

Get the System Access Control List for the Security Descriptor. This contains System Audit entries, not permissions. The
objects are still ACEs, but they relate to what specific access rights get audited.

```php
$aces = $sd->getSacl()->getAces();
```

------------------------
#### setSacl(Sacl $sacl = null)

Set the System Access Control List for the Security Descriptor. This must be the SACL object.

```php
use LdapTools\Security\Acl\Sacl;

$sd->setSacl(new Sacl());
```

------------------------
#### getOwner()

This returns the owner of the Security Descriptor as a SID object.

```php
echo $sd->getOwner()->toString();
```

------------------------
#### setOwner($owner)

Set the owner of the Security Descriptor. This can be a SID object or a string SID/SID short name.

```php
use LdapTools\Security\SID;

// The SID can be constructed with a SID string, or a SID short name...
$sd->setOwner(SID::SHORT_NAME['BA']);
```

------------------------
#### getGroup()

Get the primary group of the owner of the Security Descriptor. This will be a SID object.

```php
echo $sd->getGroup()->toString();
```

------------------------
#### setGroup($group)

Set the primary group of the Security Descriptor. This can be a SID object or a string SID/SID short name.

```php
// The SID can be constructed with a SID string, or a SID short name...
$sd->setGroup('S-1-5-21-1263317781-1938881490-3107577794-512');
```

------------------------
#### toBinary($canonical = true)

Get the binary representation of the Security Descriptor. This is the format it must be in when being sent back via LDAP.
You can optionally pass `false` as a parameter if you're sending a non-canonical set of ACEs to LDAP. Typically you do
not want to do that.

```php
$binary = $sd->toBinary();
```

------------------------
#### toSddl($canonical = true)

Get the Security Descriptor Definition Language (SDDL) string representation of the object. You can optionally pass 
`false` as a parameter if you want the ACEs represented in a non-canonical form. Typically you do not want to do that.

```php
echo $sd->toSddl();
```

------------------------
#### getControlFlags()

Get the Control Flags for the Security Descriptor. This returns a flags object.

```php
use LdapTools\Security\ControlFlags;

$flags = $sd->getControlFlags();

if ($flags->has(ControlFlags::FLAG['SACL_PRESENT'])) {
   // ...
}
```

------------------------
#### setControlFlags(ControlFlags $flags)

Set the control flags for the Security Descriptor. This must be a ControlFlags object.

```php
use LdapTools\Security\ControlFlags;

$sd->setControlFlags(new ControlFlags(ControlFlags::FLAGS['SELF_RELATIVE']));
```

## DACL and SACL Methods

The DACL and SACL both contain a set of ACEs. The DACL is what contains all of the permission ACEs. The SACL contains
all of the System ACEs used for auditing purposes. However, they both contain the same basic methods, with only a few
exceptions.

------------------------
#### getAces()

Get all of the ACEs assigned to the ACL as an array of ACE objects.

```php
// Loop through each ACE and print out the SDDL representation of it...
foreach ($sd->getDacl()->getAces() as $ace) {
    echo $ace->toSddl();
}
```

------------------------
#### setAces(Ace ...$aces)

Set the ACEs for the ACL. This overwrites any ACEs that might be set on the ACL already.

```php
// Set some valid access obviously (not these...)
$aces = [
    (new Ace('D'))->setTrustee('BA'),
    (new Ace('A'))->setTrustee('PS'),
];

$sd->getDacl()->setAces(...$aces);
```

------------------------
#### hasAce(Ace ...$aces)

Check if an ACE (or several ACEs) exists on the ACL. The check is strict, so the ACE needs to be the same object to return true.

------------------------
#### removeAce(Ace ...$aces)

Remove an ACE (or several ACEs) from the ACL. The ACE must be the same object already assigned to the ACL.

```php
foreach ($sd->getDacl()->getAces() as $ace) {
    // Perform some logic to check if the ACE should be removed...
    // ...
    $sd->getDacl()->removeAce($ace);
}
```

------------------------
#### addAce(Ace ...$aces)

Add an ACE (or several ACEs) to the ACL. You do not have to worry about the the ACE order in the ACL when adding it.
When the ACL is converted to binary/SDDL form it is canonicalized to be in the correct order.

```php
use LdapTools\Security\Ace\Ace;
use LdapTools\Security\Ace\AceRights;

// OA is short for an object allow ace type...
$ace = (new Ace('OA'))
    // The SID of the user being granted the right...
    ->setTrustee('S-1-5-21-1263317781-1938881490-3107577794-1020')
    // This is an extended access right allowing the trustee to reset a password...
    ->setObjectType(AceRights::EXTENDED['RESET_PASSWORD'])
    // This sets the ACE with the Access Control right...
    ->setRights(new AceRights(AceRights::FLAGS['DS_CONTROL_ACCESS']));

// Add the ACE to the DACL...
$sd->getDacl()->addAce($ace);
```

------------------------
#### toSddl($canonicalize = true)

This will return a SDDL string that represents all ACEs within the ACL. You can optionally pass a bool to indicate
whether or not the ACEs should be canonicalized or not (by default they are).

```php
echo $sd->getDacl()->toSddl();
```

------------------------
#### toBinary($canonicalize = true)

This will return the binary form of the ACL. This is not very useful by itself outside the context of the binary Security
Descriptor overall. You can optionally pass a bool to indicate whether or not the ACEs should be canonicalized or not
(by default they are).

------------------------
#### isCanonical()

This method is only valid for the DACL. It returns a bool value for whether or not all of the ACEs in the ACL are in 
canonical form. If you want to see if the ACEs are in canonical for coming from AD then you should call this immediately
after instantiating the Security Descriptor.

```php
if (!$sd->getDacl()->isCanonical()) {
    echo "Warning! The DACL is not in canonical form. Be careful when editing...";
}
```

------------------------
#### canonicalize()

Calling this method forces all ACEs in the ACL into canonical form. This is the order that AD expects the ACEs to be in
when saving it back via LDAP. The ACLs are canonicalized automatically when being converted to binary/SDDL, so you do
not have to call this each time you make a modification.

## Ace Methods

The Access Control Entry (ACE) represents a specific permission assigned to an ACL. This can represent whether a specific
user (The "Trustee" as it is referred to on an ACE) is allowed or denied read/write access to a specific Active Directory
property object, or event if they are allowed or denied rights for specific actions, such as resetting a password or being
able to have send-as rights on a user's mailbox in Exchange.

There are several helper/convenience methods available to make it easier to decipher and parse the ACEs assigned to an
object so you can take the action you need.

------------------------
#### getType()

The ACE type represents if this is an access allowed or access denied type, and whether the ACE relates to an object type
or not. 

```
// Show the SDDL short name of the ACE type...
echo $ace->getType()->getShortName();
```

------------------------
#### setType($type)

When setting the ACE type explicitly you need to use the AceType object or the ACE type shortname. The ACE type can be 
one of several types and can be defined by its SDDL short name or one of the constants available on the `LdapTools\Security\Ace\AceType` 
class. Available types defined on the AceType class include:

```php
    const TYPE = [
        'ACCESS_ALLOWED' => 0x00,
        'ACCESS_DENIED' => 0x01,
        'SYSTEM_AUDIT' => 0x02,
        'SYSTEM_ALARM' => 0x03,
        'ACCESS_ALLOWED_COMPOUND' => 0x04,
        'ACCESS_ALLOWED_OBJECT' => 0x05,
        'ACCESS_DENIED_OBJECT' => 0x06,
        'SYSTEM_AUDIT_OBJECT' => 0x07,
        'SYSTEM_ALARM_OBJECT' => 0x08,
        'ACCESS_ALLOWED_CALLBACK' => 0x09,
        'ACCESS_DENIED_CALLBACK' => 0x0A,
        'ACCESS_ALLOWED_CALLBACK_OBJECT' => 0x0B,
        'ACCESS_DENIED_CALLBACK_OBJECT' => 0x0C,
        'SYSTEM_AUDIT_CALLBACK' => 0x0D,
        'SYSTEM_ALARM_CALLBACK' => 0x0E,
        'SYSTEM_AUDIT_CALLBACK_OBJECT' => 0x0F,
        'SYSTEM_ALARM_CALLBACK_OBJECT' => 0x10,
        'SYSTEM_MANDATORY_LABEL' => 0x11,
        'SYSTEM_RESOURCE_ATTRIBUTE' => 0x12,
        'SYSTEM_SCOPED_POLICY_ID' => 0x13,
    ];
```

Only `ACCESS_*` ACEs can be added to a DACL. And only `SYSTEM_*` ACEs can be added to a SACL.

```php
use LdapTools\Ace\Ace;
use LdapTools\Ace\AceType;

// You can instantiate an ACE using its AceType SDDL short name...
$ace = new Ace('A');

// Set the AceType by object, flipping it to a deny...
$ace->setType(new AceType('D'));

// Set the type by the shortname to flip it back to an allow...
$ace->setType('A');
```

------------------------
#### getTrustee()

Get SID object of the trustee for the ACE.
 
```php
// Print the trustee SID as a string...
echo $ace->getTrustee()->toString();
```

------------------------
#### setTrustee($sid)

Set the SID of the trustee for this ACE. This can be either a SID object, a string SID, or a SID short name.

```php
// Set the trustee SID by short name...
$ace->setTrustee('PS');

// Set the trustee SID by using a string SID...
$ace->setTrustee('S-1-5-21-1263317781-1938881490-3107577794-512');
```

------------------------
#### getObjectType()

Get the GUID object type this ACE controls access for if this is an object type ACE (otherwise this will be null). This GUID
can represent a specific AD property, or some type of extended access right for the ACE.

```php
if ($ace->getObjectType()) {
    echo $ace->getObjectType()->toString();
}
```

------------------------
#### setObjectType($guid)

Set the GUID object type this ACE controls access for. When you set this it will automatically toggle the AceObjectFlags
as needed. You can pass either a GUID object or a GUID string as a parameter.

```php
use LdapTools\Security\GUID;
use LdapTools\Security\Ace\AceRights;

// Set by the GUID object...
$ace->setObjectType(new GUID('bf967950-0de6-11d0-a285-00aa003049e2'));

// Set by a string GUID from a constant...
$ace->setObjectType(AceRights::EXTENDED['RESET_PASSWORD']);
```

------------------------
#### getInheritedObjectType()

Get the GUID object type that represents the type of child objects that can inherit the ACE. This returns the same type
of value as the `getObjectType()` method. See above.

------------------------
#### setInheritedObjectType($guid)

Set the GUID object type that represents the type of child objects that can inherit the ACE. This allows the same type
of values as the `setObjectType()` method. See above.

------------------------
#### getRights()

Returns an AceRights object that has several helper methods for checking/settting what rights are assigned to this ACE:
 
```php
// Get the AceRights object...
$rights = $ace->getRights();

// The below functions can either check for access, or set access (pass a bool true or false to toggle)...

/**
 * Check or set the ability to perform a delete-tree operation on the object.
 */
$rights->deleteTree($action = null);

/**
 * Check or set the ability to read a specific property.
 */
$rights->readProperty($action = null);

/**
 * Check or set the ability to write a specific property.
 */
$rights->writeProperty($action = null);

/**
 * Check or set the ability to create child objects.
 */
$rights->createChildObject($action = null);

/**
 * Check or set the ability to delete child objects.
 */
$rights->deleteChildObject($action = null);

/**
 * Check or set the ability to list child objects.
 */
$rights->listChildObject($action = null);

/**
 * Check or set the ability to delete objects of a certain type (all if objectType on the ACE is empty).
 */
$rights->deleteObject($action = null);

/**
 * Check or set the ability to list objects of a specific type.
 */
$rights->listObject($action = null);

/**
 * Check or set the ability to perform a validated write for a property.
 */
$rights->validatedWrite($action = null);

/**
 * Check or set control access rights. These control specific actions/operations on an object or attribute.
 */
$rights->controlAccess($action = null);

/**
 * Check or set the ability to read data from the security descriptor (minus the SACL).
 */
$rights->readSecurity($action = null);

/**
 * Check or set the ability to access the SACL of an object.
 */
$rights->accessSacl($action = null);

/**
 * Check or set the ability to write the DACL of an object.
 */
$rights->writeDacl($action = null);

/**
 * Check or set the ability to assume ownership of the object. The user must be an object trustee. The user cannot
 * transfer the ownership to other users.
 */
$rights->writeOwner($action = null);

/**
 * Check or set the ability to read permissions on this object, read all the properties on this object, list this
 * object name when the parent container is listed, and list the contents of this object if it is a container.
 */
$rights->readAll($action = null);

/**
 * Check or set the ability to read permissions on this object, write all the properties on this object, and perform
 * all validated writes to this object.
 */
$rights->writeAll($action = null);

/**
 * Check or set the ability to read permissions on, and list the contents of, a container object.
 */
$rights->execute($action = null);

/**
 * Check or set the ability to create or delete child objects, delete a subtree, read and write properties, examine
 * child objects and the object itself, add and remove the object from the directory, and read or write with an
 * extended right.
 */
$rights->fullControl($action = null);

/**
 * Check or set the ability to use the object for synchronization. This enables a thread to wait until the
 * object is in the signaled state.
 */
$rights->synchronize($action = null);
```

------------------------
#### setRights(AceRights $rights)

Set a specific AceRights to the ACE.

```php
use LdapTools\Security\Ace\AceRights;

$rights = new AceRights(AceRights::SHORT_NAME['CR']);
$ace->setRights($rights);
```

------------------------
#### getFlags()

Gets an AceFlags object that represents all of the flags set for the ACE. This has several helper methods for extracting
useful information about the ACE:

```php
// Get the AceFlags object...
$flags = $ace->getFlags();

// The below functions can either check for a specific flag, or toggle it on/off with a bool true or false...

/**
 * Check or set whether the ACE does not control access to the object to which it is attached. When this is true,
 * the ACE only controls access on those objects which inherit it.
 */
$flags->inheritOnly($action = null);

/**
 * Check or set whether inheritance of this ACE should be propagated.
 */
$flags->propagateInheritance($action = null);

/**
 * Check or set whether containers should inherit this ACE.
 */
$flags->containerInherit($action = null);

/**
 * Check or set whether objects should inherit this ACE.
 */
$flags->objectInherit($action = null);

/**
 * Whether or not the ACE should generate audit messages for failed access attempts (only valid in the SACL).
 */
$flags->auditFailedAccess($action = null);

/**
 * Whether or not the ACE should generate audit messages for successful access attempts (only valid in the SACL).
 */
$flags->auditSuccessfulAccess($action = null);

/**
 * Check whether or not the ACE is inherited. This method can only be checked, not set.
 */
$flags->isInherited();
```

------------------------
#### setFlags(AceFlags $flags)

Set the AceFlags object assigned to the ACE.
 
```php
use LdapTools\Security\Ace\AceFlags;

$flags = new AceFlags(AceFlags::FLAGS['INHERIT_ONLY']);
$ace->setFlags($flags);
```

------------------------
#### isAllowAce()

A convenience method that will return true for any ACE type that allows access.

------------------------
#### isDenyAce()

A convenience method that will return true for any ACE type that denies access.

------------------------
#### isObjectAce()

A convenience method that will return true for any object type ACE.

------------------------
#### toBinary()

Returns the binary representation of the ACE.

------------------------
#### toSddl()

Returns the SDDL string representation of the ACE.

```php
echo $ace->toSddl();
```

## Parsing SDDL

Security Descriptor Definition Language (SDDL) is a string representation of a Security Descriptor. It may be necessary
, or easier, to represent a set of ACLs/ACEs in string form as you pass it around your application. To that end, this
library contains a SDDL parser capable of taking a SDDL string and producing a SecurityDescriptor object that contains
everything represented in the SDDL:

```php
use LdapTools\Security\SddlParser;
use LdapTools\Exception\SddlParserException;

// The following SDDL string represents the default Exchange Security Descriptor...
$sddl = 'O:PSG:PSD:(A;CI;RCCC;;;PS)';

// Parse will return a "\LdapTools\Security\SecurityDescriptor" object, as described in earlier sections...
try {
    $sd = (new SddlParser())->parse($sddl);
} catch (SddlParserException $e) {
    echo "Error Parsing SDDL: ".$e->getMessage();
    exit;
}

echo "Owner: ".$sd->getOwner();
echo "Group: ".$sd->getGroup();
foreach($sd->getDacl()->getAces() as $ace) {
    echo sprintf('Ace type "%s" for "%s"', $ace->getType(), $ace->getTrustee());
}
```

When you are parsing a more complex SDDL string, you may need to instantiate the parser with an LdapConnection instance
from the LdapManager of this library. It is needed when it translates certain well-known SIDs that relate to domain
objects;

```php
use LdapTools\Security\SddlParser;
use LdapTools\Exception\SddlParserException;

// The 'DA' short name needs a lookup to find the Domain Admin group SID... 
$sddl = 'O:DAG:DAD:(A;CI;RCCC;;;DA)';

try {
    // Assumes $ldap is the LdapManager instance...
    $sd = (new SddlParser($ldap->getConnection()))->parse($sddl);
} catch (SddlParserException $e) {
    echo "Error Parsing SDDL: ".$e->getMessage();
    exit;
}
```
