# Logging
---------

Logging is available for the following LDAP operations performed by the connection class: add, modify, delete, move, and
rename. To enable logging you must pass a logger to the `setLogger($logger)` method of the main `\LdapTools\Configuration`
class. The only requirement for the class is that it implements `\LdapTools\Log\LdapLoggerInterface`.
 
## Enabling Logging for the LdapManager
---------------------------------------

To enable logging for all connections in the LdapManager you can pass a class implementing `\LdapTools\Log\LdapLoggerInterface`
as the third argument to the constructor. There are two classes that implement this interface that come with LdapTools.
The simplest implementation is a logger that outputs log information to standard output using echo statements. The other
is a class that lets you chain several loggers into one.

```php
use LdapTools\Log\EchoLdapLogger;
use LdapTools\Log\LoggerChain;
use LdapTools\LdapManager;
use LdapTools\Configuration;

# Load your overall config
$config = new Configuration();

# ... add/set domain configuration, load from YML, etc...

# Adds a simple echo logger to the config
$logger = new EchoLdapLogger();
$config->setLogger($logger);

$ldap = new LdapManager($config);

# Add a logger chain so several loggers can be used at once
$logger = new LoggerChain();

# Add some loggers to the chain...
$logger->addLogger(new EchoLdapLogger());
$logger->addLogger(new EchoLdapLogger());
$config->setLogger($logger);

# You will now see duplicated echo statements as the result of both loggers.
# Not a very useful example, but demonstrates the capability for several logging mechanisms.
$ldap = new LdapManager($config);
```

## The LdapLoggerInterface and the LogOperation

To do your own logging you can extend the LdapLoggerInterface. This class has two method requirements: `start()` and `end()`.
The start method is called before the operation is executed by LDAP and the end method is called immediately afterwards.
Both methods are passed an instance of the `\LdapTools\Log\LogOperation` class. This contains all of the useful information
about what operation just executed.
