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

This method creates an authentication operation object and executes it against the current connection. You could also
do the following:

```php
use LdapTools\Operation\AuthenticationOperation;

$operation = (new AuthenticationOperation())->setUsername($username)->setPassword($password);

// With your LdapManager class already instantiated...
$response = $ldapManager->getConnection()->execute($operation);

if (!$response->isAuthenticated()) {
    echo "Error validating password for '".$operation->getUsername()."': ".$response->getErrorMessage();
}
```

## Valid Username Formats

The `authenticate()` method username argument can be the same value types as the username you defined in your config. For
Active Directory, this means you can authenticate a user using either a UPN, a text SID, a text GUID, a
distinguished name, or just a normal username. With OpenLDAP the username must be a full DN. However, you can adjust the
`bind_format` option for the domain configuration to modify this behavior.

## Authentication Error Messages

There are many times where you may want to provide a more meaningful response as to why authentication for a user has
failed. This information is possible to get by passing additional optional variables to the `authenticate()` method.
 
```php
 
// With your LdapManager class already instantiated...
if (!$ldapManager->authenticate($username, $password, $message, $code)) {
     echo "Error ($code): $message";
}
```

When using Active Directory, the above can give you very helpful information as to why the user cannot log in. Such as a
disabled account, a locked account, or an account whose password needs to change before they can login again. The most
common error codes you may see in AD:

| Error Number | Constant | Description |
| ------------ | ----------- | ----------- |
| 1317 | `ACCOUNT_INVALID` | Account does not exist. |
| 1326 | `ACCOUNT_CREDENTIALS_INVALID` | Account password is invalid. |
| 1327 | `ACCOUNT_RESTRICTIONS` | Account Restrictions prevent this user from signing in. |
| 1328 | `ACCOUNT_RESTRICTIONS_TIME` | Time Restriction - The account cannot login at this time. |
| 1329 | `ACCOUNT_RESTRICTIONS_DEVICE` | Device Restriction - The account is not allowed to log on to this computer. |
| 1330 | `ACCOUNT_PASSWORD_EXPIRED` | The password for the account has expired. |
| 1331 | `ACCOUNT_DISABLED` | The account is currently disabled. |
| 1384 | `ACCOUNT_CONTEXT_IDS` | The account is a member of too many groups and cannot be logged on. |
| 1793 | `ACCOUNT_EXPIRED` | The account has expired. |
| 1907 | `ACCOUNT_PASSWORD_MUST_CHANGE` | The accounts password must change before it can login. |
| 1909 | `ACCOUNT_LOCKED` | The account is currently locked out. |

All constants are located in `\LdapTools\Connection\ADResponseCodes`. You should use those constants to compare against 
the received error number to take a specific action for an event.
