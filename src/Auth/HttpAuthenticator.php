<?php declare(strict_types = 1);

namespace OriNette\Http\Auth;

use Nette\Http\IRequest;
use Nette\Http\IResponse;
use Tracy\Debugger;
use function array_rand;
use function assert;
use function class_exists;
use function hash_equals;
use function implode;
use function password_verify;
use function preg_match;
use function preg_split;
use function str_starts_with;
use function strlen;
use function substr;
use const PREG_SPLIT_NO_EMPTY;

final class HttpAuthenticator
{

	private const DefaultTitle = 'Speak friend and enter.';

	private const DefaultErrorResponses = [
		'You shall not pass!',
		'You have no authority here! Your orders mean nothing!',
		'No, thank you! We don’t want any more visitors, well-wishers or distant relations!',
	];

	private ?string $title;

	private ?string $errorResponse;

	/** @var array<string, string> */
	private array $users = [];

	/** @var array<string> */
	private array $excludedPaths = [];

	public function __construct(?string $title = null, ?string $errorResponse = null)
	{
		$this->title = $title;
		$this->errorResponse = $errorResponse;
	}

	public function addUser(string $user, string $password): void
	{
		$this->users[$user] = $password;
	}

	public function addExcludedPath(string $path): void
	{
		$this->excludedPaths[] = $path;
	}

	public function authenticate(IRequest $request, IResponse $response): void
	{
		if ($this->isPathExcluded($request)) {
			return;
		}

		$url = $request->getUrl();
		if ($this->isUserAllowed($url->getUser(), $url->getPassword())) {
			return;
		}

		if (class_exists(Debugger::class)) {
			Debugger::$productionMode = true;
		}

		$this->forbidden($response);
	}

	private function isPathExcluded(IRequest $request): bool
	{
		$url = $request->getUrl();
		$relativePath = $this->normalizePath(
			substr($url->getPath(), strlen($url->getBasePath())),
		);

		foreach ($this->excludedPaths as $excludedPath) {
			if (str_starts_with($relativePath, $this->normalizePath($excludedPath))) {
				return true;
			}
		}

		return false;
	}

	private function isUserAllowed(string $user, string $password): bool
	{
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

	/**
	 * @return never
	 */
	private function forbidden(IResponse $response): void
	{
		$title = $this->title ?? self::DefaultTitle;
		$response->setCode(IResponse::S401_UNAUTHORIZED);
		$response->setHeader('WWW-Authenticate', "Basic realm=\"$title\"");

		echo $this->errorResponse ?? self::DefaultErrorResponses[array_rand(self::DefaultErrorResponses)];

		exit;
	}

	private function normalizePath(string $path): string
	{
		// Get rid of leading, trailing and duplicate slashes, /example//path/ -> example/path
		$split = preg_split('#/#', $path, -1, PREG_SPLIT_NO_EMPTY);
		assert($split !== false);

		return implode('/', $split);
	}

}
