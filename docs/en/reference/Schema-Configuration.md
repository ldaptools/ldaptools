# Schema Configuration
----------------------

* [Schema File Structure](#schema-file-structure-yaml)
* [General Schema File Options](#schema-configuration-options)
* [Schema Object Type Options](#schema-object-configuration-options)

LdapTools uses a schema definition to describe various LDAP objects in your directory service. This allows you to easily
create and modify any part of the schema to suit your needs. Default schema definitions are included in the 
`resources/schema` directory in the root of this library. Both OpenLDAP and Active Directory have default schema files.

The schema definition allows you to abstract the LDAP schema so you can refer to objects by whatever name and attribute
names that you want within this class. It also allows you to assign attribute converters to LDAP attributes so you can 
convert the data in LDAP to the way you want it to be displayed in PHP, and vice-versa.

The following serves as a reference for the structure of a schema and the possible configuration directives.
 
## Schema File Structure (YAML)

The schema YAML file is structured as follows:

```yaml
# This name must always be defined
objects:
    # This name can be whatever you want, but must be unique for this section of the YAML.
    user_object:
        # ... Schema object directives are defined here
    # Another schema object definition
    group_object:
        # ... More object directives are defined here
```

### Schema Configuration Options
--------------------------------

#### objects ***(Required)***

Underneath the `objects` is where you define all your schema object definitions. This option must be defined.

#### extends_default
--------------------

By using this option you can specify the name of one of the default schemas to extend. Any options contained within that
schema will be merged into your schema. To add additional options to existing schema types the keys underneath "objects"
must match between the default schema and your own!

```yaml
# Extends the default 'ad' schema included within the 'resources/schema' directory.
extends_default: ad
objects:
    # This 'user' key must exist in the default schema for it to merge, otherwise it will be considered a new object type.
    user:
        # Sets a custom repository definition for the user object, in addition to everything else in the default schema.
        repository: '\My\Custom\Repository'
```

#### include
--------------------

By using this option you can specify additional schema files to include within the current schema file. This way you can
logically separate your schema files and include them as needed. Note that the schema needs to exist within the folder
path you defined for your schema in the configuration.

```yaml
# Includes a schema named 'custom'. This needs to exist within the schema folder you defined in your config settings.
include: custom
objects:
    # Include any additional schema object definitions ...
```

#### include_default
--------------------

By using this option you can specify additional schema files to include within the current schema file that exist within
the default schema folder. The only valid schema names for this are those that are included within this libraries default
schema folder.

```yaml
include_default: exchange
objects:
    # Include any additional schema object definitions ...
```

### Schema Object Configuration Options
---------------------------------------

#### type ***(Required)***

The name for the type is how you will refer to this LDAP schema object within the class. This is a required field. 
Default LDAP object types that the class has defined are: `user`, `group`, `computer`, `contact`.

--------------------
#### filter
 
This is an instance of `\LdapTools\Query\Operator\BaseOperator`. This operator is used to construct the filter used to
query LDAP for the object type. You can omit the `class` and `category` directives and only define a filter. If you
also define a `class` and `category` they are added to this filter when the schema object is built.

A simple filter example. This would select all objects with an objectClass equal to user that start with the name Admin.

```yaml
    filter:
        eq: [ objectClass, user ]
        starts_with: [ name, Admin ]
```

A more complex example. All objects with an objectClass of user and an objectCategory person, and of those only objects
with an email address present whose department name begins with IT.

```yaml
    filter:
        - and:
            - eq: [ objectClass, user ]
            - eq: [ objectCategory, person ]
        - and:
            - present: emailAddress
            - starts_with: [ department, IT ]
```

This allows you to construct a query as complex as you need in an array representation. All of the methods used to
construct the filter above (`and`, `eq`, `present`, `starts_with`) are methods of the filter shortcuts from the
LdapQueryBuilder class. The only difference being that instead of camel-case the method names should be formatted with
underscores.

For a complete list of methods available see the [filter shortcuts documentation](../tutorials/Building-LDAP-Queries.md#filter-method-shortcuts).

--------------------
#### class
 
This is the `objectClass` value for the LDAP object you're defining. It can be any valid LDAP objectClass value (`user`,
`inetOrgPerson`, `group`, etc) and will be used in the creation of LDAP query filters when using this type. This can be
either a single string, or an array with multiple class names.

--------------------
#### category

This is the `objectCategory` value for the LDAP object you're defining. It can be any valid LDAP
objectCategory value (`person`, `computer`, `contact`, etc) and will be used in the creation of LDAP query filters 
(along with the `class` definition above) when using this type.

--------------------
#### rdn

This is the RDN attribute used to form the distinguished name of an object. You can define one or more attributes for
this role. The attribute can either be the schema attribute name (which is then mapped to the actual LDAP attribute) or 
just the LDAP attribute name itself.

*Default*: `name` 

--------------------
#### attributes 

These should be `key: value` pairs. Where the `key` is the name you would like the refer to the LDAP attribute by 
within the class, and the `value` is the name of the attribute in LDAP (ie. `firstName: givenName`).

--------------------
#### converters

These should defined as keys with the converters name with an array of attribute name values:

```yaml
    windows_generalized_time:
        - 'created'
        - 'modified'
```
    
The attribute names can either be the schema defined attribute name, or the actual LDAP attribute name. For a 
complete listing of possible built-in attribute converters, see this [reference doc](Attribute-Converters.md).
    
--------------------
#### converter_options

These should defined as keys with the converters name with an array of options that will be passed to the converter:

```yaml
    converter_options:
        user_account_control:
            defaultValue: '512'
            uacMap:
                disabled: '2'
                passwordNeverExpires: '65536'
                smartCardRequired: '262144'
                trustedForDelegation: '262144'
                passwordIsReversible: '128'
```
    
This results in an array with the keys `defaultValue` and `uacMap` (and their respective arrays) being passed to the
converter. These options are accessible from within the attribute converter by using `$this->options`.

--------------------
#### attributes_to_select

An array of attributes that will be selected by default on LDAP queries when using this type.

```yaml
    attributes_to_select:
        - 'firstName'
        - 'lastName'
        - 'guid'
```

--------------------
#### multivalued_attributes

An array of attributes that are expected to be multivalued. Setting this for an attribute will force it to always return
an array value regardless of the number of values it has. This allows for more predictable results.

```yaml
    multivalued_attributes:
        - 'otherHomePhone'
        - 'otherIpPhone'
```

--------------------
#### repository

The full class name (ie `\MyNamespace\MyClasses\CustomRepository`) to use as the default repository when calling
 `getRepository('object_type')` on the `LdapManager` class. The class must extend `\LdapTools\Object\LdapObjectRepository`.

--------------------
#### default_values

An array of attributes with what their default value should be set to whe creating this object using the 
`LdapObjectCreator`. These values also accept parameter values encased within `%` symbols that can resolve to other 
attribute values.

```yaml
    default_values:
        firstName: "%username%"
        displayName: "%lastName%, %firstName%"
        description: "%displayName%: Located in %city%"
        city: "Utah"
```
        
--------------------
#### required_attributes

An array of attributes that are required when creating this object type. If these are not present, an exception will be
thrown. This will only happen if they are not specified on creation and not contained within the `default_values` list.

```yaml
    required_attributes:
        - 'username'
        - 'password'
        - 'firstName'
        - 'lastName'
```

--------------------
#### default_container

This should be a string in DN format that represents the OU/container where new objects for this LDAP type should be
placed by default when created.

```yaml
    default_container: 'OU=Accounting,OU=Employees,DC=example,DC=local'
```

--------------------
#### base_dn

This should be a string in DN format that represents the OU/container where the base of a LDAP query for this LDAP type
should start. It also accepts parameter names for the defaultNamingContext and the configurationNamingContext.

```yaml
    # All LDAP objects under this path will be queried for the type.
    base_dn: 'OU=OU=Employees,DC=example,DC=local'
```

--------------------
#### controls

These are arrays of LDAP controls that should be used when performing operations/queries with this schema type. Each
control should be an array with the OID, and optionally the criticality and value:

```yaml
    # The OID must come first, followed by whether the control is critical, and then an optional value.
    # If you omit the criticality then it defaults to false.
    controls:
        - [ 1.2.840.113556.1.4.417, true ]  
```

--------------------
#### paging

This is a boolean value for whether or not paging should be used when querying for this object type. Certain controls
require that paging is not used. If this is not defined then it will default to whatever you set in your domain config or
the Query Builder instance.

```yaml
    # Disable paging for queries using this schema type.
    paging: false
```

--------------------
#### scope

This is a string value that determines the scope of the query performed when searching for this type. Possible values
are `subtree` (this is a recursive search), `onelevel` (this is a list search, only items located directly underneath 
the `base_dn` are found), and `base` (this is a one-level search, only the object represented by the `base_dn` is found).

```yaml
    # Only look for items directly underneath the base_dn.
    scope: onelevel
```

--------------------
#### extends

By using this option you can explicitly state to make a object type extend another object and inherit everything it
already has defined. This value can either be a string, meaning that the object to extend already exists within the
current schema, or it can be an array. The array must be like `[ schema, object ]`, where `schema` is the name of a 
separate schema file within the same schema folder and `object` is the name of a defined object type within that schema. 

```yaml
objects:
    user:
        # A bunch of stuff defined...
    custom_user:
        # Tells it to 'extend' the user type defined above and inherit its properties.
        extends: user
        # Make sure the define a different type!
        type: custom_user
    another_user:
        # Tells it to look in the schema file called 'custom' for the object type 'user' and extend that.
        extends: [ custom, user ]
        type: another_user
```

--------------------
#### extends_default

By using this option you can tell the schema to extend a specific object type from a default schema, either `ad` or 
`openldap`. This helps you to avoid repetition when configuring your own schema yet still customize it to your needs.
This value must be an array that contains the default schema name and object type to extend.

```yaml
objects:
    special_user:
        # Extends the default AD user type.
        extends_default: [ ad, user ]
        type: special_user
        # Put these accounts in a specific OU
        default_container: 'ou=special accounts,dc=example,dc=local'
        # Add an additional attribute to select.
        attributes_to_select:
            - 'title'
```
