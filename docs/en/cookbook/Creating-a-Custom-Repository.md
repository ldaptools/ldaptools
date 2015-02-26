# Creating a Custom Repository
------------------------------

A LDAP Object Repository gives a way to encapsulate and reuse common queries you may run against LDAP. So rather than
recoding the same query in several spots, you can code it once in the repository then use the repository method instead.

Your custom repository class should extend the `\LdapTools\LdapObjectRepository` class. For instance, your class could
be something like:

```php
namespace Acme\Demo;

use LdapTools\LdapObjectRepository;

class CustomUserRepository extends LdapObjectRepository
{
    public function getAllSmiths()
    {
        // You can use the buildLdapQuery() method to construct a LdapQueryBuilder instance.
        return $this->buildLdapQuery()
            ->where(['lastName' => 'Smith'])
            ->getLdapQuery()
            ->execute();
    }
}
```

Then make sure to define your new repository in the the schema config as a directive underneat the `user` object type:

```yaml
objects:
    #...
    user_type:
        #...
        repository: '\Acme\Demo\CustomUserRepository'
```

Then once you have your `LdapManager` class instantiated you can get your custom repository and use it:
 
```php
 // Assuming you defined the custom repository under the 'user' object type.
 $repository = $ldapManager->getRepository('user');
 
 $smiths = $repository->getAllSmiths();
```
