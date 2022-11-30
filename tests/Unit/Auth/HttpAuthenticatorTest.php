<?php declare(strict_types = 1);

namespace Tests\OriNette\Http\Unit\Auth;

use Generator;
use Nette\Http\Request;
use Nette\Http\UrlScript;
use Nette\Utils\Helpers;
use OriNette\Http\Auth\HttpAuthenticator;
use OriNette\Http\Tester\TestResponse;
use PHPUnit\Framework\TestCase;
use Tracy\Debugger;

/**
 * @runTestsInSeparateProcesses
 */
final class HttpAuthenticatorTest extends TestCase
{

	private HttpAuthenticator $authenticator;

	protected function setUp(): void
	{
		parent::setUp();
		$this->authenticator = new HttpAuthenticator();
		$this->authenticator->setTestMode();
	}

	/**
	 * @dataProvider provideAllowed
	 */
	public function testAllowed(string $user, string $password, string $passwordHash): void
	{
		$request = new Request(new UrlScript("https://$user:$password@example.com"));
		$response = new TestResponse();

		$this->authenticator->addUser($user, $passwordHash);

		$this->authenticator->authenticate($request, $response);
		self::assertTrue(true);
	}

	public function provideAllowed(): Generator
	{
		yield ['user', 'password', 'password'];
		yield ['user', 'password', '$2y$10$kP2nVtmSOLA2LIDnwNxa9.MpL0VnCddBOGltj1zySsLF7AxYQae3a'];
	}

	/**
	 * @dataProvider provideNotAllowed
	 */
	public function testNotAllowed(?string $user, string $userExpected, ?string $password, string $passwordHash): void
	{
		$request = new Request(new UrlScript("https://$user:$password@example.com"));
		$response = new TestResponse();

		$this->authenticator->addUser($userExpected, $passwordHash);

		$echoed = Helpers::capture(fn () => $this->authenticator->authenticate($request, $response));

		self::assertSame(
			[
				'WWW-Authenticate' => [
					'Basic realm="Speak friend and enter."',
				],
			],
			$response->getHeaders(),
		);
		self::assertContains($echoed, HttpAuthenticator::DefaultErrorResponses);
	}

	public function provideNotAllowed(): Generator
	{
		yield [null, 'user', 'password', 'password'];
		yield ['user', 'user', null, 'password'];
		yield [null, 'user', null, 'password'];
		yield ['bad', 'user', 'password', 'password'];
		yield ['bad', 'user', 'password', '$2y$10$kP2nVtmSOLA2LIDnwNxa9.MpL0VnCddBOGltj1zySsLF7AxYQae3a'];
		yield ['user', 'user', 'bad', 'password'];
		yield ['user', 'user', 'bad', '$2y$10$kP2nVtmSOLA2LIDnwNxa9.MpL0VnCddBOGltj1zySsLF7AxYQae3a'];
	}

	/**
	 * @dataProvider provideExcludedPaths
	 */
	public function testExcludedPaths(string $currentPath): void
	{
		//TODO - test base path - currently disabled in HttpAuthenticator - seems like Nette does not like / on the end
		$request = new Request(new UrlScript("https://example.com$currentPath"));
		$response = new TestResponse();

		$this->authenticator->addExcludedPath('a');
		$this->authenticator->addExcludedPath('b/');
		$this->authenticator->addExcludedPath('/c');
		$this->authenticator->addExcludedPath('/d/');
		$this->authenticator->addExcludedPath('e///foo');

		$this->authenticator->authenticate($request, $response);
		self::assertTrue(true);
	}

	public function provideExcludedPaths(): Generator
	{
		yield ['/a'];
		yield ['/a/'];
		yield ['/b'];
		yield ['/b/'];
		yield ['/c'];
		yield ['/c/'];
		yield ['/d'];
		yield ['/d/'];
		yield ['/d/foo'];
		yield ['/d//foo'];
		yield ['/d/foo/'];
		yield ['/d/foo/bar'];
		yield ['/d/foo//bar'];
		yield ['/e/foo'];
	}

	/**
	 * @dataProvider provideNotAllowedPaths
	 */
	public function testNotAllowedPaths(string $currentPath): void
	{
		$request = new Request(new UrlScript("https://example.com$currentPath"));
		$response = new TestResponse();

		$this->authenticator->setTestMode();

		$this->authenticator->addExcludedPath('a');
		$this->authenticator->addExcludedPath('b/foo');

		$echoed = Helpers::capture(fn () => $this->authenticator->authenticate($request, $response));

		self::assertSame(
			[
				'WWW-Authenticate' => [
					'Basic realm="Speak friend and enter."',
				],
			],
			$response->getHeaders(),
		);
		self::assertContains($echoed, HttpAuthenticator::DefaultErrorResponses);
	}

	public function provideNotAllowedPaths(): Generator
	{
		yield ['/z/a'];
		yield ['/z/a/'];
		yield ['/za'];
		yield ['/za/'];
		yield ['/az'];
		yield ['/az/'];
		yield ['/b'];
		yield ['/b/'];
		yield ['/b/baz'];
		yield ['/b/baz/'];
		yield ['/c/b/foo'];
		yield ['/c/b/foo/'];
	}

	public function testTracy(): void
	{
		$this->authenticator->addUser('user', 'password');

		Debugger::$productionMode = false;

		$request = new Request(new UrlScript('https://user:password@example.com'));
		$response = new TestResponse();
		$this->authenticator->authenticate($request, $response);
		self::assertFalse(Debugger::$productionMode);

		$request = new Request(new UrlScript('https://example.com'));
		$response = new TestResponse();
		Helpers::capture(fn () => $this->authenticator->authenticate($request, $response));
		self::assertTrue(Debugger::$productionMode);
	}

	public function testCustomMessages(): void
	{
		$errorResponses = ['a', 'b', 'c'];

		$this->authenticator->setRealm('realm');
		$this->authenticator->setErrorResponses($errorResponses);

		$request = new Request(new UrlScript('https://example.com'));
		$response = new TestResponse();

		$echoed = Helpers::capture(fn () => $this->authenticator->authenticate($request, $response));

		self::assertSame(
			[
				'WWW-Authenticate' => [
					'Basic realm="realm"',
				],
			],
			$response->getHeaders(),
		);
		self::assertContains($echoed, $errorResponses);
	}

	public function testNoRealm(): void
	{
		$this->authenticator->setRealm(null);

		$request = new Request(new UrlScript('https://example.com'));
		$response = new TestResponse();

		Helpers::capture(fn () => $this->authenticator->authenticate($request, $response));

		self::assertSame(
			[
				'WWW-Authenticate' => [
					'Basic',
				],
			],
			$response->getHeaders(),
		);
	}

}
