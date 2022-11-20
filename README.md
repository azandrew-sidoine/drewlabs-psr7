# Psr7 interfaces implementation

This package provides implementations for Psr7 request, response and URi interfaces.

## Installation

Use `composer` package manager [https://getcomposer.org/download/].

> composer require drewlabs/psr7

## Usage

- Creating Psr7 class

`drewlabs/psr7` package provides a `Drewlabs\Psr7\Request` class for creating a psr7 request:

```php
use Drewlabs\Psr7\Request;

// Instantiate the psr7 class
$request = new Request();

// Overriding the request method
$request = $request->withMethod('POST');

// Overriding request headers
$request = $request->withHeaders([
    // ...
]);
```

- Creating Psr7 response interface

`drewlabs/psr7` package provides a `Drewlabs\Psr7\Response` class for creating a psr7 response:

```php
use Drewlabs\Psr7\Request;

// Instantiate the psr7 class
$response = new Response();

// Instantiate response with parameters
$response = new Response('', [/* Response headers */]);

// Overriding the response method
$response = $response->withStatusCode(404);

// Overriding response headers
$response = $response->withHeaders([
    // ...
]);
```

- Creating Psr7 URI instance

`drewlabs/psr7` package provides a `Drewlabs\Psr7\Uri` class for creating a psr7 uri instances:

```php
$uri = Uri::new(/* URI string | or Null */);
```
