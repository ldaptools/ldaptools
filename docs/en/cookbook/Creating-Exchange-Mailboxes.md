# Creating Exchange Mailboxes
------------------------------

Creating an Exchange Mailbox User is a pretty simple process with the built-in Exchange schema types. All you need to create
the mailbox is the: username, password, an Exchange Database name, and an Exchange Server name. All of the rest is optional.
This should work for creating mailboxes on Exchange 2007, 2010, 2013, and 2016.

1. Find an Exchange Database for the mailbox

```php
use LdapTools\Object\LdapObjectType;

// ...

// Find an Exchange Database by its common name...
$database = $ldap->buildLdapQuery()
    ->from(LdapObjectType::EXCHANGE_DATABASE)
    ->where(['name' => 'Default Database'])
    ->getLdapQuery()
    ->getSingleResult();
    
// Not sure of the names? List them all...
$databases = $ldap->buildLdapQuery()
     ->select('name')
     ->from(LdapObjectType::EXCHANGE_DATABASE)
     ->getLdapQuery()
     ->getArrayResult();

var_dump($databases);
```

2. Find an Exchange Server for the mailbox

```php
use LdapTools\Object\LdapObjectType;

// ...

// Find an Exchange Server by its common name...
$server = $ldap->buildLdapQuery()
    ->from(LdapObjectType::EXCHANGE_SERVER)
    ->where(['name' => 'EXCHANGE01'])
    ->getLdapQuery()
    ->getSingleResult();
    
// Not sure of the server names? List them all...
$servers = $ldap->buildLdapQuery()
     ->select('name')
     ->from(LdapObjectType::EXCHANGE_SERVER)
     ->getLdapQuery()
     ->getArrayResult();

var_dump($servers);
```

3. Create the Mailbox

```php
use LdapTools\Object\LdapObjectType;

// ...

$ldap->createLdapObject(LdapObjectType::EXCHANGE_MAILBOX_USER)
    ->with([
        'username' => 'SomeGuy',
        'password' => 'P@ssword123',
        // Pass the objects from the above queries. Could also use the: name, guid, or dn
        'mailboxDatabase' => $database,
        'mailboxServer' => $server
    ])
    ->in('dc=example,dc=local')
    ->execute();
```

 For a complete listing of what attributes you can specify in the creation command above [see schema reference doc](../reference/Default-Schema-Attributes.md#exchange-mailbox-user-types).
 Since the Exchange Mailbox User type extends the default AD type, you can also use any attribute from that type on
 creation as well.
 
 However, the account creating the mailboxes will still need the appropriate permissions in order for the above to work.
 