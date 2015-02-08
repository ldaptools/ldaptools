# Schema Configuration
----------------------

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

### Schema Object Configuration Options
---------------------------------------

#### type ***(Required)***

The name for the type is how you will refer to this LDAP schema object within the class. This is a required field. 
Default LDAP object types that the class has defined are: `user`, `group`, `computer`, `contact`.

--------------------
#### class
 
This is the `objectClass` value for the LDAP object you're defining. It can be any valid LDAP objectClass value (`user`,
`inetOrgPerson`, `group`, etc) and will be used in the creation of LDAP query filters when using this type.

--------------------
#### category

This is the `objectCategory` value for the LDAP object you're defining. It can be any valid LDAP
objectCategory value (`person`, `computer`, `contact`, etc) and will be used in the creation of LDAP query filters 
(along with the `class` definition above) when using this type.

--------------------
#### attributes 

These should be `key: value` pairs. Where the `key` is the name you would like the refer to the LDAP attribute by 
within the class, and the `value` is the name of the attribute in LDAP (ie. `firstName: givenName`).

--------------------
#### converters

These should defined as keys with the converters name with an array of attribute name values:

```yaml
    convert_windows_generalized_time:
        - 'created'
        - 'modified'
```
    
The attribute names can either be the schema defined attribute name, or the actual LDAP attribute name. For a 
complete listing of possible built-in attribute converters, see this [reference doc](attribute-converters.md).
    
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
#### repository

The full name class name (ie `\MyNamespace\MyClasses\CustomRepository`) to use as the default repository when calling
 `getRepository('object_type')` on the `LdapManager` class. The class must extend `\LdapTools\LdapObjectRepository`.

--------------------
#### default_values

An array of attributes with what their default value should be set to whe creating this object using the 
`LdapObjectCreator`. These values also accept parameter values encased within % symbols that can resolve to other 
attribute values.

```yaml
    attributes_to_select:
        firstName: "%username%"
        displayName: "%lastName%, %firstName%"
        description: "%displayName%: Located in %city%"
        city: "Utah"
```
        
--------------------
#### attributes_required

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
