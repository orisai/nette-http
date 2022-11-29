<?php declare(strict_types = 1);

namespace OriNette\Http\Tester;

use DateTimeImmutable;
use DateTimeInterface;
use Nette\Http\Helpers;
use Nette\Http\IResponse;
use function implode;
use function time;

/**
 * @phpstan-type CookieValue array{
 *        value: string,
 *        expire: string|int|DateTimeInterface|null,
 *        path: string|null,
 *        domain: string|null,
 *        secure: bool|null,
 *        httpOnly: bool|null
 * }
 */
final class TestResponse implements IResponse
{

	private int $code = self::S200_OK;

	/** @var array<string, array<string>> */
	private array $headers = [];

	/**
	 * @var array<string, array<mixed>>
	 * @phpstan-var array<string, CookieValue>
	 */
	private array $cookies = [];

	public function setCode(int $code, ?string $reason = null): self
	{
		$this->code = $code;

		return $this;
	}

	public function getCode(): int
	{
		return $this->code;
	}

	public function setHeader(string $name, ?string $value): self
	{
		if ($value === null) {
			$this->deleteHeader($name);
		} else {
			$this->headers[$name] = [$value];
		}

		return $this;
	}

	public function addHeader(string $name, string $value): self
	{
		$this->headers[$name][] = $value;

		return $this;
	}

	public function getHeader(string $header): ?string
	{
		if (!isset($this->headers[$header])) {
			return null;
		}

		return $header . ': ' . implode(',', $this->headers[$header]);
	}

	/**
	 * @return array<string, array<string>>
	 */
	public function getHeaders(): array
	{
		return $this->headers;
	}

	public function deleteHeader(string $name): self
	{
		unset($this->headers[$name]);

		return $this;
	}

	public function setContentType(string $type, ?string $charset = null): self
	{
		$this->setHeader(
			'Content-Type',
			$type . ($charset !== null ? '; charset=' . $charset : ''),
		);

		return $this;
	}

	public function redirect(string $url, int $code = self::S302_FOUND): void
	{
		$this->setCode($code);
		$this->setHeader('Location', $url);
	}

	public function setExpiration(?string $expire): self
	{
		$this->setHeader('Pragma', null);
		if ($expire === null) { // no cache
			$this->setHeader('Cache-Control', 's-maxage=0, max-age=0, must-revalidate');
			$this->setHeader('Expires', 'Mon, 23 Jan 1978 10:00:00 GMT');

			return $this;
		}

		$time = new DateTimeImmutable($expire);
		$this->setHeader('Cache-Control', 'max-age=' . ($time->format('U') - time()));
		$this->setHeader('Expires', Helpers::formatDate($time));

		return $this;
	}

	public function isSent(): bool
	{
		return false;
	}

	/**
	 * @param string|int|DateTimeInterface|null $expire
	 */
	public function setCookie(
		string $name,
		string $value,
		$expire,
		?string $path = null,
		?string $domain = null,
		?bool $secure = null,
		?bool $httpOnly = null
	): self
	{
		$this->cookies[$name] = [
			'value' => $value,
			'expire' => $expire,
			'path' => $path,
			'domain' => $domain,
			'secure' => $secure,
			'httpOnly' => $httpOnly,
		];

		return $this;
	}

	/**
	 * @return array<string, array<mixed>>
	 * @phpstan-return array<string, CookieValue>
	 */
	public function getCookies(): array
	{
		return $this->cookies;
	}

	public function deleteCookie(
		string $name,
		?string $path = null,
		?string $domain = null,
		?bool $secure = null
	): void
	{
		$this->setCookie($name, '', 0, $path, $domain, $secure);
	}

}
