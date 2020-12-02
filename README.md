<img src="https://static.germania-kg.com/logos/ga-logo-2016-web.svgz" width="250px">

------




# Germania KG ·  ClientLocation

[![Packagist](https://img.shields.io/packagist/v/germania-kg/client-location.svg?style=flat)](https://packagist.org/packages/germania-kg/client-location)
[![PHP version](https://img.shields.io/packagist/php-v/germania-kg/client-location.svg)](https://packagist.org/packages/germania-kg/client-location)
[![Build Status](https://img.shields.io/travis/GermaniaKG/ClientLocation.svg?label=Travis%20CI)](https://travis-ci.org/GermaniaKG/ClientLocation)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/GermaniaKG/ClientLocation/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/GermaniaKG/ClientLocation/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/GermaniaKG/ClientLocation/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/GermaniaKG/ClientLocation/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/GermaniaKG/ClientLocation/badges/build.png?b=master)](https://scrutinizer-ci.com/g/GermaniaKG/ClientLocation/build-status/master)



## Installation

```bash
$ composer require germania-kg/client-location
```



## PSR-15 Middleware

The middleware checks the incoming *ServerRequest* for a `client-ip` attribute. The client IP is passed to the *factory callable* which returns some location information. This client location is then stored in the *ServerRequest* as `client-location` attribute.

If the `client-ip` attribute is not set or empty, the middleware does nothing and passes on to the *RequestHandler*.

The **ClientIpLocationMiddleware** implements PSR-15 *MiddlewareInterface*. The constructor requires 

- **Factory callable** which accepts the client's IP address and actually determines the client location.
- **PSR-3 Logger** for errors. 
- An optional **PSR-3 error loglevel name** can be added optionally. Default is `LogLevel::ERROR`

```php
<?php
use Germania\ClientIpLocation\ClientIpLocationMiddleware;
use Psr\Log\LogLevel;

$fn = function($ip) { return "Berlin"; };
$logger = new Monolog(...);

$middleware = new ClientIpLocationMiddleware($fn, $logger);
$middleware = new ClientIpLocationMiddleware($fn, $logger, LogLevel::ERROR);
```

**Configuration**

Both attribute names can be renamed. Here the defaults:

```php
$middleware->setClientIpAttributeName("client-ip")
           ->setClientLocationAttributeName("client-location");
```




## Issues

See [full issues list.][i0]

[i0]: https://github.com/GermaniaKG/ClientLocation/issues



## Development

Grab and go using one of these:

```bash
$ git clone git@github.com:GermaniaKG/ClientLocation.git
$ gh repo clone GermaniaKG/ClientLocation
```




## Unit tests

Either copy `phpunit.xml.dist` to `phpunit.xml` and adapt to your needs, or leave as is. Run [PhpUnit](https://phpunit.de/) test or composer scripts like this:

```bash
$ composer test
# or
$ vendor/bin/phpunit
```

