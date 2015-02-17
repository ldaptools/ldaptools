CHANGELOG
=========

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