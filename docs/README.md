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

Register and enable extension

```neon
extensions:
	orisai.http.auth: OriNette\Http\Auth\DI\HttpAuthExtension

orisai.http.auth:
	enabled: true
```

Add list of authorized users

- in `username:password` format
- both plain passwords and passwords hashed
  via [password_hash](https://www.php.net/manual/en/function.password-hash.php) are accepted

```neon
orisai.http.auth:
	users:
		user1: password
		user2: $2y$10$kP2nVtmSOLA2LIDnwNxa9.MpL0VnCddBOGltj1zySsLF7AxYQae3a
```

[Optional] Exclude paths from http authentication

```neon
orisai.http.auth:
	exclude:
		paths:
			- /api
```

[Optional] Change title and error response from default ones (if you don't like Lord Of The Rings)

```neon
orisai.http.auth:
	title: HTTP authentication
	errorResponse: Not allowed
```
