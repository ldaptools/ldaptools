# Authenticating a User
-----------------------

Often times you may want to simply test a username/password against LDAP to see if it is valid. This can be done with a
shorthand method directly on the `LdapManager` class:

```php

// With your LdapManager class already instantiated...
if ($ldapManager->authenticate($username, $password)) {
    echo "Success! The password for $username is correct.";
}
```

This method actually uses the already existing `authenticate()` method of the `LdapConnection` class in the current
context and is included as a quick shortcut for not having to do `$ldapManager->getConnection()->authenticate($username, $password)`.