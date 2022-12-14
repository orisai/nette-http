<?php declare(strict_types = 1);

namespace OriNette\Http\Auth\DI;

use Nette\DI\CompilerExtension;
use Nette\Http\IRequest;
use Nette\Http\IResponse;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use OriNette\Http\Auth\HttpAuthenticator;
use stdClass;

/**
 * @property-read stdClass $config
 */
final class HttpAuthExtension extends CompilerExtension
{

	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'enabled' => Expect::bool(false),
			'realm' => Expect::anyOf(Expect::string(), Expect::null())->default(false),
			'errorResponses' => Expect::anyOf(
				Expect::listOf(Expect::string())->min(1),
				Expect::null(),
			),
			'users' => Expect::arrayOf(Expect::string(), Expect::string()),
			'exclude' => Expect::structure([
				'paths' => Expect::listOf(Expect::string()),
			]),
		]);
	}

	public function loadConfiguration(): void
	{
		parent::loadConfiguration();

		$builder = $this->getContainerBuilder();
		$config = $this->config;

		if (!$config->enabled) {
			return;
		}

		$authenticatorDefinition = $builder->addDefinition($this->prefix('authenticator'))
			->setFactory(HttpAuthenticator::class);

		if ($config->realm !== false) {
			$authenticatorDefinition->addSetup('setRealm', [$config->realm]);
		}

		if ($config->errorResponses !== null) {
			$authenticatorDefinition->addSetup('setErrorResponses', [$config->errorResponses]);
		}

		foreach ($config->users as $user => $password) {
			$authenticatorDefinition->addSetup('addUser', [
				$user,
				$password,
			]);
		}

		foreach ($config->exclude->paths as $path) {
			$authenticatorDefinition->addSetup('addExcludedPath', [
				$path,
			]);
		}
	}

	public function beforeCompile(): void
	{
		parent::beforeCompile();

		$builder = $this->getContainerBuilder();
		$config = $this->config;

		if (!$config->enabled) {
			return;
		}

		$initialize = $this->getInitialization();
		$initialize->addBody('$this->getService(?)->authenticate($this->getService(?), $this->getService(?));', [
			$this->prefix('authenticator'),
			$builder->getByType(IRequest::class, true),
			$builder->getByType(IResponse::class, true),
		]);
	}

}
