# Clarabridge Engage Api

<a href="https://travis-ci.org/turanct/engagor-api"><img src="https://travis-ci.org/turanct/engagor-api.svg?branch=master" alt="Build Status"></a>


## Install

Via [Composer](https://getcomposer.org/)

```bash
$ composer require turanct/engagor-api
```

Or manually add `turanct/engagor-api` to your `composer.json` and run `composer install`.


## Usage

### Setting up

For this library to work, you'll need a PSR-7, PSR-17 and PSR-18 implementation. It doesn't really matter which ones, but an easy way to get started is to use these in your `composer.json` and then run `composer install`.

```json
"nyholm/psr7": "^1.2.0",
"kriswallsmith/buzz": "^1.0.1",
```

In this case PSR-7 and PSR-17 implementations are provided by `nyholm/psr7` and the PSR-18 implementation is `kriswallsmith/buzz`.


### Authentication

Create an `Authentication` instance:

```php
$httpClient = new Buzz\Client\Curl($requestFactory);
$httpRequestFactory = new Nyholm\Psr7\Factory\Psr17Factory();

$clientId = '<CLIENT ID HERE>';
$clientId = '<CLIENT SECRET HERE>';

$authentication = new Engagor\Authentication(
    $httpClient,
    $httpRequestFactory,
    $clientId,
    $clientSecret
);
```

Once you have created this instance, you can use it to authenticate the user of your app with Engage:

```php
$url = $authentication->step1(
    array(
        'identify',
        'accounts_read',
        'accounts_write',
        'socialprofiles',
        'email',
    ),
    '<RANDOM STATE HERE>'
);
```

This will return an array that you can redirect your users too. Insert a random state, and save it in the user's session so that you can verify later on that the redirect URL that the user will be sent to is valid.

That redirect URL will look a bit like this:

```
https://example.com/your-redirect-url?state=<RANDOM STATE HERE>&code=<YOUR AUTH CODE>
```

You should verify the state to be the same that you generated when you called `step1()` to create the redirect URL in the previous step. If that check succeeds, the `code` that's also in the request to your redirect endpoint is what you'll need for the next step:

```php
$tokens = $authentication->step2('<YOUR AUTH CODE>');
```

If it's successful, you should get a `Tokens` object, which holds the access & refresh tokens that we can use to issue calls to the Engage api.


### Making authenticated requests to the API

Now that you've obtained a `Tokens` object, you can create a `Client instance`:

```php
$client = new Engagor\Client(
    $httpClient,
    $httpRequestFactory,
    $tokens
);
```

The `$httpClient` and `$httpRequestFactory` can be the same instances as described in the [Authentication](#authentication) step above.

Now that you've got an API `$client` instance, you can either call the implemented API methods using the methods with their name, e.g. `/me` will be called `me()`, or you can use the `request()` method to manually make requests to the API.

```php
$me = $client->me();
```

or

```php
$request = $httpRequestFactory->createRequest(
    'GET',
    'https://api.engagor.com/me/'
);

$response = $client->execute($request);
```


## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.


## Credits

- [Toon Daelman](https://github.com/turanct)


## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
