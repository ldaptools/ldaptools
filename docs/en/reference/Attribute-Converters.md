# Attribute Converters
---------------------

Attribute converters are responsible for converting data from LDAP to the format you want it as in PHP, and vice-versa.
Several attribute converters are built-in and exist under the `\LdapTools\AttributeConverter` namespace. All attribute
converters have the methods `toLdap($value)` and `fromLdap($value)` that you can use to convert your data to the correct
form. Typically this is done automatically from the schema definition, but you can also use them on their own as long as
they don't have a dependency on the `LdapConnection` to make the data conversion.
 
```php
use LdapTools\Factory\AttributeConvertFactory;
 
// will return the string form of an objectGuid binary value.
$guid = AttributeConverterFactory::get('windows_guid')->fromLdap($value)
```

The `AttributeConverterInterface` requires a method called `setLdapConnection`. This is called during the data hydration
process and the converter may use the connection to make LDAP calls to resolve data as necessary.

## Default Attribute Converters
-------------------------------

#### *bool* 
  * `toLdap`: Converts a PHP bool to a string of 'TRUE' or 'FALSE'.

  * `fromLdap`: Converts a LDAP 'TRUE' or 'FALSE' string into a corresponding PHP bool.
  
#### *int* 
  * `toLdap`: Converts a PHP int to a string.

  * `fromLdap`: Converts a LDAP numeric string to a PHP int.

#### *flags*
  * `toLdap`: Takes a bool for the current attribute and flips the bit of the overall flag value going to LDAP.
  
  * `fromLdap`: Returns a bool based on whether the current attribute bit is set in the flag value from LDAP.

#### *generalized_time*
  * `toLdap`: Converts a PHP `\DateTime` object to a generalized timestamp string.

  * `fromLdap`: Converts a LDAP generalized timestamp into a PHP `\DateTime` object.

#### *password_must_change*
  * `toLdap`: Converts a PHP bool to either a -1 (false) or 0 (true). This toggles the "Password Must Change" property.
  
  * `fromLdap`: Converts a LDAP '0' (true - password must change) or '>0' (false - password doesn't have to change).
   
#### *windows_generalized_time*
  * `toLdap`: Converts a PHP `\DateTime` object to the generalized timestamp format string that Active Directory expects.
  
  * `fromLdap`: Converts an Active Directory generalized timestamp format to a PHP `\DateTime` object.

#### *windows_guid*
  * `toLdap`: Converts a string GUID to an escaped hex sequence string.

  * `fromLdap`: Converts a binary GUID to its string representation.
  
#### *windows_sid*
  * `toLdap`: Converts a string SID to an escaped hex sequence string.
  
  * `fromLdap`: Converts a binary SID to its string representation.
  
#### *windows_time*
  * `toLdap`: Converts a PHP `\DateTime` object into a string representation of Windows time (nanoseconds).
  
  * `fromLdap`: Converts a Windows timestamp into a PHP `\DateTime` object.

#### *windows_security*
  * `toLdap`: Converts a SDDL string or `LdapTools\Security\SecurityDescriptor` object to binary format.
  
  * `fromLdap`: Returns a `LdapTools\Security\SecurityDescriptor` object representing the binary data.

#### *encode_windows_password*
  * `toLdap`: Encodes a string to its unicodePwd representation, which is a quote encased UTF-16LE encoded value.
  
  * `fromLdap`: This will not do anything since a unicodePwd attribute cannot be queried.
  
#### *exchange_proxy_address*
  * `toLdap`: Takes an array of email addresses and formats them properly for the proxyAddresses attribute.
  
  * `fromLdap`: Parses through the proxyAddresses attribute to return only the address portion for a specific address type.

#### *exchange_recipient_type*
  * `toLdap`: Takes an integer and maps it to the friendly display name of the recipient type info.
  
  * `fromLdap`: Takes a friendly recipient type name and converts it to the integer value.

#### *exchange_object_version*
  * `toLdap`: Takes a friendly Exchange version (ie. 2013, 2016, etc) and maps it to the object version number.
  
  * `fromLdap`: Takes an Exchange object version number and maps it to the simple major version (ie. 2010, 2013).

#### *exchange_legacy_dn*
  * `toLdap`: Should be passed the term `auto:username`. Where `username` is the actual username of the user.
  
  * `fromLdap`: Returns the unmodified legacyExchangeDn value.

#### *exchange_recipient_policy*
  * `toLdap`: Takes a set of recipient policies (dn, GUID, LdapObject) and converts it to the value needed for LDAP.
  
  * `fromLdap`: Converts the recipient policy GUIDs to their friendly policy names.

#### *logon_workstations*
  * `toLdap`: Takes an array of computer names and formats it as a comma-separated list for LDAP.
  
  * `fromLdap`: Formats the comma-separated list from LDAP as an array of computer names.
  
#### *account_expires*
  * `toLdap`: Takes either a bool false (never expires) or a `\DateTime` object for when it should expire.
  
  * `fromLdap`: Will either be false (never expires) or a `\DateTime` object of when it will expire.
  
#### *group_type*
  * `toLdap`: Takes a bool and switches the group between domain local, universal, global, security, or distribution.

  * `fromLdap`: Depending on the attribute, does a bitwise conversion to set a specific type as true or false.

#### *gpo_link*
  * `toLdap`: Takes an array of `\LdapTools\Utilities\GPOLink` objects to resolve GPOs by name/GUID/DN/LdapObject, and returns a valid string for the gPLink attribute.

  * `fromLdap`: Takes a gPLink attribute string, splits it into the distinct GPOs, and returns an array of `\LdapTools\Utilities\GPOLink` objects.

#### *value_to_dn*
  * `toLdap`: Takes an objects GUID, SID, DN, or name and returns the full distinguished name after validating it exists.

  * `fromLdap`: Returns a common name for the object instead of the full distinguished name.

#### *primary_group*
  * `toLdap`: Takes a group name and converts it to its RID decimal value for the primaryGroupID attribute.

  * `fromLdap`: Takes the RID of a group and returns the group's name.

#### *functional_level*
  * `toLdap`: This should not be used. You should only read the value from LDAP.
  
  * `fromLdap`: Takes an Active Directory functional level as an int and converts it to a human readable form.

#### *gpoptions*
  * `toLdap`: Takes a bool and converts it to a string int to represent whether inheritance is blocked on an OU. 
  
  * `fromLdap`: Takes a gpOptions value and converts it to a bool for whether GPO inheritance is set to be blocked.

#### *exchange_roles*
  * `toLdap`: This should not be used. It currently does not modify the value going back to LDAP.
  
  * `fromLdap`: Takes an integer and and determines what roles it contains and converts that to an array of role names.
  
#### *exchange_version*
  * `toLdap`: This should not be used. It currently does not modify the value going back to LDAP.

  * `fromLdap`: Takes a build string stored in the serialNumber attribute and parses it to a readable Exchange version.

#### *ldap_type*
  * `toLdap`: This should not be used. It is currently unsupported.

  * `fromLdap`: This returns the LDAP Object Type string represented by the `LdapTools\Object\LdapObjectType` constants.
