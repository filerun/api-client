<?php
namespace FileRun\API;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\ClientInterface as HttpClientInterface;

class Client {
	private $url;
	private $redirect_uri;
	private $client_id;
	private $client_secret;
	private $username;
	private $password;

	public function __construct(array $options = [], array $collaborators = []) {
		foreach ($options as $option => $value) {
			if (property_exists($this, $option)) {
				$this->{$option} = $value;
			}
		}
	}
	public function connect() {
		$http = new HttpClient();
		try {
			$req = $http->post($this->url.'/oauth2/token/',
				array(
					'client_id' => $this->client_id,
					'client_secret' => $this->client_secret,
					'username' => $this->username,
					'password' => $this->password,
					'redirect_uri' => $this->redirect_uri,
					'grant_type' => 'password'
				));
			$response = $req->send();
		} catch (\GuzzleHttp\Exception\BadResponseException $e) {
			echo 'Bad server response: '.$e->getResponse()->getStatusCode();
			echo $e->getResponse();
			exit();
		} catch (\GuzzleHttp\Exception\CurlException $e) {
			echo 'Connection failed: '.$e->getErrorNo();
			echo $e->getMessage();
			exit();
		}
		if ($response->isSuccessful()) {
			$rs = $response->json();
			print_r($rs);
			return true;
		}

	}
}