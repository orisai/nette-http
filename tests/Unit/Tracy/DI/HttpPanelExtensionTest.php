<?php declare(strict_types = 1);

namespace Tests\OriNette\Http\Unit\Tracy\DI;

use Nette\Http\IResponse;
use OriNette\DI\Boot\ManualConfigurator;
use OriNette\Http\Tracy\HttpPanel;
use PHPUnit\Framework\TestCase;
use Tracy\Bar;
use function dirname;
use function mkdir;
use const PHP_VERSION_ID;

/**
 * @runTestsInSeparateProcesses
 */
final class HttpPanelExtensionTest extends TestCase
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

		$configurator->addConfig(__DIR__ . '/HttpPanelExtension.disabled.neon');

		$container = $configurator->createContainer();

		$bar = $container->getByType(Bar::class);
		self::assertNull($bar->getPanel('orisai.http.panel'));

		$container->getByType(IResponse::class);
		self::assertNull($bar->getPanel('orisai.http.panel'));
	}

	public function testEnabled(): void
	{
		$configurator = new ManualConfigurator($this->rootDir);
		$configurator->setForceReloadContainer();

		$configurator->addConfig(__DIR__ . '/HttpPanelExtension.enabled.neon');

		$container = $configurator->createContainer();

		$bar = $container->getByType(Bar::class);
		self::assertInstanceOf(HttpPanel::class, $bar->getPanel('orisai.http.panel'));
	}

	public function testEnabledNoInitialize(): void
	{
		$configurator = new ManualConfigurator($this->rootDir);
		$configurator->setForceReloadContainer();

		$configurator->addConfig(__DIR__ . '/HttpPanelExtension.enabled.neon');

		$container = $configurator->createContainer(false);

		$bar = $container->getByType(Bar::class);
		self::assertNull($bar->getPanel('orisai.http.panel'));

		$container->getByType(IResponse::class);
		self::assertInstanceOf(HttpPanel::class, $bar->getPanel('orisai.http.panel'));
	}

}
