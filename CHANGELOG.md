CHANGELOG
=========

0.24.0 (2017-04-09)
-------------------
  * You can now create Microsoft Exchange Mailboxes (2007+) using the ExchangeMailboxUser schema type.
  * You can now rename an LDAP object by setting the 'name' attribute and persisting it.
  * Add an in() (IN clause equivalent)filter shortcut method for the query builder
  * Add a match() and matchDn() filter shortcut method for the query builder.
  * A getParentDn() utility method is now availabe in the LdapUtilities class @markusu49
  * A findAll() convenience method is now available in the LdapObjectRepository @markusu49
  * Improve support for all matching rule operations in the MatchingRule query operator.
  * The schema type is now available in the LDAP object creation event.
  * Multi-valued RDNs are now supported on creation from a schema object.
  * The RDN is now a configurable attribute in the schema (defaults to the 'name' attribute mapping)
  * The User Account Control converter is now a generic flag value converter.
  * Allow determining the wildcard type from the query operator class.
  * Add several attribute converters for working with Microsoft Exchange.
  * Several performance related improvements related to case-insensitive look-ups. 
  * An ACE type can now be set via a string or object.
  * Allow LDAP query operation results to be cached.
  * Allow setting the cache class directly on the Configuration class. 
  * Redesigned much of the Cache system. 
  * A query operation with an empty filter will now throw a Query Exception, as it is not valid in LDAP.
  * Corrected the deleteObject() and readSecurity() AceRights flags to reflect their intended flags.

0.23.0 (2016-11-27)
-------------------
  * Add a Windows Security Descriptor/SDDL parser and encoder. Provides easy read/writing of AD ACLs/ACEs.
  * Add a string representation of the DN to the LDAP objects.
  * The reset method for an LdapObject is now variadic, so multiple attributes can be reset with one command.
  * When setting an attribute value to an empty string or array it will now perform a reset operation instead.
  * Operation error messages now contain the full diagnostic message for easier troubleshooting.
  * The LDAP object type can now be passed directly to the `createLdapObject` method of the LdapManager.
  * Provide a better error message when trying to convert a value (SID, GUID, etc) to a DN and it fails.
  * Provide a better error message when attempting to switch domains on the LdapManager and it fails.
  * LDAP query results are now freed at the end of a query operation.
  * Binary data will now be ignored for the encoding process when going to LDAP.
  * Added Windows Server 2016 to the recognized domain/forest functional levels in the RootDSE.
  * Fix parameter resolution for new objects that contain values that are objects (such as another LdapObject).
  * Fix SID encoding/decoding under certain circumstances.
  * Fix setting the use_paging option when resolving configuration settings. 

0.22.0 (2016-08-22)
-------------------
  * Add a connect_timeout option for more control over the initial connection test timeout value.
  * Sorting is now done case-insensitive by default. It can be toggled on the LdapQuery class.
  * Redesign the GPO Link converter to respect link order and enable/enforce options on specific links.
  * Fix the attribute validation for the query builder so single character attributes are allowed.
  * Empty arrays and null values are now ignored on LDAP object creation.
  * The schema 'extend' directive now works as expected.
  * UTF-8 characters will now sort properly when using orderBy in the query builder.
  * Consistent UTF-8 support across the library.
  * Fix non-standard port usage when connecting to LDAP.

0.21.0 (2016-06-27)
-------------------
  * Add a utility class for encoding/decoding userParameters data in AD.
  * Sanitize non-printable characters in operation data for the logging array.
  * A lazy-bind/closed connection will now connect when operations are executed.
  * Add an idle timeout reconnect option to reconnect to LDAP after a specified time.
  * Fix the Value-to-DN converter for non-AD schemas.
  * Rename the Exchange Retention Policy to Recipient policy (This is what it was always targeting).
  * More areas throw AttributeConverterException where it makes sense.
  * Parameters in the base DN are now only attempted to be resolved if any exist.
  * Optimize the LDAP queries in some attribute converters.
  * A few other bug fixes.

0.20.0 (2016-05-10)
-------------------
  * Add a schema type for searching for deleted LDAP objects (AD Recycle Bin support).
  * Add a method to restore LDAP objects from the AD Recycle Bin.
  * Allow for querying multiple schema types at once.
  * Allow using aliases when querying multiple schema types to form more complex LDAP filters.
  * Add the ability to set size limits on queries.
  * Querying with userAccountControl properties via bool values now produces the expected result.
  * Querying with group type/scope attributes via bool values now produces the expected result.
  * Querying for accounts with expiration dates can now be done via a bool on the schema attribute.
  * Add the ability to disable/enable, and set auth delegation, on AD computer objects via attributes.
  * Add an 'enabled' attribute for the user schema as a convenience.
  * Remove the class and category methods on the LdapObject instances. Simplify the constructor.
  * Change 'getLdapFilter()' to 'toLdapFilter()' in all places for consistency. Deprecate 'getLdapFilter()'.
  * A few bug fixes.

0.19.0 (2016-04-01)
-------------------
  * Add two new schema file directives: include, include_default. Allows for more logical separation of schema files.
  * Add a schema object directive: base_dn. This allows setting the default query BaseDN on the object type.
  * Add a schema object directive: filter. Allows for greater flexibility instead of class and/or category only.
  * Add a new converter/attribute for AD OUs to determine if GPO inheritance is being blocked.
  * Add a new converter for group membership. The 'groups' attribute can now be directly modified like you'd expect.
  * Add a new converter for the Exchange Server version and roles.
  * Add an Exchange Schema that includes: Exchange Servers, Databases, RBAC/ActiveSync/Retention Policies, OWA, DAG 
  * Several LDIF improvements and fixes (line folding, line endings, multi-line base64 encoding, comment parsing, etc) 
  * Refactor some of the LdapQueryBuilder.

0.18.0 (2016-02-13)
-------------------
  * Allow LDAP controls to be set per operation.
  * Add an option to recursively delete a LDAP object.
  * Add a LDIF parser/creator. Supports URL loading, operation controls, and is schema aware.
  * Add an event to trigger before/after any LDAP operation.
  * Fix SID string to hex conversion with sub authority counts.
  * Fix parameter resolving for multi-valued attributes on LDAP object creation.
  * Throw LdapTools specific Invalid Argument Exceptions.
  * Make the Stash Cache type compatible with recent changes.

0.17.0 (2015-12-25)
------------------
  * Added a method to retrieve the event dispatcher from the LdapManager.
  * Mask sensitive data in the operation's log array by default.
  * When loading a configuration from an array, the array no longer has to have a domain defined.
  * Added an AttributeConverterException for issues encountered during attribute value conversion.

0.16.0 (2015-12-02)
------------------
  * Implemented a logging mechanism for LDAP operations.
  * Large re-write for how LDAP operations (add, modify, delete, etc) are handled by the connection.
  * Paging can now be enabled/disabled in the domain configuration (enabled by default).
  * Paging can now be enabled/disabled on a per-query basis (enabled by default).
  * The LDAP server can now be set on per-query/operation basis (defaults to your config server options)
  * Added a helpful exception message for a common misconfiguration when modifying/sending a password.
  * DNs sent to LDAP on creation/modification are now escaped in a more RFC compliant manner.
  * Queries generated by LdapQueryBuilder are filtered with LDAP_ESCAPE_FILTER. This makes them much easier to read.
    Previously the query filter values were completely hex escaped.
  * Removed many redundant methods from the connection class/interface. Added a getter for the configuration.
  * Implemented an authentication operation and a method to "switch credentials" after an auth attempt.
  * Added an authentication event that can be triggered before or after an authentication operation.
  * A few bug fixes.
  
0.15.0 (2015-10-30)
------------------
  * Added an event system. Event listeners are available for LDAP object creation, deletion, and modification.
  * Multiple class values can now be defined for a schema object type.
  * Multiple values can now be passed to a LDAP objects 'add*()' and 'remove*()' methods.
  * Added a 'less-than' and 'greater-than' LDAP filter shortcut.
  * Fixes and optimizations for the LDAP object manager.
  * Be strict about validating operator symbols for query syntax.
  * Be strict about validating values used for attribute names/OIDs.
  
0.14.0 (2015-06-16)
------------------
  * Add the ability to use doctrine cache for the caching mechanism. This allows for easier Symfony integration.
  * Update the cache interface for the delete and deleteAll methods.
  * Allow retrieving the Cache, Parser, and LdapObjectSchemaFactory from the LdapManager.
  * Respect LDAP SRV weight and priority when sorting for the server pool.
  * Better base_dn guessing when using OpenLDAP and the base_dn is not defined.
  * A few bug fixes.

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