<?php declare(strict_types = 1);

namespace Tests\OriNette\Http\Unit\Auth\DI;

use Generator;
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

		$response = Helpers::capture(static fn () => $configurator->createContainer());

		// TODO - test headers with test response

		self::assertContains($response, HttpAuthenticator::DefaultErrorResponses);
	}

	public function testCustomTexts(): void
	{
		$configurator = new ManualConfigurator($this->rootDir);
		$configurator->setForceReloadContainer();

		$configurator->addConfig(__DIR__ . '/HttpAuthExtension.enabled.neon');
		$configurator->addConfig(__DIR__ . '/HttpAuthExtension.customTexts.neon');

		$response = Helpers::capture(static fn () => $configurator->createContainer());

		// TODO - test headers with test response

		self::assertContains($response, ['a', 'b', 'c']);
	}

	public function testExcludedPath(): void
	{
		$configurator = new ManualConfigurator($this->rootDir);
		$configurator->setForceReloadContainer();

		$configurator->addConfig(__DIR__ . '/HttpAuthExtension.enabled.neon');
		$configurator->addConfig(__DIR__ . '/HttpAuthExtension.excludePath.neon');

		$_SERVER['REQUEST_URI'] = 'https://example.com/api';

		$container = $configurator->createContainer();

		$authenticator = $container->getService('orisai.http.auth.authenticator');
		self::assertInstanceOf(HttpAuthenticator::class, $authenticator);
		self::assertSame($authenticator, $container->getByType(HttpAuthenticator::class));
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

		$authenticator = $container->getService('orisai.http.auth.authenticator');
		self::assertInstanceOf(HttpAuthenticator::class, $authenticator);
		self::assertSame($authenticator, $container->getByType(HttpAuthenticator::class));
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

		$response = Helpers::capture(static fn () => $configurator->createContainer());

		// TODO - test headers with test response

		self::assertContains($response, HttpAuthenticator::DefaultErrorResponses);
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
