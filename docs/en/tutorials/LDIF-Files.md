# LDIF Files
------------------------

* [LDIF Parsing](#ldif-parsing)
* [LDIF URL Loaders](#ldif-url-loaders)
* [LDIF Creation](#ldif-creation)
* [The LDIF Object](#the-ldif-object)
* [LDIF Line Endings](#ldif-line-endings)
* [Line Folding and Max Line Length](#line-folding-and-max-line-length)

LdapTools provides an easy method to both parse and create LDIF files. The parser is able to take a LDIF string and
return an object that allows you to get all the entries it contains. From the object you can get all the LDAP operations
represented by those entries and enter them into LDAP. You also have the ability to build a LDIF file in an 
object-oriented way and then output the result to a LDIF string.

## LDIF Parsing

To parse a LDIF file you can pass the contents of it to the parser then do what you need with the LDIF object returned:

```php
use LdapTools\Ldif\LdifParser;
use LdapTools\Exception\LdifParserException;

$parser = new LdifParser();

try {
    $ldif = $parser->parse(file_get_contents('/path/to/ldif.txt'));
} catch (LdifParserException $e) {
    echo "Error Parsing LDIF: ".$e->getMessage();
}

// Assuming $ldap is your LdapManager instance, execute the LDIF entries into LDAP...
foreach ($ldif->toOperations() as $operation) {
    $ldap->getConnection()->execute($operation);
}
```

### LDIF URL Loaders

In a LDIF file you can specify the data of an attributes value from a remote source using a URL format such as:

```ldif
description:< file:///some/path/to/data.txt
```

The valid default URL types recognized by the parser are: `file`, `http`, and `https`. You can implement your own URL
loader by creating a class that implements `LdapTools\Ldif\UrlLoader\UrlLoaderInterface`. The only needed method to be
implemented is `load($url)`. You can then add the URL loader to the parser using the `setUrlLoader($type, $loader)`
method.

```php
use LdapTools\Ldif\LdifParser;

$parser = new LdifParser();

// Add a custom URL loader. The $type is the string at the beginning of the URL, such as 'file' or 'http'.
// The $loader is your constructed class that implements 'LdapTools\Ldif\UrlLoader\UrlLoaderInterface'.
$parser->setUrlLoader($type, $loader);

// Remove a URL loader by type if you don't want it to be supported...
$parser->removeUrlLoader($type);

// Check if a URL loader for a specific type exists...
if ($parser->hasUrlLoader($type)) {
   // do something...
}
```

## LDIF Creation

You can easily construct LDIF files by using a few classes. The easiest way to construct a LDIF file is in the context
of your LDAP manager object:

```php
use LdapTools\Object\LdapObjectType;

// Assuming $ldap is your LdapManager instance. 
// The 'add' and 'modify' LDIF entries are schema aware when you constructed using your LdapManager...
$ldif = $ldap->createLdif();

$userEntry = $ldif->entry()->add()
    ->setType(LdapObjectType::USER)
    ->setAttributes(['username' => 'Jimmy', 'password' => '12345'])
    ->setLocation('ou=employees,dc=example,dc=local');

$ldif->addEntry(
    $userEntry,
    $ldif->entry()->delete('cn=Some User,dc=foo,dc=bar'),
    $ldif->entry()->move('cn=Frank,dc=foo,dc=bar', 'ou=Employees,dc=foo,dc=bar')
);

// Output the LDIF object as a string to a file.
file_put_contents('/path/to/ldif.txt', $ldif->toString());
```

However, you can also build LDIF files by just constructing the class and passing whatever you want:

```php
use LdapTools\Ldif\Ldif;

$ldif = new Ldif();

$ldif->addEntry(
    $ldif->entry()->add('cn=Some Group,dc=foo,dc=bar', [
        'sAMAccountName' => 'Some Group',
        'objectClass' => 'group'
    ]),
    $ldif->entry()->delete('cn=Some User,dc=foo,dc=bar'),
    $ldif->entry()->move(
        'cn=Frank,dc=foo,dc=bar',
        'ou=Employees,dc=foo,dc=bar'
    )
);

// Output the LDIF object as a string to a file.
file_put_contents('/path/to/ldif.txt', $ldif->toString());
```

The above is the general method for creating a LDIF file. But there are a lot of the methods and options available.
Below is a summary of some of the objects and methods involved and how to use them.

------------------------
### The LDIF Object

The LDIF object is used to represent the LDIF file in its entirety. When you parse a LDIF file via the parser it returns
this object. Likewise, you can construct a LDIF object to get the LDIF string representation from it.

```php
use LdapTools\Ldif\Ldif;

// Construct the LDIF object...
$ldif = new Ldif();

// By default the LDIF version is set to '1' to follow the RFC. 
// You can remove it by setting it to null.
$ldif->setVersion(null);

// Add a few comments to appear at the top of the LDIF...
$ldif->addComment('This is just a test LDIF file', 'Created on '.date("m.d.y"));

// Create a new entry for the LDIF by using the helper 'entry()' method.
// This creates an entry that will add a new object to LDAP.
$entry = $ldif->entry()->add('cn=Some User,dc=example,dc=local')->addAttribute('sn', 'Sikorra');

// Add the created entry to the LDIF. This method is variadic, so add as many entries at a time as you like.
$ldif->addEntry($entry);

// Output the LDIF to a string and do whatever you need with it...
$ldifData = $ldif->toString();
echo $ldifData;
```

### The LDIF Delete Entry Type

The delete entry type is a `delete` changetype in LDIF. It is used to delete an object from LDAP. All you need to
do is pass it the full DN of the object you'd like to delete.

```php
use LdapTools\Ldif\Entry\LdifDeleteEntry;

$dn = 'cn=foo,dc=example,dc=local';

// Either construct it on your own...
$delete = new LdifEntryDelete($dn);

// Or construct it with the helper method on the LDIF object...
$delete = $ldif->entry()->delete($dn);
```

### The LDIF Add Entry Type

The add entry type is an 'add' changetype in LDIF. It is used to add a new object to LDAP. It needs the full DN
and all the attributes.

```php
use LdapTools\Ldif\Entry\LdifEntryAdd;

$dn = 'cn=foo,dc=example,dc=local';
$attributes = [
    'objectclass' => ['top', 'person', 'organizationalPerson'],
    'cn' => 'Barbara Jensen',
    'sn' => 'Jensen',
    'uid' => 'bjensen',
    'telephonenumber' => '+1 408 555 1212',
    'description' => "Peon",
    'title' => 'Awesome Stuff',
];

// Construct the entry on your own...
$add = new LdifEntryAdd($dn, $attributes);

// Or construct it with the helper method on the LDIF object...
$add = $ldif->entry()->add($dn)->setAttributes($attributes);
 
// Construct the entry from the helper method and use the methods on the add entry type to add individual attributes...
$add = $ldif->entry()->add($dn)
    ->addAttribute('objectClass', ['top', 'person', 'organizationalPerson'])
    ->addAttribute('sn', 'Jensen')
    ->addAttribute('description', 'Peon');
```

### The LDIF Modify Entry Type

The modify entry type is a 'modify' changetype in LDIF. You can use this to modify an already existing object in LDAP.

```php
use LdapTools\Ldif\Entry\LdifEntryModify;

$dn = 'cn=foo,dc=example,dc=local';

// Construct it manually...
$modify = new LdifEntryModify($dn);

$modify->replace('description', 'Works at building 2') // Replace the contents of an attribute.
    ->reset('title')                                   // Reset the attribute, which removes any value it has.
    ->add('telephonenumber', '555-5555')               // Add a new value to an attribute.
    ->delete('faxnumber', '222-2222');                 // Delete a specific attribute value. The value must exist.

// Use the helper method on the LDIF object...
$modify = $ldif->entry()->modify($dn)
    ->delete('faxnumber', '222-2222')
    ->replace('sn', 'Johnson');

```

### The LDIF Mod DN Entry Type

The Mod DN entry type is a 'moddn' changetype in LDIF. This allows you to rename, move, or add to the DN of an object in
LDAP.

```php
use LdapTools\Ldif\Entry\LdifEntryModDn;

$dn = 'cn=foo,dc=example,dc=local';

// Construct it manually...
$moddn = new LdifEntryModDn($dn);

// Move it to a new location. Specify the full DN of the new location:
$moddn->setNewLocation('ou=employees,dc=example,dc=local');

// Tell it that the old RDN from the previous location should be removed:
$moddn->setDeleteOldRdn(true);

// Set a new name (RDN) for the object (Needs to be in 'attribute=value' format!):
$moddn->setNewName('cn=bar');

// Constructing the entry using the helper method on the LDIF object...
$moddn = $ldif->entry()->moddn($dn)
    ->setNewLocation('ou=employees,dc=example,dc=local')
    ->setDeleteOldRdn(true)
    ->setNewName('cn=bar');
```

### The LDIF Mod RDN Type

The Mod RDN type is implemented to adhere to RFC 2849. There is no functional difference between the 'modrdn' and 'moddn'
changetypes. The class for 'modrdn' simply extends the class for the 'moddn' type. The only difference being that when
it is output as a LDIF string the changetype will be 'modrdn':

```php
use LdapTools\Ldif\Entry\LdifEntryModRdn;

$modrdn = new LdifEntryModRdn('cn=foo,dc=example,dc=local');
$modrdn->newLocation($someOU);

// Outputs a 'changetype' of 'modrdn'...
echo $modrdn->toString();
```

### Helper Methods to Create LDIF Entries

An easy way to construct the LDIF entries is to use the 'entry()' method of the LDIF object. This takes care of some of
the manual object construction so you don't have to remember class names and produces a more fluent object-oriented
method to build up the LDIF object.

```php
use LdapTools\Ldif\Ldif;

$ldif = new Ldif();

// An entry to delete a LDAP object...
$ldif->addEntry($ldif->entry()->delete('cn=user,dc=example,dc=local'));

// An entry to add a new object to LDAP...
$ldif->addEntry($ldif->entry()->add('cn=Josh,ou=employees,dc=example,dc=local')->setAttributes([
    'objectclass' => ['top', 'person', 'organizationalPerson'],
    'cn' => 'Josh Jensen',
    'sn' => 'Jensen',
    'uid' => 'jjensen',
]));

// Modify an existing LDAP object...
$modify = $ldif->entry()->modify('cn=foo,dc=example,dc=local')
    ->addAttribute('phonenumber', '555-5555')
    ->replace('title', 'worker');
$ldif->addEntry($modify);

// Rename an object in LDAP...
$ldif->addEntry($ldif->entry()->rename('cn=josh,dc=example,dc=local', 'cn=jill'));

// Move an object in LDAP...
$ldif->addEntry($ldif->entry()->move('cn=jill,dc=example,dc=local', 'ou=employees,dc=example,dc=local'));

// Modify the DN of an LDAP object...
$ldif->addEntry($ldif->entry()->moddn('cn=jill,ou=employees,dc=example,dc=local')->setNewRdn('cn=jill'));
```

### LDIF Line Endings

By default when you call `toString()` on an LDIF object all of the line endings will be in Windows format (CRLF). If you
want to use Unix/Linux format (LF) you can use the `setLineEnding()` method:

```php
use LdapTools\Ldif\Ldif;

$ldif = $ldap->createLdif();

$ldif->setLineEnding(Ldif::LINE_ENDING['UNIX']);
```

This is mostly useful for compatibility purposes. Some utilities consuming the LDIF file may be expecting a certain line
ending in order to work correctly.

### Line Folding and Max Line Length

By default when you call `toString()` on an LDIF object it will not fold any lines regardless of how long they are. If
you would like for the long lines to be folded and continued on the next line after a certain length (to improve the
readability of the file) you can use the `setLineFolding(true)` and `setMaxLineLength($length)` methods:
 
```php
use LdapTools\Ldif\Ldif;

$ldif = $ldap->createLdif();

# Long line (including comments) will be broken into smaller chunks now...
$ldif->setLineFolding(true);
# Unless this is explicitly set, the max line length is 76 by default.
$ldif->setMaxLineLength(100);
``` 
