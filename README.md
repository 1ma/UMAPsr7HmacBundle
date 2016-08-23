# UMAPsr7HmacBundle

Clean integration between the [Psr7Hmac](https://github.com/1ma/Psr7Hmac) authentication library and the Symfony [Security Component](http://symfony.com/doc/current/security.html).

[![Build Status](https://travis-ci.org/1ma/UMAPsr7HmacBundle.svg?branch=master)](https://travis-ci.org/1ma/UMAPsr7HmacBundle) [![Code Coverage](https://scrutinizer-ci.com/g/1ma/UMAPsr7HmacBundle/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/1ma/UMAPsr7HmacBundle/?branch=master) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/1ma/UMAPsr7HmacBundle/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/1ma/UMAPsr7HmacBundle/?branch=master) [![Code Climate](https://codeclimate.com/github/1ma/UMAPsr7HmacBundle/badges/gpa.svg)](https://codeclimate.com/github/1ma/UMAPsr7HmacBundle) [![SensioLabsInsight](https://insight.sensiolabs.com/projects/8940fbf1-1197-43fc-aea9-2b441d8fe231/mini.png)](https://insight.sensiolabs.com/projects/8940fbf1-1197-43fc-aea9-2b441d8fe231)


## Setup

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
            hmac: true
            provider: provider.api_user
            # this flag is mandatory because persistent sessions do not make 
            # any sense in the context of HMAC authentication
            stateless: true 

    # The UMAPsr7HmacBundle is only concerned about user authentication (who are they). Therefore you must
    # handle their authorization (what they can do) using Symfony's standard mechanisms, such as roles and voters.
    access_control:
        - { path: ^/, roles: ROLE_API_USER }
```
