# Configuration
---------------

You can either choose to configure your settings via a file (YAML) or directly using the methods on the `Configuration` 
and `DomainConfiguration` classes. Once you have an instance of the configuration, you can pass it to the `LdapManager`
class.
 
### Manual Configuration
 
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

### YAML Configuration

The easy way to configure everything is by using a single YAML configuration file. See the [example configuration](/resources/config/example.yml)
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

##### General Section

* **default_domain**: If you have added more than one domain configuration, set this to the domain name you would like to be
 the default context when using the `LdapManager` class.
 
* **server_selection**: When more than one server is listed for a domain, choose which one is selected for the 
 connection. The possible choices are `order` (tried in the order they appear), `random`. Default is `order`
 
* **schema_format**: The format that the schema file is in. Default (and only current option) is `yml`

* **schema_folder**: This is where the LDAP object schema definition files are stored. It defaults to the 
`resources/schema` folder in the libraries root directory.

* **cache_type**: The default caching mechanism to use when parsing schema files. Options are `stash` or `none`. The
default is `none`.

* **cache_options**: An array of options that will be passed to the cache type when it is instantiated.

##### Domain Section

* **domain_name**: The FQDN of the domain (ie. `example.com`).

* **base_dn**: The base DN for searches (ie. `dc=example,dc=com`).

* **servers**: An array of LDAP servers (ie. `[ 'dc01' ]`).

* **page_size**: The default page size to use for paging operations. The default is `1000`.

* **port**: The default port number to connect to LDAP on. The default is `389`.

* **use_ssl**: Whether or not to talk to LDAP over SSL. The default is `false`. Typically you want to use the `use_tls`
directive (in the case of Active Directory). Setting this to `true` also changes the port to `636`.

* **use_tls**: Whether or not to initiate TLS when connecting to LDAP. This is required for certain LDAP operations
(such as password changes in Active Directory). The default is `false`.

* **username**: The username to use when binding to LDAP.

* **password**: The password to use when binding to LDAP.

* **ldap_type**: The LDAP type for this domain. Choices are `ad` or `openldap`. Defaults to `ad`.

* **lazy_bind**: If set to `true`, then the connection will not automatically connect and bind when first created. The
default is `false`.

* **schema_name**: The schema name to use for this domain. This typically refers to the name of the schema file to use
within the path defined by the `schema_folder` directive in the general section. The default is to be the same name as
the `ldap_type`.
