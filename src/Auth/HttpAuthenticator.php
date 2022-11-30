<?php declare(strict_types = 1);

namespace OriNette\Http\Auth;

use Nette\Http\IRequest;
use Nette\Http\IResponse;
use Orisai\Utils\Dependencies\Dependencies;
use Tracy\Debugger;
use function array_rand;
use function assert;
use function hash_equals;
use function implode;
use function method_exists;
use function password_verify;
use function preg_match;
use function preg_split;
use function str_starts_with;
use const PREG_SPLIT_NO_EMPTY;

final class HttpAuthenticator
{

	public const DefaultTitle = 'Speak friend and enter.';

	public const DefaultErrorResponses = [
		'You shall not pass!',
		'You have no authority here! Your orders mean nothing!',
		'No, thank you! We don’t want any more visitors, well-wishers or distant relations!',
	];

	private ?string $realm = self::DefaultTitle;

	/** @var non-empty-list<string> */
	private array $errorResponses = self::DefaultErrorResponses;

	/** @var array<string, string> */
	private array $users = [];

	/** @var array<string> */
	private array $excludedPaths = [];

	private bool $testMode = false;

	public function setRealm(?string $realm): void
	{
		$this->realm = $realm;
	}

	/**
	 * @param non-empty-list<string> $responses
	 */
	public function setErrorResponses(array $responses): void
	{
		$this->errorResponses = $responses;
	}

	public function addUser(string $user, string $password): void
	{
		$this->users[$user] = $password;
	}

	public function addExcludedPath(string $path): void
	{
		$this->excludedPaths[] = $path;
	}

	/**
	 * @internal
	 */
	public function setTestMode(): void
	{
		$this->testMode = true;
	}

	public function authenticate(IRequest $request, IResponse $response): void
	{
		if ($this->isPathExcluded($request)) {
			return;
		}

		if ($this->isUserAllowed($request)) {
			return;
		}

		if (Dependencies::isPackageLoaded('tracy/tracy')) {
			Debugger::$productionMode = true;
		}

		$this->forbidden($response);
	}

	private function isPathExcluded(IRequest $request): bool
	{
		$url = $request->getUrl();
		$relativePath = $this->normalizePath(
			//substr($url->getPath(), strlen($url->getBasePath())),
			$url->getPath(),
		);

		foreach ($this->excludedPaths as $excludedPath) {
			if (str_starts_with($relativePath, $this->normalizePath($excludedPath))) {
				return true;
			}
		}

		return false;
	}

	private function isUserAllowed(IRequest $request): bool
	{
		if (method_exists($request, 'getBasicCredentials')) {
			[$user, $password] = $request->getBasicCredentials();
		} else {
			$url = $request->getUrl();
			$user = $url->getUser();
			$password = $url->getPassword();
		}

		$expectedPassword = $this->users[$user] ?? null;
		if ($expectedPassword === null) {
			return false;
		}

		return $this->verifyPassword($password, $expectedPassword);
	}

	private function verifyPassword(string $password, string $expectedPassword): bool
	{
		if (preg_match('~\$.{50,}~A', $expectedPassword) === 1) {
			return password_verify($password, $expectedPassword);
		}

		return hash_equals($expectedPassword, $password);
	}

	private function forbidden(IResponse $response): void
	{
		$response->setCode(IResponse::S401_UNAUTHORIZED);
		$response->setHeader(
			'WWW-Authenticate',
			'Basic' . ($this->realm !== null ? " realm=\"$this->realm\"" : ''),
		);

		echo $this->errorResponses[array_rand($this->errorResponses)];

		if (!$this->testMode) {
			exit;
		}
	}

	private function normalizePath(string $path): string
	{
		// Get rid of leading, trailing and duplicate slashes, /example//path/ -> example/path
		$split = preg_split('#/#', $path, -1, PREG_SPLIT_NO_EMPTY);
		assert($split !== false);

		return implode('/', $split);
	}

}
