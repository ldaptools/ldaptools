CHANGELOG
=========

0.13.0 (2015-04-19)
------------------

  * A `LdapObject` returned from queries can be passed as a value to many attributes: groups, members, manager, 
    exchangeSendOnBehalfOf, managedBy, etc. This allows for more fluent code.
  * Added Password Settings Objects (PSO) to the AD schema definition. 
  * The `servers` domain config option is no longer mandatory. It will be queried from DNS if not provided.
  * The `base_dn` domain config option is no longer mandatory. It will be queried from the RootDSE if not provided.
  * All attributes can now be selected when running a query. `*` selects all schema attributes. `**` Selects all
    attributes (both schema and LDAP).
  * Better RootDSE support. It is now returned as a normal LdapObject and is based off a schema.
  * The `hasMemberRecursively` filter now accepts a username, GUID, SID, LdapObject or DN.
  * Parameters for the container/OU path are now resolved on LDAP object creation.
  * Added all of the `other*` AD telephone attributes to the schema.
  * Defined the objectClass as a multivalued attribute for all schema objects.
  * The value-to-dn converter will no longer query LDAP when the passed value is a DN.
  * A few bug fixes.
  
0.12.0 (2015-04-08)
------------------

  * Added several new query search result methods: getSingleResult, getOneOrNullResult, getSingleScalarResult, etc.
  * Added a converter for the AD primary group of a user. It can now be searched/modified/displayed by the group name.
  * Added the groups attribute to the schema for AD users, groups, contacts, and computers. Returns groups by name.
  * Implemented an updated 'value_to_dn' converter to easily search/update values by name, GUID, SID, or full DN.
  * The groups attribute can be used to query a user for membership by a group name, GUID, SID, or full DN.
  * Add the ability to format the bind username. You can now bind/auth to AD with a GUID, SID, UPN, DN, or username.
  * More code cleanup and bug fixes.

0.11.0 (2015-03-23)
------------------

  * Implement the ability to order search results by any specified attributes.
  * Add the OU object type to both the default schemas for searching, creation, and modification.
  * Implement a method for getting extended error messages from AD.
  * Better LDAP authentication handling with optional detailed error messages and codes.
  * Implement a gPLink converter to list/modify GPOs associated with an OU by their name.
  * Additional array functions are available for a LdapObjectCollection: first, last, next, previous, current, key
  * More code cleanup and a few bug fixes.

0.10.0 (2015-03-08)
------------------

  * Make the string encoder to LDAP a domain level configuration that no longer requires an attribute converter.
  * Implement a proxyAddresses converter for adding/remove/setting Exchange SMTP addresses.
  * Implement a converter for the AD "log on to..." computer list.
  * Implement a converter to easily set the AD account expiration attribute.
  * Implement a groupType converter to easily switch a group between Global, Universal, Security Enabled, etc.
  * Add more Exchange attributes to the schema.
  * More LDAP query filter builder helpers: Group Types, Account Expiration
  * Lots of code clean-up and reorganization.
  
0.9.0 (2015-02-26)
------------------

  * Implement the ability to easily extend the schema and the defined schema objects.
  * Implement the ability to register custom converter classes in the config.
  * add a 'has' magic method to LdapObjects. Can now do things like: if ($user->hasFirstName('Foo'))
  * Many AD account properties can now be changed by a simple bool (disabled, delegation, passwordNeverExpires, etc).
  * Add a name to distinguished name converter. Easily translates full DN attributes to their base name and back again.
  * Many attribute converter changes to allow for increased flexibility and options.
  * Started to add some Exchange support via attributes (exchangeMailboxDatabase, exchangePolicyActiveSync, etc).
  
0.8.0 (2015-02-16)
------------------

  * Implement an object hydrator and make it the default.
  * Implement automatic setters/getters/property access on searched objects.
  * Implement modifying LDAP objects by persisting a hydrated LDAP object using the LdapManager.
  * Implement a method to easily delete LDAP objects using the LdapManager.
  * Better error detection in the LdapConnection.
  * Improved YAML schema parsing performance.
  
0.7.0 (2015-02-09)
------------------

  * Add the ability to easily create common LDAP objects (User, Group, Contact, Computer).
  * Implement a parameter resolver for LDAP object creation.
  * Add several new schema directives: default_values, attributes_required, default_container
  * Throw exceptions on LDAP add/delete operations for better error handling in try/catch blocks.
  * Fixed the DN mapping for the AD schema.
  * Fix AD password encoding.
  
0.6.0 (2015-02-01)
------------------

  * Various code clean-up since initial commit.
  * Implement a server pool for redundancy when more than one LDAP server is in the config.
  * Improved error handling in LdapConnection.
  * Fixed the incorrect scope map in the LdapConnection.
  * Setup the repository with travis ci and scrutinizer.
  
0.5.0 (2015-01-30)
------------------

  * Initial release.