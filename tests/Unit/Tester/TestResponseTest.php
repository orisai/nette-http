<?php declare(strict_types = 1);

namespace Tests\OriNette\Http\Unit\Tester;

use DateTimeImmutable;
use DateTimeZone;
use OriNette\Http\Tester\TestResponse;
use PHPUnit\Framework\TestCase;
use function array_keys;

final class TestResponseTest extends TestCase
{

	public function testIsSent(): void
	{
		$response = new TestResponse();
		self::assertFalse($response->isSent());
	}

	public function testCodes(): void
	{
		$response = new TestResponse();
		self::assertSame(200, $response->getCode());

		$response->setCode(300);
		self::assertSame(300, $response->getCode());
	}

	public function testHeaders(): void
	{
		$response = new TestResponse();

		self::assertNull($response->getHeader('foo'));
		self::assertSame([], $response->getHeaders());

		$response->setHeader('foo', '1');
		self::assertSame('foo: 1', $response->getHeader('foo'));

		$response->setHeader('foo', '2');
		self::assertSame('foo: 2', $response->getHeader('foo'));

		$response->addHeader('foo', '3');
		$response->addHeader('bar', '1');
		self::assertSame('foo: 2,3', $response->getHeader('foo'));

		self::assertSame(
			[
				'foo' => ['2', '3'],
				'bar' => ['1'],
			],
			$response->getHeaders(),
		);

		$response->deleteHeader('foo');
		self::assertSame(
			[
				'bar' => ['1'],
			],
			$response->getHeaders(),
		);
	}

	public function testCookies(): void
	{
		$response = new TestResponse();
		self::assertSame([], $response->getCookies());

		$response->setCookie('a', '1', 100);
		$response->setCookie('b', '1', 100);
		$response->setCookie('b', '2', 200, 'path', 'domain', true, true);
		$response->setCookie('c', '1', 100);
		$response->deleteCookie('c');
		$response->deleteCookie('d', 'path', 'domain', true);
		self::assertSame(
			[
				'a' => [
					'value' => '1',
					'expire' => 100,
					'path' => null,
					'domain' => null,
					'secure' => null,
					'httpOnly' => null,
				],
				'b' => [
					'value' => '2',
					'expire' => 200,
					'path' => 'path',
					'domain' => 'domain',
					'secure' => true,
					'httpOnly' => true,
				],
				'c' => [
					'value' => '',
					'expire' => 0,
					'path' => null,
					'domain' => null,
					'secure' => null,
					'httpOnly' => null,
				],
				'd' => [
					'value' => '',
					'expire' => 0,
					'path' => 'path',
					'domain' => 'domain',
					'secure' => true,
					'httpOnly' => null,
				],
			],
			$response->getCookies(),
		);
	}

	public function testRedirect(): void
	{
		$response = new TestResponse();
		self::assertSame(200, $response->getCode());
		self::assertNull($response->getHeader('Location'));

		$response->redirect('https://example.com', 303);
		self::assertSame(303, $response->getCode());
		self::assertSame('Location: https://example.com', $response->getHeader('Location'));

		$response->redirect('https://example2.com', 301);
		self::assertSame(301, $response->getCode());
		self::assertSame('Location: https://example2.com', $response->getHeader('Location'));
	}

	public function testContentType(): void
	{
		$response = new TestResponse();
		self::assertNull($response->getHeader('Content-Type'));

		$response->setContentType('application/json', 'utf-8');
		self::assertSame('Content-Type: application/json; charset=utf-8', $response->getHeader('Content-Type'));

		$response->setContentType('application/xml');
		self::assertSame('Content-Type: application/xml', $response->getHeader('Content-Type'));
	}

	public function testExpiration(): void
	{
		$response = new TestResponse();
		self::assertSame([], $response->getHeaders());

		$response->setExpiration(null);
		self::assertSame(
			[
				'Cache-Control' => [
					's-maxage=0, max-age=0, must-revalidate',
				],
				'Expires' => [
					'Mon, 23 Jan 1978 10:00:00 GMT',
				],
			],
			$response->getHeaders(),
		);

		$datetime = new DateTimeImmutable('2038-01-19 03:14:08', new DateTimeZone('UTC'));
		$response->setExpiration($datetime->format($datetime::ATOM));
		self::assertSame(['Cache-Control', 'Expires'], array_keys($response->getHeaders()));
		self::assertStringStartsWith('Cache-Control: max-age=', $response->getHeader('Cache-Control'));
		self::assertSame('Expires: Tue, 19 Jan 2038 03:14:08 GMT', $response->getHeader('Expires'));
	}

}
