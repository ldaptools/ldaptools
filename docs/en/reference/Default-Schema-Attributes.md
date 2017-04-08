# Default Schema Attributes

Default schemas are provided for both Active Directory and OpenLDAP that contain attribute name mappings and converters
against commonly used attributes and object types. Below is a reference for the mappings and their converters that can be
used when generating queries and returning data from LDAP.

#### Active Directory Schema
  * [Users](#ad-user-types)
  * [Groups](#ad-group-types)
  * [Computers](#ad-computer-types)
  * [Contacts](#ad-contact-types)
  * [Containers](#ad-container-types)
  * [OUs](#ad-ou-types)
  * [Password Settings Objects](#ad-password-settings-objects-types)
  * [Deleted Objects](#ad-deleted-objects-types)

#### Exchange Schema
  * [Servers](#exchange-server-types)
  * [Databases](#exchange-database-types)
  * [Mailbox User](#exchange-mailbox-user-types)
  * [Recipient Policies](#exchange-recipient-policy-types)
  * [ActiveSync Policies](#exchange-activesync-policy-types)
  * [RBAC Policies](#exchange-rbac-policy-types)
  * [Transport Rules](#exchange-transport-rule-types)
  * [DAG](#exchange-dag-types)
  * [OWA](#exchange-owa-types)
  
#### OpenLDAP Schema
  * [Users](#openldap-user-types)
  * [Groups](#openldap-group-types)
  * [OUs](#openldap-ou-types)

### The Active Directory Schema
---

#### AD User Types

These are typical LDAP user objects.

* **Type**: `LdapObjectType::USER`
* **Filter**: `(&(objectClass=user)(objectCategory=person))`

| LdapTools Name  | LDAP Attribute | Value Type | Description |
| --------------- | -------------- | ---------- | ----------- |
| accountExpirationDate | accountExpires | `\DateTime|false` | The date the account expires |
| badPasswordCount | badPwdCount | int | The number of bad password attempts |
| city | l | string | The city for the user account |
| company | company | string | The company for the user account |
| country | c | string | The country for the user account |
| created | whenCreated | `\DateTime` | The date the account was created |
| department | department | string | The department for the user account |
| description | description | string | The description for the user account |
| disabled | userAccountControl | bool | Whether or not the account is disabled |
| displayName | displayName | string | The display name for the account |
| division | division | string | The user's division |
| dn | dn | string | The full distinguished name of the user |
| emailAddress | mail | string | The user's email address |
| employeeId | employeeId | string | The user's employee ID |
| employeeNumber | employeeNumber | int | The user's employee number |
| enabled | userAccountControl | bool | Whether or not the account is enabled |
| exchangeAlias | mailNickname | string | The user's exchange alias |
| exchangeHideFromGAL | msExchHideFromAddressLists | bool | Whether the user should be hidden from the address list |
| exchangeMailboxDatabase | homeMDB | string | The database name where the user's mailbox is located |
| exchangeMailboxGuid | msExchMailboxGUID | string | The user's mailbox GUID |
| exchangePolicyRBAC | msExchRBACPolicyLink | string | The name of the assigned RBAC policy |
| exchangePolicyActiveSync | msExchMobileMailboxPolicyLink | string | The name of the assigned ActiveSync policy | 
| exchangePolicyRetention | msExchMailboxTemplateLink | string | The name of the assigned retention policy |
| exchangeSendOnBehalfOf | publicDelegates | array | All of the users that can send-on-behalf of this user |
| exchangeSmtpAddresses | proxyAddresses | array | All of the user's assigned SMTP addresses |
| exchangeDefaultSmtpAddress | proxyAddresses | string | The user's default SMTP address |
| fax | facsimileTelephoneNumber | string | The user's fax phone number |
| firstName | givenName | string | The user's first name |
| groups | memberOf | array | The group names the user belongs to (not recursive) |
| guid | objectGuid | string | The user's GUID |
| homeDirectory | homeDirectory | string | The user's home directory path (ie. the UNC path) |
| homeDrive | homeDrive | string | The user's home drive letter (ie. "H:") |
| homePage | wWWHomePage | string | The user's home page as a URL |
| homePhone | homePhone | string | The user's home telephone number |
| initials | initials | string | The initials that represent part of the user's name (ie. middle initial) |
| ipPhone | ipPhone | string |  The user's IP telephone number |
| lastName | sn | string | The user's last name |
| locked | lockoutTime | bool | Whether or not the user's account is locked out |
| lockedDate | lockoutTime | `\DateTime|false` | The date the user's account was locked (or false if not) |
| logonWorkstations | userWorkstations | array | The system names the user is allowed to login to |
| manager | manager | string | The common name of the user's manager |
| middleName | middleName | string | A name in addition to the user's first/last name (ie. middle name) |
| mobilePhone | mobile | string | The user's mobile phone number |
| modified | whenChanged | `\DateTime` | The date when the account was last modified |
| notes | info | string | Any additional information/notes for the user |
| office | physicalDeliveryOfficeName  | string | The user's office name |
| organization | o | string | The user's organization name |
| otherFaxes | otherFacsimileTelephoneNumber | array | Additional fax telephone numbers for the user |
| otherHomePhones | otherHomePhone | array | Additional home telephone numbers for the user |
| otherIpPhones | otherIpPhone | array | Additional IP telephone numbers for the user |
| otherPagers | otherPager | array | Additional pager numbers for the user |
| otherPhoneNumbers | otherTelephoneNumber | array |  Additional telephone numbers for the user |
| password | unicodePwd | string | The user's password (can only be created or modified) |
| passwordIsReversible | userAccountControl | bool | Whether the password is reversible |
| passwordLastSet | pwdLastSet | `\DateTime` | The date the password was last set |
| passwordMustChange | pwdLastSet | bool | Whether the password must change on next login |
| passwordNeverExpires | userAccountControl | bool | Whether the password is set to never expire |
| pager | pager | string | The user's pager number |
| phoneNumber | telephoneNumber | string | The user's primary telephone number |
| poBox | postOfficeBox | string | The user's PO box number |
| primaryGroup | primaryGroupID | string | The user's primary group (typically Domain Users) |
| profilePath | profilePath | string | The user's profile path (ie. \\some\path) |
| scriptPath | scriptPath | string | The user's login script (ie. \\server\scripts\login.bat) |
| servicePrincipalNames | servicePrincipalName | array | All of the user's SPNs (ie `['SQLservice\foo.bar.com:1456']` |
| sid | objectSid | string | The user's SID (security identifier) |
| smartCardRequired | userAccountControl | bool | Whether or not the user must use a smart card |
| state | st | string | The user's state (ie. WI) |
| streetAddress | streetAddress | string | The user's street address |
| title | title | string | The user's title (ie. Systems Administrator) |
| trustedForAllDelegation | userAccountControl | bool | Whether the account is trusted for delegation |
| trustedForAnyAuthDelegation | userAccountControl | bool | Whether the account is trusted for any auth delegation |
| username | sAMAccountName | string | The user's username |
| upn | userPrincipalName | string | The user's user principal name (ie. foo@bar.local)
| zipCode | postalCode | string | The user's zip code |

#### AD Group Types

These are typical LDAP group objects.

* **Type**: `LdapObjectType::GROUP`
* **Filter**: `(objectClass=group)`

| LdapTools Name  | LDAP Attribute | Value Type | Description |
| --------------- | -------------- | ---------- | ----------- |
| accountName | sAMAccountName | string | The account name of the group |
| created | whenCreated | `\DateTime` | The date the group was created |
| description | description | string | The description of the group |
| displayName | displayName | string | The display name for the group |
| dn | dn | string | The full distinguished name of the group |
| emailAddress | mail | string | The group's email address |
| exchangeAddressBookFlags | msExchAddressBookFlags | int | 
| exchangeAlias | mailNickname | string | The group's exchange alias |
| exchangeHideFromGAL | msExchHideFromAddressLists | bool | Whether the group should be hidden from the address list |
| exchangeInternalOnly | msExchRequireAuthToSendTo | bool | Whether the group requires authentication to send to it |
| exchangeSmtpAddresses | proxyAddresses | array | All of the group's assigned SMTP addresses |
| exchangeDefaultSmtpAddress | proxyAddresses | string | The group's default SMTP address |
| groups | memberOf | array | The group names the group belongs to (not recursive) |
| guid | objectGuid | string | The group's GUID |
| managedBy | managedBy | string | The common name for who the group is managed by |
| members | member | array| All of the members of the group (not recursive) |
| modified | whenChanged | `\DateTime` | The date when the group was last modified |
| name | cn | string | The common name (RDN) of the group |
| notes | info | string | Any additional information/notes for the group |
| scopeDomainLocal | groupType | bool | Whether or not the group's scope is domain local |
| scopeGlobal | groupType | bool | Whether or not the group's scope is global |
| scopeUniversal | groupType | bool | Whether or not the group's scope is universal |
| sid | objectSid | string | The group's SID (security identifier)
| typeBuiltin | groupType | bool | Whether or not this is builtin group |
| typeDistribution | groupType | bool | Whether or not this is a distribution group |
| typeSecurity | groupType | bool | Whether or not this is a security group |

#### AD Computer Types

These are typical LDAP computer objects.

* **Type**: `LdapObjectType::COMPUTER`
* **Filter**: `(objectCategory=computer)`

| LdapTools Name  | LDAP Attribute | Value Type | Description |
| --------------- | -------------- | ---------- | ----------- |
| accountName | sAMAccountName | string | The account name of the computer |
| created | whenCreated | `\DateTime` | The date the computer object was created |
| description | description | string | The description of the computer |
| disabled | userAccountControl | bool | Whether or not the computer object is disabled |
| displayName | displayName | string | The display name for the computer |
| dn | dn | string | The full distinguished name of the computer |
| dnsHostName | dNSHostName | string | The fully qualified domain name (FQDN) of the computer |
| enabled | userAccountControl | bool | Whether or not the computer object is enabled |
| groups | memberOf | array | The group names the computer belongs to (not recursive) |
| guid | objectGuid | string | The computer's GUID |
| location | location | string | The location of the computer (such as office name) |
| modified |whenModified | `\DateTime` | The date the computer was last modified |
| name | cn | string | The common name (RDN) of the computer |
| notes | info | string | Any additional information/notes for the computer |
| os | operatingSystem | string | The operating system name for the computer object |
| osServicePack | operatingSystemServicePack | string | The name of the operating system service pack |
| osVersion | operatingSystemVersion | string | The operating system version number (ie. 6.0) |
| sid | objectSid | string | The computer's SID (Security Identifier)
| trustedForAllDelegation | userAccountControl | bool | Whether the computer is trusted for delegation |
| trustedForAnyAuthDelegation | userAccountControl | bool | Whether the computer is trusted for any auth delegation |

#### AD Contact Types

These are typical LDAP contact objects.

* **Type**: `LdapObjectType::CONTACT`
* **Filter**: `(objectCategory=contact)`

| LdapTools Name  | LDAP Attribute | Value Type | Description |
| --------------- | -------------- | ---------- | ----------- |
| created | whenCreated | `\DateTime` | The date the contact was created |
| description | description | string | The description of the contact  |
| displayName | displayName | string | The display name for the contact |
| dn | dn | string | The full distinguished name of the contact |
| emailAddress | mail | string | The contact's email address |
| exchangeAlias | mailNickname | string | The contact's exchange alias |
| exchangeHideFromGAL | msExchHideFromAddressLists | bool | Whether the contact should be hidden from the address list |
| exchangeSmtpAddresses | proxyAddresses | array | All of the contact's assigned SMTP addresses |
| exchangeDefaultSmtpAddress | proxyAddresses | string | The contact's default SMTP address |
| firstName | givenName | string | The contact's first name |
| groups | memberOf | array | The group names the contact belongs to (not recursive) |
| guid | objectGuid | string | The contact's GUID |
| lastName | sn | string | The contact's last name |
| manager | manager | string | The common name of the contact's manager |
| modified | whenModified | `\DateTime` | The date the contact was last modified |
| notes | info | string | Any additional information/notes for the contact |
| phoneNumber | telephoneNumber | string | The contact's telephone number |
| sid | objectSid | string | The contact's SID (Security Identifier) |

#### AD OU Types

These are typical LDAP OU objects.

* **Type**: `LdapObjectType::OU`
* **Filter**: `(objectCategory=organizationalUnit)`

| LdapTools Name  | LDAP Attribute | Value Type | Description |
| --------------- | -------------- | ---------- | ----------- |
| city | l | string | The OU's city name |
| country | c | string | The OU's country name |
| created | whenCreated | `\DateTime` | When date the OU was created |
| description | description | string | The description of the OU |
| dn | dn | string | The full distinguished name of the OU |
| gpoLinks | gPLink | GPOLink[] | All of the GPOs linked to this OU |
| gpoInheritanceBlocked | gpOptions | bool | Whether or not GPO inheritance is blocked for this OU |
| guid | objectGuid | string | The OU's GUID |
| modified | whenModified | `\DateTime` | The date the OU was last modified |
| name | ou | string | The common name (RDN) for the OU |
| sid | objectSid | string | The OU's SID (Security Identifier) |
| state | st | string | The OU's state name |
| streetAddress | streetAddress | string | The OU's street address |
| zipCode | postalCode | string | The OU's zip code |

#### AD Container Types

These are typical LDAP Container objects.

* **Type**: `LdapObjectType::CONTAINER`
* **Filter**: `(objectCategory=container)`

| LdapTools Name  | LDAP Attribute | Value Type | Description |
| --------------- | -------------- | ---------- | ----------- |
| created | whenCreated | `\DateTime` |  The date the container was created |
| description | description | string | The description of the container |
| dn | dn | string | The full distinguished name of the container |
| guid | objectGuid | string | The container's GUID |
| modified | whenModified | `\DateTime` | The date the container was last modified |
| name | cn | string | The common name (RDN) for the container |

#### AD Password Settings Objects Types

These are Password Settings Objects, also known as PSOs. They can be used with 
the type name `PSO`.

* **Filter**: `(objectClass=msDS-PasswordSettings)`

| LdapTools Name  | LDAP Attribute | Value Type | Description |
| --------------- | -------------- | ---------- | ----------- |
| appliesTo | msDS-PSOAppliesTo | array | All of the group/user names this PSO is applied to |
| created | whenCreated | `\DateTime` | The date the PSO was created |
| description | description | string | The description of the PSO |
| dn | distinguishedName | string | The full distinguished name of the PSO |
| guid | objectGuid | string | The PSO's GUID |
| lockoutDuration | msDS-LockoutDuration | `\LdapTools\Utilities\ADTimeSpan` | Lockout duration time span |
| lockoutObservationWindow | msDS-LockoutObservationWindow | `\LdapTools\Utilities\ADTimeSpan` | Lockout observation window time span |
| lockoutThreshold | msDS-LockoutThreshold | int | Number of attempts before the account is locked |
| maximumPasswordAge | msDS-MaximumPasswordAge | `\LdapTools\Utilities\ADTimeSpan` | Max password age time span |
| minimumPasswordAge | msDS-MinimumPasswordAge | `\LdapTools\Utilities\ADTimeSpan` | Min password age time span |
| minimumPasswordLength | msDS-MinimumPasswordLength | int | Min password length |
| modified | whenChanged | `\DateTime` | The date the PSO was last modified |
| name | cn | string | The common name (RDN) for the PSO |
| passwordComplexity | msDS-PasswordComplexityEnabled | bool | Whether or not password complexity is enabled |
| passwordHistoryLength | msDS-PasswordHistoryLength | int | The number of previous passwords that cannot be used |
| passwordReversibleEncryption | msDS-PasswordReversibleEncryptionEnabled | bool | Whether the password can be decrypted |
| precedence | msDS-PasswordSettingsPrecedence | int | The precedence (lower value == higher rank) of this PSO |
| sid | objectSid | string | The PSO's SID (Security Identifier) |

#### AD Deleted Objects Types

These are deleted objects that reside in the AD Recycle Bin.

* **Type**: `LdapObjectType::DELETED`
* **Filter**: `(&(isDeleted=TRUE)(lastKnownParent=*))`

| LdapTools Name  | LDAP Attribute | Value Type | Description |
| --------------- | -------------- | ---------- | ----------- |
| created | whenCreated | `\DateTime` | The date when the object was created |
| description | description | string | The description of the deleted object |
| dn | distinguishedName | string | The full distinguished name of the deleted object |
| firstName | givenName | string | The first name of the deleted object (if any) |
| guid | objectGuid | string | The deleted object's GUID |
| isDeleted | isDeleted | bool | Whether the object is deleted |
| lastName | sn | string | The last name of the deleted object (if any) |
| lastKnownLocation | lastKnownParent | string | The DN of the last known location (ie. ou=foo,dc=example,dc=com) |
| lastKnownName | lastKnownRdn | string | The last known name (RDN) for the deleted object |
| modified | whenChanged | `\DateTime` | The date the deleted object was last modified |
| name | cn | string | The current name (RDN) of the deleted object |
| schemaType | objectClass| string | The LdpaTools schema type of the object (ie. user, group, computer, etc) |
| sid | objectSid | string | The deleted object's SID (Security Identifier)
| upn | userPrincipalName | string | The user principal name of the deleted object (if any) |

### The Exchange Schema
---

#### Exchange Server Types

These are Exchange Servers.

* **Type**: `LdapObjectType::EXCHANGE_SERVER`
* **Filter**: `(&(objectClass=msExchExchangeServer)(serverRole=*))`

| LdapTools Name  | LDAP Attribute | Value Type | Description |
| --------------- | -------------- | ---------- | ----------- |
| name | cn | string | The common name (RDN) of the exchange server |
| created | whenCreated | `\DateTime` | The date the exchange server object was created |
| guid | objectGuid | string | The exchange server's GUID |
| modified | whenModified | `\DateTime` | The date the exchange server was last modified |
| roles | msExchCurrentServerRoles | array | All of the role names for the exchange server |
| sid | objectSid | string | The exchange server's SID (Security Identifier) |
| version | serialNumber | string | The friendly Exchange version name (ie. Exchange 2013 RTM) |

#### Exchange Database Types

These are Exchange Databases.

* **Type**: `LdapObjectType::EXCHANGE_DATABASE`
* **Filter**: `(objectClass=msExchMDB)`

| LdapTools Name  | LDAP Attribute | Value Type | Description |
| --------------- | -------------- | ---------- | ----------- |
| name | cn | string | The common name (RDN) of the exchange database | 
| created | whenCreated | `\DateTime` | The date the exchange database was created |
| guid | objectGuid | string | The exchange database's GUID |
| isBeingRestored | msexchdatabasebeingrestored | bool | Whether the database is currently being restored |
| modified | whenModified | `\DateTime` | The date the exchange database was last modified |
| mountOnStartup | msexchedboffline | bool | Whether or not the database should be mounted on startup |
| sid | objectSid | string | The exchange database's SID (Security Identifier) |

#### Exchange Mailbox User Types

These are mailbox user accounts. They extend the AD User type, so all attributes available there are available here
along with the attributes below.

* **Type**: `LdapObjectType::EXCHANGE_MAILBOX_USER`
* **Filter**: `(&(objectClass=user)(objectCategory=person)(msExchMailboxGUID=*))`

| LdapTools Name  | LDAP Attribute | Value Type | Description |
| --------------- | -------------- | ---------- | ----------- |
| activeSyncPolicy | msExchMobileMailboxPolicyLink | string | The Active Sync policy name |
| alias | mailNickname | string | The Exchange Mailbox alias | 
| archiveDatabase | msExchArchiveDatabaseLink | string | The Archive Database name |
| archiveGuid | msExchArchiveGUID | string | The GUID for the Mailbox archive |
| archiveHardQuota | msExchArchiveQuota | int | The hard quota for the archive |
| archiveName | msExchArchiveName | string | The archive name |
| archiveWarnQuota | msExchArchiveWarnQuota | int | The warning quota for the archive |
| calendarLoggingDisabled | msExchELCMailboxFlags | bool | Whether calendar logging is disabled |
| calendarLoggingEnabled | msExchELCMailboxFlags | bool | Whether calendar logging is enabled |
| defaultSmtpAddress | proxyAddresses | string | The default SMTP address for the mailbox |
| hideFromAddressBooks | msExchHideFromAddressLists | bool | Whether or not to hide the mailbox from the address books |
| isArchiveDatabaseValid | msExchELCMailboxFlags | bool | Whether or not the archive DB is considered valid |
| language | msExchUserCulture | string | The language (ie en-us) for the mailbox |
| litigationDate | msexchLitigationHoldDate | `\DateTime` | The datetime for litigation hold |
| litigationEnabled | msExchELCMailboxFlags | bool | Whether or not litigation is enabled |
| litigationOwner | msexchLitigationHoldOwner | string | The litigation owner name |
| mailboxDatabase | homeMDB | string | The name of the database where the mailbox resides | 
| mailboxDisabled | msExchUserAccountControl | bool | Whether or not the mailbox is disabled |
| mailboxGuid | msExchMailboxGUID | string | The GUID of the mailbox |
| mailboxSecurity |  msExchMailboxSecurityDescriptor | `SecurityDescriptor` | The mailbox security permissions |
| mailboxServer | msExchHomeServerName | string | The name of the exchange server where the mailbox resides |
| mailTips | msExchSenderHintTranslations | string | 
| mrmEnabled | msExchELCMailboxFlags | bool | Whether or not message records management is enabled |
| quotaSizeWarning | mDBStorageQuota | int | The quota size warning for the mailbox |
| quotaSizeProhibitSend | mDBOverQuotaLimit | int | The quota size to limit sends at |
| quotaSizeProhibitAll | mDBOverHardQuotaLimit | int | The quota size to limit all actions at |
| rbacPolicy | msExchRBACPolicyLink | string | The RBAC policy name |
| retentionEnabled |  msExchELCMailboxFlags | bool | Whether or not retention is enabled |
| retentionPolicy | msExchMailboxTemplateLink | string | The retention policy name |
| recipientDisplayType | msExchRecipientDisplayType | string | The recipient display type |
| recipientPolicies | msExchPoliciesIncluded | array | The recipient policy names for the mailbox |
| recipientTypeDetails | msExchRecipientTypeDetails | string | The recipient type details |
| sendOnBehalfOf | publicDelegates | array | The users allowed to send-on-behalf of this mailbox |
| showInAddressBooks | showInAddressBook | array | The address book names where this mailbox should appear |
| singleItemRecoveryEnabled | msExchELCMailboxFlags | bool | Whether or not single item recovery is enabled |
| smtpAddresses | proxyAddresses | array | All of the SMTP addresses for the mailbox |
| useDefaultQuota | mDBUseDefaults | bool | Whether or not the mailbox should use the default quota limits | 

#### Exchange Recipient Policy Types

These are Exchange Recipient policies.

* **Type**: `LdapObjectType::EXCHANGE_RECIPIENT_POLICY`
* **Filter**: `(objectClass=msExchRecipientPolicy)`

| LdapTools Name  | LDAP Attribute | Value Type | Description |
| --------------- | -------------- | ---------- | ----------- |
| name | cn | string | The common name (RDN) of the recipient policy |
| created | whenCreated | `\DateTime` | The date the recipient policy was created |
| guid | objectGuid | string | The recipient policy's GUID |
| modified | whenModified | `\DateTime` | The date the recipient policy was last modified |
| sid | objectSid | string | The recipient policy's SID (Security Identifier |

#### Exchange ActiveSync Policy Types

These are Exchange ActiveSync policies.

* **Type**: `LdapObjectType::EXCHANGE_ACTIVESYNC_POLICY`
* **Filter**: `(objectClass=msExchMobileMailboxPolicy)`

| LdapTools Name  | LDAP Attribute | Value Type | Description |
| --------------- | -------------- | ---------- | ----------- |
| name | cn | string | The common name (RDN) of the ActiveSync policy |
| created | whenCreated | `\DateTime` | The date the ActiveSync policy was created |
| guid | objectGuid | string | The ActiveSync policy's GUID |
| modified | whenModified | `\DateTime` | The date the ActiveSync policy was last modified |
| numberOfPreviousPasswordsDisallowed | msExchMobileDeviceNumberOfPreviousPasswordsDisallowed | int | Password history limit |
| sid | objectSid | string | The ActiveSync policy's SID (Security Identifier) |

#### Exchange RBAC Policy Type

These are Exchange RBAC policies.

* **Type**: `LdapObjectType::EXCHANGE_RBAC_POLICY`
* **Filter**: `(objectClass=msExchRBACPolicy)`

| LdapTools Name  | LDAP Attribute | Value Type | Description |
| --------------- | -------------- | ---------- | ----------- |
| name | cn | string | The common name (RDN) of the RBAC policy |
| created | whenCreated | `\DateTime` | The date the RBAC policy was created |
| guid | objectGuid | string | The RBAC policy's GUID |
| modified | whenModified | `\DateTime` | The date the RBAC policy was last modified |
| sid | objectSid | string | The RBAC policy's SID (Security Identifier) |

#### Exchange Transport Rule Types

These are Exchange Transport Rules.

* **Type**: `LdapObjectType::EXCHANGE_TRANSPORT_RULE`
* **Filter**: `(objectClass=msExchTransportRule)`

| LdapTools Name  | LDAP Attribute | Value Type | Description |
| --------------- | -------------- | ---------- | ----------- |
| name | cn | string | The common name (RDN) of the transport rule |
| created | whenCreated | `\DateTime` | The date the transport rule was created |
| guid | objectGuid | string | The transport rule's GUID |
| modified | whenModified | `\DateTime` | The date the transport rule was last modified |
| sid | objectSid | string | The transport rule's SID (Security Identifier) |

#### Exchange DAG Types

This represents an Exchange DAG.

* **Type**: `LdapObjectType::EXCHANGE_DAG`
* **Filter**: `(objectClass=msExchMDBAvailabilityGroup)`

| LdapTools Name  | LDAP Attribute | Value Type | Description |
| --------------- | -------------- | ---------- | ----------- |
| name | cn | string | The common name (RDN) of the DAG |
| created | whenCreated | `\DateTime` | The date the DAG was created |
| guid | objectGuid | string | The DAG's GUID |
| modified | whenModified | `\DateTime` | The date the DAG was last modified |
| sid | objectSid | string | The DAG's SID (Security Identifier) |

#### Exchange OWA Types

These are the Exchange OWA instances.

* **Type**: `LdapObjectType::EXCHANGE_OWA`
* **Filter**: `(objectClass=msExchOWAVirtualDirectory)`

| LdapTools Name  | LDAP Attribute | Value Type | Description |
| --------------- | -------------- | ---------- | ----------- |
| name | cn | string | The common name (RDN) of the OWA object |
| created | whenCreated | `\DateTime` | The date the OWA object was created |
| guid | objectGuid | string | The OWA's GUID |
| modified | whenModified | `\DateTime` | The date the OWA object was last modified |
| sid | objectSid | string | The OWA's SID (Security Identifier) |
| url | msExchInternalHostname | string | The URL of the OWA instance |

### The OpenLDAP Schema
---

#### OpenLDAP User Types

These are typical LDAP user objects.

* **Type**: `LdapObjectType::USER`
* **Filter**: `(objectClass=inetOrgPerson)`

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
| groups | memberOf | array |
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

These are typical LDAP group objects.

* **Type**: `LdapObjectType::GROUP`
* **Filter**: `(objectClass=groupOfUniqueNames)`

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

These are typical LDAP OU objects.

* **Type**: `LdapObjectType::OU`
* **Filter**: `(objectClass=organizationalUnit)`

| LdapTools Name  | LDAP Attribute | Value Type |
| --------------- | -------------- | ---------- |
| created | createTimestamp | `\DateTime` |
| dn | dn | string |
| modified | modifyTimestamp | `\DateTime` |
| name | ou | string |
