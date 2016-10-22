# Getting Your LDAP SSL Certificate
------------------------------

Sometimes trying to determine your LDAP SSL certificate can be a challenge if you're not completely aware of your environment
and what servers are actually involved. To accomplish this in an easier way you can determine your LDAP SSL certificate
in a programmatic way using a utility function in this library.
  
#### 1. Determine Your LDAP Servers
 
If you already know what LDAP servers are in your environment, then you can skip to the next step. However, if you
aren't sure of the names of your LDAP servers you can easily figure them out using the utility function below:
 
```php
use LdapTools\Utilities\LdapUtilities;

// Simply pass this function your domain name...
$servers = LdapUtilities::getLdapServersForDomain('example.local');

foreach ($servers as $server) {
    echo $server.PHP_EOL;
}
```

**NOTE**: The above does require that you have DNS properly configured on the box you run PHP from. To determine the
LDAP servers in the domain it needs to query DNS service records.

#### 2. Using a LDAP Server, Get the SSL Certificates

Using one of the servers from above, pass it to another utility function to retrieve the LDAP SSL certificates the server
is using:

```php
use LdapTools\Utilities\LdapUtilities;

// This will retrieve an array containing the 'peer_certificate' and 'peer_certificate_chain'...
$certificates = LdapUtilities::getLdapSslCertificates('dc1.example.local');

// You can use the above to create a certificate bundle containing all your needed LDAP SSL certificates...
$bundle = $certificates['peer_certificate'];
foreach($certificates['peer_certificate_chain'] as $cert) {
    $bundle .= $cert;
}

// You can now output the certificate bundle to a separate location...
file_put_contents('./ldap-ssl-bundle.crt', $bundle);
```

**NOTE**: The above grabs the certificates using a StartTLS command and inspecting the SSL stream.

#### 3. Reference Your New SSL Cert Bundle in Your LDAP Config

To make use of the new bundles you need to use the `TLS_CACERT` directive in your ldap.conf file. You just need to point
it to the location where you saved your certificate in step 3.

```
TLS_REQCERT hard
TLS_CACERT /path/to/ldap-ssl-bundle.crt
```

If you are using PHP 7.1 you can make use of the new constants to reference the certificate without needing the ldap.conf
file. The constants are `LDAP_OPT_X_TLS_CACERTFILE` and `LDAP_OPT_X_TLS_REQUIRE_CERT`:

```php
use LdapTools\DomainConfiguration;

// Make sure to set your LDAP options for your Domain Configuration...
$domain = (new DomainConfiguration('example.local'))
    ->setBaseDn('dc=example,dc=local')
    ->setServers(['dc1.example.local', 'dc2.example.local'])
    ->setUsername('foo')
    ->setPassword('secret')
    ->setUseTls(true)
    ->setLdapOptions([
        'LDAP_OPT_X_TLS_CACERTFILE' => '/path/to/ldap-ssl-bundle',
        'LDAP_OPT_X_TLS_REQUIRE_CERT' => LDAP_OPT_X_TLS_HARD,
    ]);
```

#### 4. Troubleshooting

If you did all of the above and you still cannot connect when setting `use_tls` to `true` then there are a few ways to go
about figuring out what is going wrong. 

It's possible that your environment has an intermediate server that issues certificates along with an enterprises root 
Certificate Authority. In this case not all of the certificates needed for the certificate chain will be created in step 2.
In order to troubleshoot this you can view the full certificate chain by opening the resulting certificate file from step 2.
If there are 3 steps in the chain, then likely you are missing a certificate. The easiest way to get the missing
intermediate certificate would probably be to open the certificate bundle on a windows machine in the domain in question
and extract the intermediate certificate to a separate file (in base64 encoded format!). You can then copy the contents 
of that file into your bundle file from step 2.

It's also possible that OpenSSL has an issue with one of the certificates, perhaps due to a name or something else. To
get more details on the issue you can set the debug level to 7 prior to trying to connect to LDAP. Simply put this at the
beginning of your code:

```php

ldap_set_option(null, LDAP_OPT_DEBUG_LEVEL, 7);

# ...
```

Now when you connect you should get a more complete description of what exactly is failing.
