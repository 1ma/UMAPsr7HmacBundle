# UMAPsr7HmacBundle

Clean integration between the [Psr7Hmac](https://github.com/1ma/Psr7Hmac) authentication library and the Symfony [Security Component](http://symfony.com/doc/current/security.html).

[![Build Status](https://travis-ci.org/1ma/UMAPsr7HmacBundle.svg?branch=master)](https://travis-ci.org/1ma/UMAPsr7HmacBundle) [![Code Coverage](https://scrutinizer-ci.com/g/1ma/UMAPsr7HmacBundle/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/1ma/UMAPsr7HmacBundle/?branch=master) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/1ma/UMAPsr7HmacBundle/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/1ma/UMAPsr7HmacBundle/?branch=master) [![Code Climate](https://codeclimate.com/github/1ma/UMAPsr7HmacBundle/badges/gpa.svg)](https://codeclimate.com/github/1ma/UMAPsr7HmacBundle) [![SensioLabsInsight](https://insight.sensiolabs.com/projects/8940fbf1-1197-43fc-aea9-2b441d8fe231/mini.png)](https://insight.sensiolabs.com/projects/8940fbf1-1197-43fc-aea9-2b441d8fe231)


## Server setup

Add [`uma/psr7-hmac-bundle`](https://packagist.org/packages/uma/psr7-hmac-bundle) to your `composer.json` file:

    php composer.phar require "uma/psr7-hmac-bundle"

And register the bundle in `app/AppKernel.php`:

``` php
public function registerBundles()
{
    return [
        // ...
        new UMA\Psr7HmacBundle\UMAPsr7HmacBundle(),
    ];
}
```

Create your own `User` entity and implement the `HmacApiClientInterface` provided
by the bundle. Alternatively you can implement the convenience `HmacApiUserInterface`, which
groups Symfony's `UserInterface` and `HmacApiClientInterface` together.

``` php
use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use UMA\Psr7HmacBundle\Definition\HmacApiClientInterface;
use UMA\Psr7HmacBundle\Definition\HmacApiUserInterface;

class User implements UserInterface, HmacApiClientInterface
{
    // mandatory method declarations...
}

class User implements HmacApiUserInterface // Equivalent to the above class definition
{
}

class User implements AdvancedUserInterface, HmacApiClientInterface // This would be acceptable, too
{
}
```

The Symfony documentation offers [plenty of guidance](http://symfony.com/doc/current/security/entity_provider.html) about how to implement the `UserInterface`.
To implement the `HmacApiClientInterface` you can base your entity on the following skeleton, or check out the [full example](https://github.com/1ma/hmac-api-symfony/blob/cb5bcbd51691352d98859f859fb4f3ef72313443/src/AppBundle/Entity/Customer.php) from the showcase project.

``` php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use UMA\Psr7HmacBundle\Definition\HmacApiClientInterface;

/**
 * @ORM\Entity()
 * @ORM\Table(name="api_users")
 */
class ApiUser implements UserInterface, HmacApiClientInterface
{
    // A 18 byte sequence produces a 24 character long
    // base64 string with no padding (trailing equal signs).
    // Moreover, 18 bytes equal 144 bits which is even more than
    // what a standard UUID has (128 bits of entropy).
    const BYTES_OF_ENTROPY = 18;
    
    // other constants and attribute declarations...

    /**
     * @var string
     *
     * @example 'MDEyMzQ1Njc4OUFCQ0RFRkdI'
     *
     * @ORM\Column(name="api_key", type="string", length=24, unique=true)
     */
    private $apiKey;

    /**
     * @var string
     *
     * @example 'MDEyMzQ1Njc4OUFCQ0RFRkdI'
     *
     * @ORM\Column(name="shared_secret", type="string", length=24)
     */
    private $sharedSecret;

    public function __construct()
    {
        $this->apiKey = base64_encode(random_bytes(static::BYTES_OF_ENTROPY));
        $this->sharedSecret = base64_encode(random_bytes(static::BYTES_OF_ENTROPY));
    }

    /**
     * {@inheritdoc}
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * {@inheritdoc}
     */
    public function getSharedSecret()
    {
        return $this->sharedSecret;
    }

    // other method declarations...
}
```

Finally, configure an `hmac` firewall in your `app/config/security.yml` file.

``` yaml
security:
    providers:
        # you can define your user provider in any other way you want, just make sure that the
        # users' class implements both the UserInterface and the HmacApiClientInterface
        provider.api_user:
            entity:
                class: AppBundle\Entity\ApiUser
                property: apiKey

    firewalls:
        dev:
            pattern: ^/(_(profiler|wdt|homepage)|css|images|js)/
            security: false
        api:
            pattern: ^/
            hmac: ~
            provider: provider.api_user
            # this flag is mandatory because persistent sessions do not make 
            # any sense in the context of HMAC authentication
            stateless: true 

    # The UMAPsr7HmacBundle is only concerned about user authentication (who are they). Therefore you must
    # handle their authorization (what they can do) using Symfony's standard mechanisms, such as roles and voters.
    access_control:
        - { path: ^/, roles: ROLE_API_USER }
```

At this point you'll probably want to persist an ApiUser entity to start making your first requests, so
make sure that you also have at least one route protected under your new firewall.


## Client setup

In order to make an HMAC authenticated request, a client needs to install the [Psr7Hmac](https://github.com/1ma/Psr7Hmac) library (available
through Composer) and hold an API key and a shared secret from one of the application users. The lifecycle of an HMAC request goes like this:

### Client side
1. The client prepares an instance of PSR-7's RequestInterface as needed. The Guzzle Request object is one such implementation, but Psr7Hmac supports many more.
2. It then sets an `Api-Key` HTTP header containing the API key.
3. A new instance of Psr7Hmac's `Signer` class is instantiated with the shared secret, and the resulting request from step 2 is passed to its `sign` method, which produces a signed request.
4. The signed request is sent to the server, for instance using Guzzle's Client service.

### Server side
1. When the server receives the request it extracts its `Api-Key` header and looks for an ApiUser with a matching key.
2. If it finds one, it retrieves the user from the database and attempts to produce the same signature that was present in the request using his shared secret.
3. If the signatures match the authentication is successful,the request proceeds to the controller and the involved API user will be available through the `Controller::getUser()` helper and/or Symfony's `TokenStorage` service.
4. If any of the above 3 steps fails, the bundle arranges an 401 Unauthorized response on behalf of the application and the controller is never reached.

### Brief client example

This example assumes:
* That the server (a Symfony application) has the bundle properly set up, as discussed above.
* That it has a persisted ApiUser fixture from which to get an API key and shared secret for the client.
* That the server defines a `/hello/{name}` like the one that used to be included in the DemoBundle of Symfony's Standard Edition of old.

``` php
// client.php

require_once __DIR__.'/vendor/autoload.php';

$client = new \GuzzleHttp\Client();
$signer = new \UMA\Psr7Hmac\Signer('am9qZHNhOGo4ZGE4c2o4OGRo');

// It is very important to provide the full URI with the domain name because
// the Host HTTP header is used by the signature algorithm.
// On a development environment http://localhost/hello/turtle would also work.
$request = (new \GuzzleHttp\Psr7\Request('GET', 'https://example.com/hello/turtle'))
    ->withHeader('Api-Key', 'MDEyMzQ1Njc4OUFCQ0RFRkdI');
$signedRequest = $signer->sign($request);

// Hint: modify the $signedRequest here

$response = $client->send($signedRequest);
var_dump((string) $response->getBody()); // Hello turtle!
```

If you modify the $signedRequest before sending it to the server it will return an HTTP 401 response.

### Advanced client example

The use case that the UMAPsr7HmacBundle tries to solve is HMAC authentication between PHP backends communicating over HTTP.
In this scenario it may possibly be the case that both the client and server are Symfony projects, and that the ApiUsers
actually represent applications consuming some kind of service provided over HTTP by the server.

When that is indeed the case I would advise to leverage the DIC on the client side to do some of the work and build an agnostic "SDK service"
that accepts plain PSR-7 requests, tags them with the Api-Key header, signs them, sends them and returns the server response.

This would be the general idea:
``` yaml
// app/config/parameters.yml
example.api_key: 'MDEyMzQ1Njc4OUFCQ0RFRkdI'
example.shared_secret: 'am9qZHNhOGo4ZGE4c2o4OGRo'
```
``` yaml
// app/config/services.yml
services:
  example_hmac_signer:
    class: UMA\Psr7Hmac\Signer
    arguments: ["%example.shared_secret%"]

  example_sdk:
    class: AppBundle\Service\ExampleSDK
    arguments: ["%example.api_key%", "@example_hmac_signer"]
```
``` php
// src/AppBundle/Service/ExampleSDK.php

namespace AppBundle\Service;

use UMA\Psr7Hmac\Signer;

class ExampleSDK
{
    private $apiKey;
    private $signer;
    
    public function __construct(string $apiKey, Signer $signer)
    {
        $this->signer = $signer;
        $this->apiKey = $apiKey;
    }
    
    public function send(RequestInterface $request): ResponseInterface
    {
        // some sort of adaptation of the "brief client example"
    }
}
```


## Advanced configuration

UMAPsr7HmacBundle sports a couple of knobs to customize it a little further.

### Customize the `Api-Key` header

Set it at `apikey_header` under the `hmac` key in your `security.yml` file. Boom, done.

``` yaml
// app/config/security.yml
    firewalls:
        default:
            pattern: ^/api
            hmac:
                apikey_header: 'X-My-Custom-Header-Key'
            stateless: true
```

### Customize the error response

By default the bundle will return a laconic `401 Unauthorized` response for
every authentication error that happens inside an hmac firewall.

However, Symfony's Security Component has a little known feature called [entry points](http://symfony.com/doc/current/components/security/firewall.html#entry-points) that are nothing more than implementations of the [AuthenticationEntryPointInterface](http://api.symfony.com/3.1/Symfony/Component/Security/Http/EntryPoint/AuthenticationEntryPointInterface.html)
and let you do just that.
``` yaml
// app/config/security.yml
    firewalls:
        default:
            pattern: ^/api
            hmac: ~
            stateless: true
            entry_point: my_entry_point_id
```

## FAQ

### I don't like long, random API keys

Well, then just return anything you want in the `getApiKey()` method from your user, for instance an email or whatever.
Just make sure that these "API keys" are unique between all users, maybe even at the database level using UNIQUE constraints.

### Shouldn't you store a hash of the shared secret in the server database, like you would do with a regular password?

That'd be kinda nice, but you can't.

The only reason you get to store user passwords as cryptographic hashes is because your application does not actually
need their _content_, rather it just need to check that the stored password is the same that the one received during a
login attempt, and to do that you can just compare their hashes. On the other hand the contents of a shared secret are
actually needed in order to calculate an HMAC signature.

On a brighter note, machine-generated random shared secrets are not nearly as sensitive as user-provided passwords that
are potentially reused all over the internet, so the extra hassle of hashing them may not even be warranted.
