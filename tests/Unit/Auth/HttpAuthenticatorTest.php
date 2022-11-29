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

	/**
	 * @dataProvider provideAllowed
	 */
	public function testAllowed(string $user, string $password, string $passwordHash): void
	{
		$request = new Request(new UrlScript("https://$user:$password@example.com"));
		$response = new TestResponse();

		$authenticator = new HttpAuthenticator();
		$authenticator->addUser($user, $passwordHash);

		$authenticator->authenticate($request, $response);
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

		$authenticator = new HttpAuthenticator();
		$authenticator->setTestMode();
		$authenticator->addUser($userExpected, $passwordHash);

		$echoed = Helpers::capture(static fn () => $authenticator->authenticate($request, $response));

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
		$request = new Request(new UrlScript("https://example.com/$currentPath"));
		$response = new TestResponse();

		$authenticator = new HttpAuthenticator();
		$authenticator->addExcludedPath('a');
		$authenticator->addExcludedPath('b/');
		$authenticator->addExcludedPath('/c');
		$authenticator->addExcludedPath('/d/');
		$authenticator->addExcludedPath('e///foo');

		$authenticator->authenticate($request, $response);
		self::assertTrue(true);
	}

	public function provideExcludedPaths(): Generator
	{
		yield ['a'];
		yield ['a/'];
		yield ['b'];
		yield ['b/'];
		yield ['c'];
		yield ['c/'];
		yield ['d'];
		yield ['d/'];
		yield ['d/foo'];
		yield ['d//foo'];
		yield ['d/foo/'];
		yield ['d/foo/bar'];
		yield ['d/foo//bar'];
		yield ['e/foo'];
	}

	public function testTracy(): void
	{
		$authenticator = new HttpAuthenticator();
		$authenticator->setTestMode();
		$authenticator->addUser('user', 'password');

		Debugger::$productionMode = false;

		$request = new Request(new UrlScript('https://user:password@example.com'));
		$response = new TestResponse();
		$authenticator->authenticate($request, $response);
		self::assertFalse(Debugger::$productionMode);

		$request = new Request(new UrlScript('https://example.com'));
		$response = new TestResponse();
		Helpers::capture(static fn () => $authenticator->authenticate($request, $response));
		self::assertTrue(Debugger::$productionMode);
	}

	public function testCustomMessages(): void
	{
		$title = 'title';
		$errorResponses = ['a', 'b', 'c'];

		$authenticator = new HttpAuthenticator($title, $errorResponses);
		$authenticator->setTestMode();

		$request = new Request(new UrlScript('https://example.com'));
		$response = new TestResponse();

		$echoed = Helpers::capture(static fn () => $authenticator->authenticate($request, $response));

		self::assertSame(
			[
				'WWW-Authenticate' => [
					'Basic realm="title"',
				],
			],
			$response->getHeaders(),
		);
		self::assertContains($echoed, $errorResponses);
	}

}
