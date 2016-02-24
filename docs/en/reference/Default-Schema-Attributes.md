# Default Schema Attributes

Default schemas are provided for both Active Directory and OpenLDAP that contain attribute name mappings and converters
against commonly used attributes and object types. Below is a reference for the mappings and their converters that can be
used when generating queries and returning data from LDAP.

#### Active Directory Schema
  * [Users](#ad-user-types)
  * [Groups](#ad-group-types)
  * [Computers](#ad-computer-types)
  * [Contacts](#ad-contact-types)
  * [OUs](#ad-ou-types)
  * [Password Settings Objects](#ad-password-settings-objects-types)

#### OpenLDAP Schema
  * [Users](#openldap-user-types)
  * [Groups](#openldap-group-types)
  * [OUs](#openldap-ou-types)

### The Active Directory Schema
---

#### AD User Types

These are typical LDAP user objects (`objectClass=user` and `objectCategory=person`).

| LdapTools Name  | LDAP Attribute | Value Type |
| --------------- | -------------- | ---------- |
| accountExpirationDate | accountExpires | `\DateTime|false` |
| badPasswordCount | badPwdCount | int |
| city | l | string |
| company | company | string |
| country | c | string |
| created | whenCreated | `\DateTime` | 
| department | department | string |
| description | description | string |
| disabled | userAccountControl | bool |
| displayName | displayName | string |
| division | division | string |
| dn | dn | string |
| emailAddress | mail | string |
| employeeId | employeeId | string |
| employeeNumber | employeeNumber | int |
| exchangeAlias | mailNickname | string |
| exchangeHideFromGAL | msExchHideFromAddressLists | bool |
| exchangeMailboxDatabase | homeMDB | string |
| exchangeMailboxGuid | msExchMailboxGUID | string |
| exchangePolicyRBAC | msExchRBACPolicyLink | string |
| exchangePolicyActiveSync | msExchMobileMailboxPolicyLink | string |
| exchangePolicyRetention | msExchMailboxTemplateLink | string |
| exchangeSendOnBehalfOf | publicDelegates | array |
| exchangeSmtpAddresses | proxyAddresses | array |
| exchangeDefaultSmtpAddress | proxyAddresses | string |
| fax | facsimileTelephoneNumber | string |
| firstName | givenName | string |
| groups | memberOf | array |
| guid | objectGuid | string |
| homeDirectory | homeDirectory | string |
| homeDrive | homeDrive | string |
| homePage | wWWHomePage | string |
| homePhone | homePhone | string |
| initials | initials | string |
| ipPhone | ipPhone | string |
| lastName | sn | string |
| logonWorkstations | userWorkstations | array |
| manager | manager | string |
| middleName | middleName | string |
| mobilePhone | mobile | string |
| office | physicalDeliveryOfficeName  | string |
| organization | o | string |
| otherFaxes | otherFacsimileTelephoneNumber | array |
| otherHomePhones | otherHomePhone | array |
| otherIpPhones | otherIpPhone | array |
| otherPagers | otherPager | array |
| otherPhoneNumbers | otherTelephoneNumber | array |
| password | unicodePwd | string |
| passwordIsReversible | userAccountControl | bool |
| passwordLastSet | pwdLastSet | `\DateTime` |
| passwordMustChange | pwdLastSet | bool |
| passwordNeverExpires | userAccountControl | bool |
| pager | pager | string |
| phoneNumber | telephoneNumber | string |
| poBox | postOfficeBox | string |
| primaryGroup | primaryGroupID | string |
| profilePath | profilePath | string |
| scriptPath | scriptPath | string |
| servicePrincipalNames | servicePrincipalName | array |
| sid | objectSid | string |
| smartCardRequired | userAccountControl | bool |
| state | st | string |
| streetAddress | streetAddress | string |
| title | title | string |
| trustedForAllDelegation | userAccountControl | bool |
| trustedForAnyAuthDelegation | userAccountControl | bool |
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
| emailAddress | mail | string |
| exchangeAddressBookFlags | msExchAddressBookFlags | int |
| exchangeAlias | mailNickname | string |
| exchangeHideFromGAL | msExchHideFromAddressLists | bool |
| exchangeInternalOnly | msExchRequireAuthToSendTo | bool |
| exchangeSmtpAddresses | proxyAddresses | array |
| exchangeDefaultSmtpAddress | proxyAddresses | string |
| groups | memberOf | array |
| guid | objectGuid | string |
| managedBy | managedBy | string |
| members | member | array|
| modified |whenModified | `\DateTime` |
| name | cn | string |
| scopeDomainLocal | groupType | bool |
| scopeGlobal | groupType | bool |
| scopeUniversal | groupType | bool |
| sid | objectSid | string |
| typeSecurity | groupType | bool |
| typeDistribution | groupType | bool |

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
| groups | memberOf | array |
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
| exchangeAlias | mailNickname | string |
| exchangeHideFromGAL | msExchHideFromAddressLists | bool |
| exchangeSmtpAddresses | proxyAddresses | array |
| exchangeDefaultSmtpAddress | proxyAddresses | string |
| firstName | givenName | string |
| groups | memberOf | array |
| guid | objectGuid | string |
| lastName | sn | string |
| manager | manager | string |
| modified | whenModified | `\DateTime` |
| phoneNumber | telephoneNumber | string |
| sid | objectSid | string |

#### AD OU Types

These are typical LDAP OU objects (`objectCategory=organizationalUnit`).

| LdapTools Name  | LDAP Attribute | Value Type |
| --------------- | -------------- | ---------- |
| city | l | string |
| country | c | string |
| created | whenCreated | `\DateTime` | 
| description | description | string |
| dn | dn | string |
| gpoLinks | gPLink | array |
| guid | objectGuid | string |
| inheritanceBlocked | gpOptions | bool |
| modified | whenModified | `\DateTime` |
| name | ou | string |
| sid | objectSid | string |
| state | st | string |
| streetAddress | streetAddress | string |
| zipCode | postalCode | string |

#### AD Password Settings Objects Types

These are Password Settings Objects, also known as PSOs, (`objectClass=msDS-PasswordSettings`). They can be used with 
the type name `PSO`.

| LdapTools Name  | LDAP Attribute | Value Type |
| --------------- | -------------- | ---------- |
| appliesTo | msDS-PSOAppliesTo | array |
| created | whenCreated | `\DateTime` |
| description | description | string |
| dn | distinguishedName | string |
| guid | objectGuid | string |
| lockoutDuration | msDS-LockoutDuration | `\LdapTools\Utilities\ADTimeSpan` |
| lockoutObservationWindow | msDS-LockoutObservationWindow | `\LdapTools\Utilities\ADTimeSpan` |
| lockoutThreshold | msDS-LockoutThreshold | int |
| maximumPasswordAge | msDS-MaximumPasswordAge | `\LdapTools\Utilities\ADTimeSpan` |
| minimumPasswordAge | msDS-MinimumPasswordAge | `\LdapTools\Utilities\ADTimeSpan` |
| minimumPasswordLength | msDS-MinimumPasswordLength | int |
| modified | whenChanged | `\DateTime` |
| name | cn | string |
| passwordComplexity | msDS-PasswordComplexityEnabled | bool |
| passwordHistoryLength | msDS-PasswordHistoryLength | int |
| passwordReversibleEncryption | msDS-PasswordReversibleEncryptionEnabled | bool |
| precedence | msDS-PasswordSettingsPrecedence | int |
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

#### OpenLDAP OU Types

These are typical LDAP OU objects (`objectClass=organizationalUnit`).

| LdapTools Name  | LDAP Attribute | Value Type |
| --------------- | -------------- | ---------- |
| created | createTimestamp | `\DateTime` |
| dn | dn | string |
| modified | modifyTimestamp | `\DateTime` |
| name | ou | string |
