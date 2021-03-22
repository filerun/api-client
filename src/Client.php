<?php
namespace FileRun\API;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\RequestException;

class Client {
	private $url;
	private $redirect_uri = 'http://localhost';
	private $client_id;
	private $client_secret;
	private $username;
	private $password;
	private $error;
	private $scope;//OAuth2 scope
	private $access_token;//the OAuth2 access token
	private $refresh_token;//the OAuth2 refresh token
	private $http;//the Guzzle HTTP Client
	public $debug = false;

	public function __construct(array $options = []) {
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
				[
					'form_params' =>
						[
							'client_id' => $this->client_id,
							'client_secret' => $this->client_secret,
							'username' => $this->username,
							'password' => $this->password,
							'redirect_uri' => $this->redirect_uri,
							'grant_type' => 'password',
							'scope' => implode(' ', $this->scope)
						],
					'verify' => false
				]);
		} catch (RequestException $e) {
			$this->error =  $e->getMessage();
			return false;
		}
		if (!$response) {
			$this->error = 'Unexpected empty server response';
			return false;
		}
		$responseBody = $response->getBody()->getContents();
		$rs = json_decode($responseBody);
		if (!is_object($rs)) {
			$this->error = 'Unexpected server response: '.$responseBody;
			return false;
		}
		if (isset($rs->error)) {
			$this->error = 'Server error: '.$rs->message;
			return false;
		}
		if (!$rs->access_token) {
			$this->error = 'Missing access token';
			return false;
		}
		$this->access_token = $rs->access_token;
		$this->refresh_token = $rs->refresh_token;
		return true;
	}
	public function refreshAccessToken() {
		if (!$this->refresh_token) {
			$this->error = 'Missing refresh token';
			return false;
		}
		try {
			$response = $this->http->request('POST', $this->url.'/oauth2/token/',
				[
					'form_params' =>
						[
							'client_id' => $this->client_id,
							'client_secret' => $this->client_secret,
							'grant_type' => 'refresh_token',
							'refresh_token' => $this->refresh_token
						],
					'verify' => false
				]);
		} catch (RequestException $e) {
			$this->error =  $e->getMessage();
			return false;
		}
		if (!$response) {
			$this->error = 'Unexpected empty server response';
			return false;
		}
		$responseBody = $response->getBody()->getContents();
		$rs = json_decode($responseBody);
		if (!is_object($rs)) {
			$this->error = 'Unexpected server response: '.$responseBody;
			return false;
		}
		if (isset($rs->error)) {
			$this->error = 'Server error: '.$rs->message;
			return false;
		}
		if (!$rs->access_token) {
			$this->error = 'Missing access token';
			return false;
		}
		$this->access_token = $rs->access_token;
		$this->refresh_token = $rs->refresh_token;
		return true;
	}
	public function getAccessToken() {
		return $this->access_token;
	}
	public function setAccessToken($token) {
		$this->access_token = $token;
		return true;
	}
	public function getRefreshToken() {
		return $this->refresh_token;
	}
	public function setRefreshToken($token) {
		$this->refresh_token = $token;
		return true;
	}
	private function callAPI($path, $method = 'GET', $opts = [], $raw = false) {
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
			$response = $this->http->request($method, $this->url.'/api.php'.$path, $p);

		} catch (RequestException $e) {
			$this->error = $e->getResponse()->getBody()->getContents();
			return false;
		}
		if (!$response) {
			$this->error = 'Empty server response';
			return false;
		}
		$contents = $response->getBody()->getContents();
		if ($raw) {
			return $contents;
		}
		$decoded = json_decode($contents, true);
		if (is_null($decoded)) {
			if ($this->debug) {
				echo $contents;
			}
			$this->error = 'Failed to decode JSON server response.';
			return false;
		}
		if (array_key_exists('success', $decoded) && !$decoded['success']) {
			$this->error = $decoded['error'];
		}
		return $decoded;

	}
	public function getError() {
		return $this->error;
	}
	public function getUserInfo($uid = false, $username = false) {
		if ($uid || $username) {
			if ($uid) {
				$opts = ['form_params' => ['UID' => $uid]];
			} else {
				$opts = ['form_params' => ['uname' => $username]];
			}
			return $this->callAPI('/admin-users/info', 'POST', $opts);
		}
		return $this->callAPI('/account/info', 'GET');
	}
	public function changePassword($existingPassword, $newPassword) {
		$opts = ['form_params' => [
			'current_password' => $existingPassword,
			'new_password' => $newPassword,

		]];
		return $this->callAPI('/account/password', 'POST', $opts);
	}
	public function getAvatar() {
		return $this->callAPI('/account/avatar/', 'GET', [], true);
	}
	public function getFolderList($params) {
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
	public function downloadThumbnail($params) {
		$opts = ['form_params' => $params];
		return $this->callAPI('/files/thumbnail/', 'POST', $opts, true);
	}
	public function rename($params) {
		$opts = ['form_params' => $params];
		return $this->callAPI('/files/rename/', 'POST', $opts);
	}
	public function move($params) {
		$opts = ['form_params' => $params];
		return $this->callAPI('/files/move/', 'POST', $opts);
	}
	public function delete($params) {
		$opts = ['form_params' => $params];
		return $this->callAPI('/files/delete/', 'POST', $opts);
	}
	public function share($params) {
		$opts = ['form_params' => $params];
		return $this->callAPI('/files/share/', 'POST', $opts);
	}
	public function unshare($params) {
		$opts = ['form_params' => $params];
		return $this->callAPI('/files/unshare/', 'POST', $opts);
	}
	public function weblink($params) {
		$opts = ['form_params' => $params];
		return $this->callAPI('/files/weblink/', 'POST', $opts);
	}
	public function removeWeblink($params) {
		$opts = ['form_params' => $params];
		return $this->callAPI('/files/unweblink/', 'POST', $opts);
	}
	public function star($params) {
		$opts = ['form_params' => $params];
		return $this->callAPI('/files/star/', 'POST', $opts);
	}
	public function unstar($params) {
		$opts = ['form_params' => $params];
		return $this->callAPI('/files/unstar/', 'POST', $opts);
	}
	public function getMetadata($params) {
		$opts = ['form_params' => $params];
		return $this->callAPI('/files/metadata/', 'POST', $opts);
	}

	public function addUser($params) {
		$opts = ['form_params' => $params];
		return $this->callAPI('/admin-users/add', 'POST', $opts);
	}
	public function editUser($params) {
		$opts = ['form_params' => $params];
		return $this->callAPI('/admin-users/edit', 'POST', $opts);
	}
	public function deleteUsers($uids) {
		$opts = ['form_params' => ['UIDS' => $uids]];
		return $this->callAPI('/admin-users/delete', 'POST', $opts);
	}
	public function deleteUser($uid) {
		return $this->deleteUsers([$uid]);
	}
	public function getUserAvatar($uid = false) {
		$opts = ['form_params' => ['UID' => $uid]];
		return $this->callAPI('/admin-users/avatar/', 'POST', $opts, true);
	}
}