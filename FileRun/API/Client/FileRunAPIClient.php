<?php
declare(strict_types = 1);

namespace FileRun\API\Client;

use Exception;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ServerException;
use Psr\Http\Message\ResponseInterface;

class FileRunAPIClient {

	private string $access_token;
	private string $refresh_token;
	private Client $http;
	public bool $debug = false;
	public bool $wasRefreshed = false;

	function __construct(
		private readonly string $url,
		private readonly string $client_id,
		private readonly string $client_secret,
		array $options = []
	) {
		foreach ($options as $option => $value) {
			if (property_exists($this, $option)) {
				$this->{$option} = $value;
			}
		}
		$this->http = new Client();
	}

	/**
	 * @throws FileRunAPIException
	 * @throws GuzzleException
	 */
	public function fetchAccessToken(string $username, string $password, array $scopes): void {
		try {
			$response = $this->http->post(
				$this->url.'/oauth2/token/',
				[
					'form_params' => [
						'client_id' => $this->client_id,
						'client_secret' => $this->client_secret,
						'username' => $username,
						'password' => $password,
						'redirect_uri' => 'http://localhost',
						'grant_type' => 'password',
						'scope' => implode(' ', $scopes)
					],
					'verify' => false
				]
			);
			$contents = $response->getBody()
				->getContents();
			$status = $response->getStatusCode();
		} catch (ClientException $e) {
			$contents = $e->getResponse()
				->getBody()
				->getContents();
			$status = $e->getResponse()
				->getStatusCode();
		}
		if ($contents === '') {
			throw new FileRunAPIException('Empty JSON server response with HTTP response code "'.$status.'"!');
		}
		$rs = self::jsonDecode($contents);

		if (isset($rs['error'])) {
			throw new FileRunAPIException('Server error: '.($rs['message'] ?? 'no error message provided'));
		}
		if (!$rs['access_token']) {
			throw new FileRunAPIException('Missing access token!');
		}
		$this->access_token = $rs['access_token'];
		$this->refresh_token = $rs['refresh_token'];
	}

	/**
	 * @throws FileRunAPIException
	 * @throws GuzzleException
	 */
	public function refreshAccessToken(): void {
		if (!$this->refresh_token) {
			throw new FileRunAPIException('Missing refresh token');
		}
		try {
			$response = $this->http->request('POST', $this->url.'/oauth2/token/',
				[
					'form_params' => [
						'client_id' => $this->client_id,
						'client_secret' => $this->client_secret,
						'grant_type' => 'refresh_token',
						'refresh_token' => $this->refresh_token
					],
					'verify' => false
				]);
			$contents = $response->getBody()
				->getContents();
			//$status = $response->getStatusCode();
		} catch (ClientException $e) {
			$contents = $e->getResponse()
				->getBody()
				->getContents();
			//$status = $e->getResponse()->getStatusCode();
		}
		$rs = self::jsonDecode($contents);
		if (isset($rs['error'])) {
			throw new FileRunAPIException('Server error: '.($rs['message'] ?? 'no error message provided'));
		}
		if (!isset($rs['access_token'])) {
			throw new FileRunAPIException('Missing access token!');
		}
		$this->access_token = $rs['access_token'];
		$this->refresh_token = $rs['refresh_token'];
		$this->wasRefreshed = true;
	}

	public function getAccessToken(): string {
		return $this->access_token;
	}

	public function getRefreshToken(): string {
		return $this->refresh_token;
	}

	/**
	 * @throws FileRunAPIException
	 * @throws GuzzleException
	 */
	public function callAPI(
		string $path,
		string $method = 'GET',
		array $opts = [],
		bool $returnResponse = false
	): array|ResponseInterface {
		try {
			$p = [
				'headers' => [
					'Authorization' => 'Bearer '.$this->access_token
				],
				'verify' => false
			];
			if (sizeof($opts) > 0) {
				$p = array_merge($p, $opts);
			}
			$response = $this->http->request($method, $this->url.'/api.php/'.$path, $p);
		} catch (ServerException|ClientException $e) {
			$response = $e->getResponse();
		}
		if ($returnResponse) {
			return $response;
		}
		$contents = $response->getBody()->getContents();
		$rs = self::jsonDecode($contents);
		if (!isset($rs['success'])) {
			$status = $response->getStatusCode();
			throw new FileRunAPIException('Server error: "'.($rs['error'] ?? 'No error message returned').'" with status code "'.$status.'"!');
		}
		return $rs;
	}

	/**
	 * @throws FileRunAPIException
	 */
	public static function jsonDecode(string $contents) {
		if ($contents === '') {
			throw new FileRunAPIException('Empty server response!');
		}
		try {
			$contents = json_decode($contents, true, 10, JSON_THROW_ON_ERROR);
			
		} catch (Exception $e) {
			throw new FileRunAPIExceptionBadJSON(
				'Unable to decode the server JSON response!',
				0,
				new Exception('Problem with JSON content: "'.htmlentities($contents).'"!', $e->getCode(), $e)
			);
		}
		if (empty($contents)) {
			throw new FileRunAPIException('Empty JSON server response!');
		}
		return $contents;
	}

	public function setAccessToken(string $accessToken): void {
		$this->access_token = $accessToken;
	}

}