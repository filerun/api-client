<?php
namespace FileRun\API;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\RequestException;

class Client {
	private $url;
	private $redirect_uri;
	private $client_id;
	private $client_secret;
	private $username;
	private $password;
	private $error;
	private $scope;//OAuth2 scope
	private $access_token;//the OAuth2 access token
	private $http;//the Guzzle HTTP Client
	public $debug = false;

	public function __construct(array $options = [], array $collaborators = []) {
		foreach ($options as $option => $value) {
			if (property_exists($this, $option)) {
				$this->{$option} = $value;
			}
		}
		$this->http = new HttpClient();
	}
	public function connect() {
		try {
			$response = $this->http->request('POST', $this->url.'/oauth2/token/',
				array(
					'form_params' =>
						array(
							'client_id' => $this->client_id,
							'client_secret' => $this->client_secret,
							'username' => $this->username,
							'password' => $this->password,
							'redirect_uri' => $this->redirect_uri,
							'grant_type' => 'password',
							'scope' => implode(' ', $this->scope)
						)
				));
		} catch (RequestException $e) {
			$this->error =  $e->getMessage();
			return false;
		}

		if ($response) {
			$rs = json_decode($response->getBody()->getContents());
			if (is_object($rs) && $rs->access_token) {
				$this->access_token = $rs->access_token;
				return true;
			}
		}
	}
	private function callAPI($path, $method = 'GET', $opts = [], $raw = false) {
		try {
			$p = [
				'headers' => [
					'Authorization' => 'Bearer '.$this->access_token
				]
			];
			if (sizeof($opts) > 0) {
				$p = array_merge($p, $opts);
			}
			$response = $this->http->request($method, $this->url.'/api.php'.$path, $p);

		} catch (RequestException $e) {
			$this->error =  $e->getMessage();
			return false;
		}

		if ($response) {
			$contents = $response->getBody()->getContents();
			if ($raw) {
				return $contents;
			}
			$decoded = json_decode($contents, true);
			if (is_null($decoded)) {
				if ($this->debug) {
					echo $contents;
				}
				return false;
			}
			if (array_key_exists('success', $decoded) && !$decoded['success']) {
				$this->error = $decoded['error'];
			}
			return $decoded;
		}
	}
	public function getError() {
		return $this->error;
	}
	public function getUserInfo() {
		return $this->callAPI('/account/info', 'GET');
	}
	public function getFileList($params) {
		$opts = ['form_params' => $params];
		return $this->callAPI('/files/browse/', 'POST', $opts);
	}
	public function searchFiles($params) {
		$opts = ['form_params' => $params];
		return $this->callAPI('/files/search/', 'POST', $opts);
	}
	public function createFolder($params) {
		$opts = ['form_params' => $params];
		return $this->callAPI('/files/createfolder/', 'POST', $opts);
	}
	public function uploadFile($params, $fileSource) {
		$opts = [
			'multipart' => [
				[
					'name' => 'file',
					'filename' => 'filename.txt',
					'contents' => $fileSource
				]
			]
		];
		foreach($params as $k => $v) {
			$opts['multipart'][] = ['name' => $k,  'contents' => $v];
		}
		return $this->callAPI('/files/upload/', 'POST', $opts);
	}
	public function downloadFile($params) {
		$opts = ['form_params' => $params];
		return $this->callAPI('/files/download/', 'POST', $opts, true);
	}
	public function getWebLink($params) {
		$opts = ['form_params' => $params];
		return $this->callAPI('/files/weblink/', 'POST', $opts);
	}
	public function deleteFile($params) {
		$opts = ['form_params' => $params];
		return $this->callAPI('/files/delete/', 'POST', $opts);
	}
	public function shareFolder($params) {
		$opts = ['form_params' => $params];
		return $this->callAPI('/files/share/', 'POST', $opts);
	}
	public function unShareFolder($params) {
		$opts = ['form_params' => $params];
		return $this->callAPI('/files/unshare/', 'POST', $opts);
	}
}