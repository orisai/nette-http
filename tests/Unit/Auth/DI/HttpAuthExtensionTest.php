<?php declare(strict_types = 1);

namespace Tests\OriNette\Http\Unit\Auth\DI;

use Generator;
use Nette\Http\IRequest;
use Nette\Http\IResponse;
use Nette\Utils\Helpers;
use OriNette\DI\Boot\ManualConfigurator;
use OriNette\Http\Auth\HttpAuthenticator;
use PHPUnit\Framework\TestCase;
use function dirname;
use function mkdir;
use const PHP_VERSION_ID;

/**
 * @runTestsInSeparateProcesses
 */
final class HttpAuthExtensionTest extends TestCase
{

	private string $rootDir;

	protected function setUp(): void
	{
		parent::setUp();

		$_SERVER['SCRIPT_NAME'] = '/index.php';

		$this->rootDir = dirname(__DIR__, 4);
		if (PHP_VERSION_ID < 8_01_00) {
			@mkdir("$this->rootDir/var/build");
		}
	}

	public function testDisabled(): void
	{
		$configurator = new ManualConfigurator($this->rootDir);
		$configurator->setForceReloadContainer();

		$configurator->addConfig(__DIR__ . '/HttpAuthExtension.disabled.neon');

		$container = $configurator->createContainer();

		self::assertFalse($container->hasService('orisai.http.auth.authenticator'));
	}

	public function testEnabled(): void
	{
		$configurator = new ManualConfigurator($this->rootDir);
		$configurator->setForceReloadContainer();

		$configurator->addConfig(__DIR__ . '/HttpAuthExtension.enabled.neon');

		$container = $configurator->createContainer(false);

		$authenticator = $container->getService('orisai.http.auth.authenticator');
		self::assertInstanceOf(HttpAuthenticator::class, $authenticator);
		self::assertSame($authenticator, $container->getByType(HttpAuthenticator::class));

		$request = $container->getByType(IRequest::class);
		$response = $container->getByType(IResponse::class);

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

	public function testCustomTexts(): void
	{
		$configurator = new ManualConfigurator($this->rootDir);
		$configurator->setForceReloadContainer();

		$configurator->addConfig(__DIR__ . '/HttpAuthExtension.enabled.neon');
		$configurator->addConfig(__DIR__ . '/HttpAuthExtension.customTexts.neon');

		$container = $configurator->createContainer(false);

		$authenticator = $container->getService('orisai.http.auth.authenticator');
		self::assertInstanceOf(HttpAuthenticator::class, $authenticator);
		self::assertSame($authenticator, $container->getByType(HttpAuthenticator::class));

		$request = $container->getByType(IRequest::class);
		$response = $container->getByType(IResponse::class);

		$echoed = Helpers::capture(static fn () => $authenticator->authenticate($request, $response));

		self::assertSame(
			[
				'WWW-Authenticate' => [
					'Basic realm="realm"',
				],
			],
			$response->getHeaders(),
		);
		self::assertContains($echoed, ['a', 'b', 'c']);
	}

	public function testExcludedPath(): void
	{
		$configurator = new ManualConfigurator($this->rootDir);
		$configurator->setForceReloadContainer();

		$configurator->addConfig(__DIR__ . '/HttpAuthExtension.enabled.neon');
		$configurator->addConfig(__DIR__ . '/HttpAuthExtension.excludePath.neon');

		$_SERVER['REQUEST_URI'] = 'https://example.com/api';

		$container = $configurator->createContainer();

		$authenticator = $container->getByType(HttpAuthenticator::class);
		self::assertInstanceOf(HttpAuthenticator::class, $authenticator);
	}

	public function testExcludedPathWithScriptPath(): void
	{
		$configurator = new ManualConfigurator($this->rootDir);
		$configurator->setForceReloadContainer();

		$configurator->addConfig(__DIR__ . '/HttpAuthExtension.enabled.neon');
		$configurator->addConfig(__DIR__ . '/HttpAuthExtension.excludePath.neon');

		$_SERVER['SCRIPT_NAME'] = '/script/index.php';
		$_SERVER['REQUEST_URI'] = 'https://example.com/script/api';

		$container = $configurator->createContainer();

		$authenticator = $container->getByType(HttpAuthenticator::class);
		self::assertInstanceOf(HttpAuthenticator::class, $authenticator);
	}

	/**
	 * @dataProvider provideVerified
	 */
	public function testVerified(string $user, string $password): void
	{
		$configurator = new ManualConfigurator($this->rootDir);
		$configurator->setForceReloadContainer();

		$configurator->addConfig(__DIR__ . '/HttpAuthExtension.enabled.neon');

		$_SERVER['PHP_AUTH_USER'] = $user;
		$_SERVER['PHP_AUTH_PW'] = $password;

		$container = $configurator->createContainer();

		$authenticator = $container->getByType(HttpAuthenticator::class);
		self::assertInstanceOf(HttpAuthenticator::class, $authenticator);
	}

	public function provideVerified(): Generator
	{
		yield ['user1', 'password'];
		yield ['user2', 'password'];
	}

	/**
	 * @dataProvider provideNotVerified
	 */
	public function testNotVerified(?string $user, ?string $password): void
	{
		$configurator = new ManualConfigurator($this->rootDir);
		$configurator->setForceReloadContainer();

		$configurator->addConfig(__DIR__ . '/HttpAuthExtension.enabled.neon');

		if ($user !== null) {
			$_SERVER['PHP_AUTH_USER'] = $user;
		}

		if ($password !== null) {
			$_SERVER['PHP_AUTH_PW'] = $password;
		}

		$echoed = Helpers::capture(static fn () => $configurator->createContainer());

		self::assertContains($echoed, HttpAuthenticator::DefaultErrorResponses);
	}

	public function provideNotVerified(): Generator
	{
		yield ['user1', 'bad'];
		yield ['user1', null];
		yield ['bad', 'password'];
		yield [null, 'password'];
		yield [null, null];
	}

}
