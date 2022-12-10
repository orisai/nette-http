<?php declare(strict_types = 1);

namespace OriNette\Http\Tracy\DI;

use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\ServiceDefinition;
use Nette\Http\IRequest;
use Nette\Http\IResponse;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use OriNette\Http\Tracy\HttpPanel;
use stdClass;
use Tracy\Bar;
use function assert;

/**
 * @property-read stdClass $config
 */
final class HttpPanelExtension extends CompilerExtension
{

	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'enabled' => Expect::bool(false),
		]);
	}

	public function beforeCompile(): void
	{
		parent::beforeCompile();

		$builder = $this->getContainerBuilder();
		$config = $this->config;

		if (!$config->enabled) {
			return;
		}

		$requestDefinition = $builder->getDefinitionByType(IRequest::class);
		$responseDefinition = $builder->getDefinitionByType(IResponse::class);
		assert($responseDefinition instanceof ServiceDefinition);

		$responseDefinition->addSetup(
			[self::class, 'setupPanel'],
			[
				$this->name,
				$builder->getDefinitionByType(Bar::class),
				$requestDefinition,
				$responseDefinition,
			],
		);
	}

	public static function setupPanel(
		string $name,
		Bar $bar,
		IRequest $request,
		IResponse $response
	): void
	{
		$bar->addPanel(
			new HttpPanel($request, $response),
			$name,
		);
	}

}
