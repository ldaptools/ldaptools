# Default Schema Attributes

Default schemas are provided for both Active Directory and OpenLDAP that contain attribute name mappings and converters
against commonly used attributes and object types. Below is a reference for the mappings and their converters that can be
used when generating queries and returining data from LDAP.

* Active Directory Schema
  * [Users](#ad-user-types)
  * [Groups](#ad-group-types)
  * [Computers](#ad-computer-types)
  * [Contacts](#ad-contact-types)

* OpenLDAP Schema
  * [Users](#openldap-user-types)
  * [Groups](#openldap-group-types)

### The Active Directory Schema
---

#### AD User Types

These are typical LDAP user objects (`objectClass=user` and `objectCategory=person`).

| LdapTools Name  | LDAP Attribute | Value Type |
| --------------- | -------------- | ---------- |
| badPasswordCount | badPwdCount | int |
| city | l | string |
| company | company | string |
| country | country | string |
| created | whenCreated | `\DateTime` | 
| department | department | string |
| description | description | string |
| displayName | displayName | string |
| division | division | string |
| dn | dn | string |
| emailAddress | mail | string |
| employeeId | employeeId | string |
| employeeNumber | employeeNumber | int |
| fax | facsimileTelephoneNumber | string |
| firstName | givenName | string |
| guid | objectGuid | string |
| homeDirectory | homeDirectory | string |
| homeDrive | homeDrive | string |
| homePage | wWWHomePage | string |
| homePhone | homePhone | string |
| initials | initials | string |
| ipPhone | ipPhone | string |
| lastName | sn | string |
| middleName | middleName | string |
| mobilePhone | mobile | string |
| office | physicalDeliveryOfficeName  | string |
| organization | o | string |
| password | unicodePwd | string |
| passwordLastSet | pwdLastSet | `\DateTime` |
| pager | pager | string |
| phoneNumber | telephoneNumber | string |
| poBox | postOfficeBox | string |
| profilePath | profilePath | string |
| scriptPath | scriptPath | string |
| sid | objectSid | string |
| state | st | string |
| streetAddress | streetAddress | string |
| title | title | string |
| username | sAMAccountName | string |
| upn | userPrincipalName | string |
| zipCode | postalCode | string |

#### AD Group Types

These are typical LDAP group objects (`objectClass=group`).

| LdapTools Name  | LDAP Attribute | Value Type |
| --------------- | -------------- | ---------- |
| accountName | sAMAccountName | string |
| created | whenCreated | `\DateTime` |
| description | description | string |
| displayName | displayName | string |
| dn | dn | string |
| guid | objectGuid | string |
| members | member | array|
| modified |whenModified | `\DateTime` |
| name | cn | string |
| sid | objectSid | string |

#### AD Computer Types

These are typical LDAP computer objects (`objectCategory=computer`).

| LdapTools Name  | LDAP Attribute | Value Type |
| --------------- | -------------- | ---------- |
| accountName | sAMAccountName | string |
| created | whenCreated | `\DateTime` |
| description | description | string |
| displayName | displayName | string |
| dn | dn | string |
| dnsHostName | dNSHostName | string |
| guid | objectGuid | string |
| location | location | string |
| modified |whenModified | `\DateTime` |
| name | cn | string |
| os | operatingSystem | string |
| osServicePack | operatingSystemServicePack | string |
| osVersion | operatingSystemVersion | string |
| sid | objectSid | string |

#### AD Contact Types

These are typical LDAP contact objects (`objectCategory=contact`).

| LdapTools Name  | LDAP Attribute | Value Type |
| --------------- | -------------- | ---------- |
| created | whenCreated | `\DateTime` | 
| description | description | string |
| displayName | displayName | string |
| dn | dn | string |
| emailAddress | mail | string |
| firstName | givenName | string |
| guid | objectGuid | string |
| lastName | sn | string |
| modified | whenModified | `\DateTime` |
| phoneNumber | telephoneNumber | string |
| sid | objectSid | string |

### The OpenLDAP Schema
---

#### OpenLDAP User Types

These are typical LDAP user objects (`objectClass=inetOrgPerson`).

| LdapTools Name  | LDAP Attribute | Value Type |
| --------------- | -------------- | ---------- |
| city | l | string |
| company | company | string |
| country | country | string |
| created | createTimestamp | `\DateTime` | 
| department | department | string |
| description | description | string |
| dn | dn | string |
| emailAddress | mail | string |
| employeeNumber | employeeNumber | int |
| fax | facsimileTelephoneNumber | string |
| firstName | givenName | string |
| homeDirectory | homeDirectory | string |
| homePhone | homePhone | string |
| initials | initials | string |
| lastName | sn | string |
| middleName | middleName | string |
| modified | modifyTimestamp | `\DateTime` |
| mobilePhone | mobile | string |
| organization | o | string |
| password | password | string |
| pager | pager | string |
| phoneNumber | telephoneNumber | string |
| poBox | postOfficeBox | string |
| state | st | string |
| streetAddress | streetAddress | string |
| title | title | string |
| zipCode | postalCode | string |

#### OpenLDAP Group Types

These are typical LDAP group objects (`objectClass=groupOfUniqueNames`).

| LdapTools Name  | LDAP Attribute | Value Type |
| --------------- | -------------- | ---------- |
| created | createTimestamp | `\DateTime` |
| description | description | string |
| displayName | displayName | string |
| dn | dn | string |
| members | uniqueMembers | array|
| modified | modifyTimestamp | `\DateTime` |
| name | cn | string |
