# Main Configuration
--------------------

You can either choose to configure your settings via a file (YAML) or directly using the methods on the `Configuration` 
and `DomainConfiguration` classes. Once you have an instance of the configuration, you can pass it to the `LdapManager`
class.
 
## Manual Configuration
------------------------
 
```php
use LdapTools\Configuration;
use LdapTools\DomainConfiguration;
use LdapTools\LdapManager;
 
// A new configuration object contains the most common default settings.
$config = new Configuration();

// A domain configuration object. Requires a domain name, servers, username, and password. 
$domain = (new DomainConfiguration('mydomain.com'))
   ->setBaseDn('dc=mydomain,dc=com')
   ->setServers(['dc01'])
   ->setUsername('username')
   ->setPassword('password');
$altDomain = (new DomainConfiguration('foo.bar'))
   ->setBaseDn('dc=foo,dc=bar')
   ->setServers(['foo'])
   ->setUsername('username')
   ->setPassword('password')
   ->setLazyBind(true)
   ->setLdapType('openldap');
$config->addDomain($domain, $altDomain);
// Defaults to the first domain added. You can change this if you want.
$config->setDefaultDomain('foo.bar');

// The LdapManager provides an easy point of access to some different classes.
$ldap = new LdapManager($config);
```

## YAML Configuration
----------------------

The easy way to configure everything is by using a single YAML configuration file. See the [example configuration](https://github.com/ldaptools/ldaptools/blob/master/resources/config/example.yml)
for a detailed overview. The YAML file is split into a `general` section and a `domains` section. 

```yaml
general:
...
domains:
    domain_one:
    ...
    domain_two:
    ...
```

Once you have a YAML configuration file defined, you can configure the class very easily:

```php
use LdapTools\Configuration;
use LdapTools\LdapManager;

$config = (new Configuration())->load('/path/to/ldap/config.yml');
$ldap = new LdapManager($config);
```

The below reference describes each possible configuration directive.

### **General Section**

--------------------
#### default_domain

If you have added more than one domain configuration, set this to the domain name (ie. `example.com`) you would like to 
be the default context when using the `LdapManager` class.

 **Default**: If more than one domain is present, the first domain added is the default domain.
 
 ------------------
#### schema_format

The format that the schema file is in. Only `yml` is available at present.

**Default**: `yml`

-------------------
#### schema_folder

This is where the LDAP object schema definition files are stored.

**Default**: The `resources/schema` folder in the libraries root directory.

----------------
#### cache_type

The default caching mechanism to use when parsing schema files. Options are `stash` or `none`. When `stash` is used it
will take the parsed LDAP schema objects and cache them to disk. It will then use the cache instead of re-parsing the
schema each time. If it detects that the schema file has been modified, it will re-parse it and cache it again.

To use the `stash` type you must install [Stash](https://github.com/tedious/Stash).

**Default:** `none`

-------------------
#### cache_options

An array of options that will be passed to the cache type when it is instantiated.

**Defaults**: No options are passed by default.

-------------------
#### attribute_converters

An array of convert to class name mappings that will be registered in the `LdapManager` for use in the schemas.

```yaml
general:
    attribute_converters:
        # This class must extend \LdapTools\AttributeConverter\AttributeConverterInterface !
        my_converter: '\My\Converter\Class'
```

**Defaults**: No additional attribute converters are registered by default.

-------------------

### **Domain Section**
----------------------

#### domain_name **(REQUIRED)**

The FQDN of the domain (ie. `example.com`).

------------------------------
#### base_dn **(REQUIRED)**

The base DN for searches (ie. `dc=example,dc=com`).

-------------------------------
#### username **(REQUIRED)**

The username to use when binding to LDAP. When using Active Directory, the username can be in any of these formats:

  * A typical username in UPN form (ie. `user@domain.com`).
  * A string GUID of an account (ie. `8227ab9b-b307-45eb-a50c-6f6cb3946318`)
  * A string SID of an account (ie. `S-1-5-21-1004336348-1177238915-682003330-512`)
  * The full distinguished name of an account.

If none of those forms are detected, then by default it will force the username into UPN form based off of the domain
name. However, if the LDAP type is `openldap`, then it will just pass the unmodified username along. This behavior can 
be modified using the `bind_format` option.

-------------------------------
#### password **(REQUIRED)**

The password to use when binding to LDAP.

-------------------------------
#### servers **(REQUIRED)**

An array of LDAP servers (ie. `[ 'dc01' ]`). When more than one server name is used it will attempt each one until it
successfully connects.

-------------------------------
#### bind_format

Defines how the username will be passed to LDAP on a bind/authentication attempt. This is a string that accepts 2
parameters: `%username%` and `%domainname%`. By default, the AD bind format is `%username%@%domainname%`. With OpenLDAP
it is simply `%username%`. However, you could set it to an DN path, such as: `CN=%username%,OU=Users,DC=example,DC=com`

-------------------------------
#### server_selection

When more than one server is listed for a domain, choose which one is selected for the connection. The possible choices 
are `order` (tried in the order they appear) or `random`. 

**Default**: `order`

-------------------------------
#### page_size

The default page size to use for paging operations.

**Default**: `1000`

-------------------------------
#### port

The default port number to connect to LDAP on.

**Default**: `389`

-------------------------------
#### use_ssl

Whether or not to talk to LDAP over SSL. The default is `false`. Typically you want to use the `use_tls` directive (in
the case of Active Directory). Setting this to `true` also changes the port to `636`.

**Default**: `false`

-------------------------------
#### use_tls

Whether or not to initiate TLS when connecting to LDAP. This is required for certain LDAP operations (such as password 
changes in Active Directory).

**Default**: `false`

-------------------------------
#### ldap_type

The LDAP type for this domain. Choices are `ad` or `openldap`.

**Default**: `ad`

-------------------------------
#### lazy_bind

If set to `true`, then the connection will not automatically connect and bind when first created.

**Default**: `false`

-------------------------------
#### schema_name

The schema name to use for this domain. This typically refers to the name of the schema file to use within the path 
defined by the `schema_folder` directive in the general section. 

**Default**: The same value set for `ldap_type`.

-------------------------------
#### encoding

The encoding to use for this domain. Usernames, passwords, and any values not explicitly assigned to an Attribute
Converter will be encoded with this encoding choice.

**Default**: UTF-8
