<?php declare(strict_types = 1);

namespace OriNette\Http\Tracy;

use Nette\Http\IRequest;
use Nette\Http\IResponse;
use Tracy\Helpers;
use Tracy\IBarPanel;

final class HttpPanel implements IBarPanel
{

	private IRequest $request;

	private IResponse $response;

	public function __construct(IRequest $request, IResponse $response)
	{
		$this->request = $request;
		$this->response = $response;
	}

	public function getTab(): string
	{
		return Helpers::capture(function (): void {
			// phpcs:disable SlevomatCodingStandard.Variables.UnusedVariable
			$response = $this->response;
			// phpcs:enable SlevomatCodingStandard.Variables.UnusedVariable

			require __DIR__ . '/Http.tab.phtml';
		});
	}

	public function getPanel(): string
	{
		return Helpers::capture(function (): void {
			// phpcs:disable SlevomatCodingStandard.Variables.UnusedVariable
			$request = $this->request;
			$response = $this->response;
			// phpcs:enable SlevomatCodingStandard.Variables.UnusedVariable

			require __DIR__ . '/Http.panel.phtml';
		});
	}

}
