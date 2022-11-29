# Nette HTTP

Extras for [nette/http](https://github.com/nette/http)

## Content

- [Setup](#setup)
- [HTTP authenticator](#http-authenticator)

## Setup

Install with [Composer](https://getcomposer.org)

```sh
composer require orisai/nette-http
```

## HTTP authenticator

[HTTP authentication](https://developer.mozilla.org/en-US/docs/Web/HTTP/Authentication)
via [Basic Auth](https://datatracker.ietf.org/doc/html/rfc7617)

Ideal for hiding publicly available dev version of the app.

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

[Optional] Exclude paths from http authentication (ideal for APIs which don't expect human interaction and would need
API client to be modified for basic auth support)

```neon
orisai.http.auth:
	exclude:
		paths:
			- /api
```

[Optional] Change title and possible (random) error responses from default ones (if you don't like Lord Of The Rings)

```neon
orisai.http.auth:
	title: HTTP authentication
	errorResponses:
		- Not allowed
```
