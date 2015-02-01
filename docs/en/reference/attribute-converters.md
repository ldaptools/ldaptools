# Attribute Converters
---------------------

Attribute converters are responsible for converting data from LDAP to the format you want it as in PHP, and vice-versa.
Several attribute converters are built-in and exist under the `\LdapTools\AttributeConverter` namespace. All attribute
converters have the methods `toLdap($value)` and `fromLdap($value)` that you can use to convert your data to the correct
form. Typically this is done automatically from the schema definition, but you can also use them on their own.
 
```php
use LdapTools\Factory\AttributeConvertFactory;
 
// will return the string form of an objectGuid binary value.
$guid = AttributeConverterFactory::get('convert_windows_guid')->fromLdap($value)
```


## Default Attribute Converters
-------------------------------

#### *convert_bool* 
  * `toLdap`: Converts a PHP bool to a string of 'TRUE' or 'FALSE'.

  * `fromLdap`: Converts a LDAP 'TRUE' or 'FALSE' string into a corresponding PHP bool.
  
#### *convert_int* 
  * `toLdap`: Converts a PHP int to a string.

  * `fromLdap`: Converts a LDAP numeric string to a PHP int.
  
#### *convert_string_to_utf8*
  * `toLdap`: Converts a string to a UTF8 encoded string.

  * `fromLdap`: Returns the UTF8 string from LDAP.
  
#### *convert_generalized_time*
  * `toLdap`: Converts a PHP `\DateTime` object to a generalized timestamp string.

  * `fromLdap`: Converts a LDAP generalized timestamp into a PHP `\DateTime` object.

#### *convert_windows_generalized_time*
  * `toLdap`: Converts a PHP `\DateTime` object to the generalized timestamp format string that Active Directory expects.
  
  * `fromLdap`: Converts an Active Directory generalized timestamp format to a PHP `\DateTime` object.

#### *convert_windows_guid*
  * `toLdap`: Converts a string GUID to an escaped hex sequence string.

  * `fromLdap`: Converts a binary GUID to its string representation.
  
#### *convert_windows_sid*
  * `toLdap`: Converts a string SID to an escaped hex sequence string.
  
  * `fromLdap`: Converts a binary SID to its string representation.
  
#### *convert_windows_time*
  * `toLdap`: Converts a PHP `\DateTime` object into a string representation of Windows time (nanoseconds).
  
  * `fromLdap`: Converts a Windows timestamp into a PHP `\DateTime` object.
  
#### *encode_windows_password*
  * `toLdap`: Encodes a string to its unicodePwd representation, which is a quote encased UTF-16LE encoded value.
  
  * `fromLdap`: This will not do anything since a unicodePwd attribute cannot be queried.
