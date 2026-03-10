<?php

namespace FileRun\API\Client\Actions;

use FileRun\API\Client\FileRunAPIClient;

class Admin {

	static function getUserInfoById(FileRunAPIClient $client, int $uid) {
		$opts = ['form_params' => ['UID' => $uid]];
		return $client->callAPI('Core/!admin/users/info', 'POST', $opts);
	}

	static function getUserInfoByUsername(FileRunAPIClient $client, string $username) {
		$opts = ['form_params' => ['uname' => $username]];
		return $client->callAPI('Core/!admin/users/info', 'POST', $opts);
	}

	function addUser(FileRunAPIClient $client, array $params) {
		$opts = ['form_params' => $params];
		return $client->callAPI('Core/!admin/users/add', 'POST', $opts);
	}

	function editUser(FileRunAPIClient $client, array $params) {
		$opts = ['form_params' => $params];
		return $client->callAPI('Core/!admin/users/edit', 'POST', $opts);
	}

	function deleteUsers(FileRunAPIClient $client, array $uids) {
		$opts = ['form_params' => ['UIDS' => $uids]];
		return $client->callAPI('Core/!admin/users/delete', 'POST', $opts);
	}

	function deleteUser(FileRunAPIClient $client, array $uid) {
		return $this->deleteUsers($client, [$uid]);
	}

	function getUserAvatar(FileRunAPIClient $client, int $uid) {
		$opts = ['form_params' => ['UID' => $uid]];
		return $client->callAPI('/admin-users/avatar/', 'POST', $opts, true);
	}
}