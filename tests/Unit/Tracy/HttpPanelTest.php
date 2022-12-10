<?php declare(strict_types = 1);

namespace Tests\OriNette\Http\Unit\Tracy;

use Nette\Http\Request;
use Nette\Http\UrlScript;
use OriNette\Http\Tester\TestResponse;
use OriNette\Http\Tracy\HttpPanel;
use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
final class HttpPanelTest extends TestCase
{

	public function test(): void
	{
		$request = new Request(new UrlScript('https://example.com'));
		$response = new TestResponse();

		$panel = new HttpPanel($request, $response);

		self::assertNotEmpty($panel->getTab());
		self::assertNotEmpty($panel->getPanel());
	}

}
