# Nette HTTP

Extras for [nette/http](https://github.com/nette/http)

## Content

- [Setup](#setup)
- [HTTP authenticator](#http-authenticator)
- [Test response](#test-response)

## Setup

Install with [Composer](https://getcomposer.org)

```sh
composer require orisai/nette-http
```

## HTTP authenticator

[HTTP authentication](https://developer.mozilla.org/en-US/docs/Web/HTTP/Authentication)
via [WWW-Authenticate](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/WWW-Authenticate) basic auth

- Ideal for hiding publicly available dev version of the app.
- Runs only in HTTP mode, CLI mode is not affected.

Register and enable extension

```neon
extensions:
	orisai.http.auth: OriNette\Http\Auth\DI\HttpAuthExtension

orisai.http.auth:
	enabled: true
```

Add list of authorized users

- in `username: password` format
- both plain passwords and passwords hashed
  via [password_hash](https://www.php.net/manual/en/function.password-hash.php) are accepted

```neon
orisai.http.auth:
	users:
		user1: password
		user2: $2y$10$kP2nVtmSOLA2LIDnwNxa9.MpL0VnCddBOGltj1zySsLF7AxYQae3a
```

Integration with debugger

- If user is not authorized, [Tracy](https://github.com/nette/tracy/) is set to production mode, so it does not leak
  debug info
- If you use any other debugger, please raise an issue

[Optional] Exclude paths from http authentication

- ideal for APIs which don't expect human interaction and would need API client to be modified for basic auth support
- if your app runs in path (base url is e.g. https://example.com/script) then don't include the `/script` part in
  excluded path, it is handled automatically

```neon
orisai.http.auth:
	exclude:
		paths:
			- /api
```

[Optional] Change [realm](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/WWW-Authenticate#realm) and possible
(random) error responses from default ones (if you don't like Lord Of The Rings)

```neon
orisai.http.auth:
	# string|null
	realm: App
	# list<string>
	errorResponses:
		- Not allowed
```

## Test response

Implementation of `Nette\Http\IResponse` which does not send data immediately and instead keeps them inside value
object.

Use for testing classes which set data to response. It is currently not integrated with nette/application and should not
be used for a runtime code.

```php
use OriNette\Http\Tester\TestResponse;

$response = new TestResponse();
```
