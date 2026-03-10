<?php
declare(strict_types = 1);

namespace FileRun\API\Client\Actions;

use FileRun\API\Client\FileRunAPIException;
use FileRun\API\Client\FileRunAPIClient;

use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;

class Account {

	/**
	 * @throws GuzzleException
	 * @throws FileRunAPIException
	 */
	static function getOwnInfo(FileRunAPIClient $client): array {
		return $client->callAPI('Core/account/info');
	}

	/**
	 * Assuming a status code of 200, use the returned ResponseInterface to fetch the image data: ResponseInterface::getBody()->getContents()
	 * @returns ResponseInterface
	 * @throws GuzzleException
	 * @throws FileRunAPIException
	 */
	static function getAvatar(FileRunAPIClient $client): ResponseInterface {
		return $client->callAPI(
			'Core/account/avatar',
			returnResponse: true
		);
	}

	/**
	 * @throws GuzzleException
	 * @throws FileRunAPIException
	 */
	public function changePassword(
		FileRunAPIClient $client,
		string $existingPassword,
		string $newPassword,
		?string $twoStepOTP
	): array|ResponseInterface {
		$opts = [
			'form_params' => [
				'current_password' => $existingPassword,
				'new_password' => $newPassword,
			]
		];
		if ($twoStepOTP) {
			$opts['form_params']['twoStepOTP'] = $twoStepOTP;
		}
		return $client->callAPI('/account/password', 'POST', $opts);
	}

}