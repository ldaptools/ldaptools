# Creating AD Password Settings Objects (PSOs)
-----------------------

Password Settings Objects were introduced in Windows Server 2008 as part of the Fine Grained Password Policy changes.
These are objects that you can create to apply to security groups (recommended) or directly to users. This allows you to
define multiple password policies in a domain where as prior to this you were limited to what could be defined in the 
Default Domain Policy.

The PSOs can be easily created using a schema definition called `PSO`:

```php
use LdapTools\Configuration;
use LdapTools\Utilities\ADTimeSpan;
use LdapTools\LdapManager;

$config = (new Configuration())->load('/path/to/my/ldap.yml');
$ldap = new LdapManager($config);

// Create a basic PSO.
$ldap->createLdapObject()
    ->create('PSO')
    ->with([
        'name' => 'Employee Password Policy',
        'precedence' => 5,
        'lockoutDuration' => (new ADTimeSpan())->setMinutes(30),
        'lockoutObservationWindow' => (new ADTimeSpan())->setMinutes(30),
        'lockoutThreshold' => 6,
        'maximumPasswordAge' => (new ADTimeSpan())->setDays(90),
        'minimumPasswordAge' => (new ADTimeSpan())->setDays(3),
        'minimumPasswordLength' => 8,
        'passwordComplexity' => true,
        'passwordHistoryLength' => 10,
    ])
    ->execute();

// Create a more restrictive PSO tied to a specific security group.
$ldap->createLdapObject()
    ->create('PSO')
    ->with([
        'name' => 'Admin Password Policy',
        'precedence' => 1,
        'lockoutDuration' => (new ADTimeSpan())->setMinutes(60),
        'lockoutObservationWindow' => (new ADTimeSpan())->setMinutes(60),
        'lockoutThreshold' => 3,
        'maximumPasswordAge' => (new ADTimeSpan())->setDays(60),
        'minimumPasswordAge' => (new ADTimeSpan())->setDays(3),
        'minimumPasswordLength' => 12,
        'passwordComplexity' => true,
        'passwordHistoryLength' => 10,
        'appliesTo' => 'Network Administrators'     // This can be the name, SID, or GUID of a security group
    ])
    ->execute();
```

Unlike other default schema types, you do NOT have to specify a default OU/container for this object type. It will be
automatically placed in the `Password Settings Container` within your domain's system container. 

## Defining Time Spans and Durations
 
The time span values (lockout duration, password age, etc) are defined by using a special object `\LdapTools\Utilities\ADTimeSpan`.
This class can be instantiated to create many different time representations:

```php
use LdapTools\Utilities\ADTimeSpan;

// All of these values are cumulative.
$adTimeSpan = (new ADTimeSpan())
    ->setDays(30)
    ->setHours(5)
    ->setMinutes(30)
    ->setSeconds(10);

// Specify a 'Never' time span. Such as a lockout duration that NEVER expires.
// While not necessarily recommended, this can be done by doing the following.
$adTimeSpan = (new ADTimeSpan())->setNever(true);

// Specify no values to keep it as '0' which is interpreted as 'None' for some attributes.
$adTimeSpan = new ADTimeSpan();
```
