<?php
declare(strict_types = 1);

namespace FileRun\API\Client\Actions;

use FileRun\API\Client\FileRunAPIClient;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

class Files {

	static function browse(FileRunAPIClient $client, array $params) {
		return $client->callAPI('Drive/files/browse', 'POST', ['form_params' => $params]);
	}

	static function search(FileRunAPIClient $client, array $params) {
		return $client->callAPI('Drive/files/search', 'POST', ['form_params' => $params]);
	}

	static function createFolder(FileRunAPIClient $client, array $params) {
		return $client->callAPI('Drive/files/createfolder', 'POST', ['form_params' => $params]);
	}

	static function upload(FileRunAPIClient $client, array $opts) {
		if (!isset($opts['query']['path'])) {
			throw new InvalidArgumentException('Missing query variable "path"!');
		}
		if (!isset($opts['query']['filePath'])) {
			throw new InvalidArgumentException('Missing query variable "filePath"!');
		}
		if (!isset($opts['query']['getOffset'])) {
			if (!isset($opts['query']['totalSize'])) {
				throw new InvalidArgumentException('Missing query variable "totalSize"!');
			}
			if (!isset($opts['query']['startByte'])) {
				throw new InvalidArgumentException('Missing query variable "startByte"!');
			}
			if (!isset($opts['body'])) {
				throw new InvalidArgumentException('Missing "body"! Assign a string or a file pointer!');
			}
		}
		$rs = $client->callAPI('Drive/files/upload', 'PUT', $opts);

		if (!empty($rs['skip'])) {
			throw new RuntimeException('The server does not want you to upload this file!');
		}
		return $rs;
	}

	static function download(FileRunAPIClient $client, array $params): ResponseInterface {
		if (!isset($params['path'])) {
			throw new InvalidArgumentException('Missing parameter "path"!');
		}
		return $client->callAPI('Drive/files/download', 'POST', ['form_params' => $params], true);
	}

	static function downloadThumbnail(FileRunAPIClient $client, array $params): ResponseInterface {
		if (!isset($params['path'])) {
			throw new InvalidArgumentException('Missing parameter "path"!');
		}
		return $client->callAPI('Drive/files/thumbnail', 'POST', ['form_params' => $params], true);
	}

	static function rename(FileRunAPIClient $client, array $params) {
		if (!isset($params['path'])) {
			throw new InvalidArgumentException('Missing parameter "path"!');
		}
		if (!isset($params['newName'])) {
			throw new InvalidArgumentException('Missing parameter "newName"!');
		}
		return $client->callAPI('Drive/files/rename', 'POST', ['form_params' => $params]);
	}

	static function move(FileRunAPIClient $client, array $params) {
		if (!isset($params['path'])) {
			throw new InvalidArgumentException('Missing parameter "path"!');
		}
		if (!isset($params['moveTo'])) {
			throw new InvalidArgumentException('Missing parameter "moveTo"!');
		}
		return $client->callAPI('Drive/files/move', 'POST', ['form_params' => $params]);
	}

	static function delete(FileRunAPIClient $client, array $params) {
		if (!isset($params['path'])) {
			throw new InvalidArgumentException('Missing parameter "path"!');
		}
		return $client->callAPI('Drive/files/delete', 'POST', ['form_params' => $params]);
	}

	static function share(FileRunAPIClient $client, array $params) {
		if (!isset($params['path'])) {
			throw new InvalidArgumentException('Missing parameter "path"!');
		}
		return $client->callAPI('Drive/files/share', 'POST', ['form_params' => $params]);
	}

	static function unshare(FileRunAPIClient $client, array $params) {
		if (!isset($params['path'])) {
			throw new InvalidArgumentException('Missing parameter "path"!');
		}
		return $client->callAPI('Drive/files/unshare', 'POST', ['form_params' => $params]);
	}

	static function weblink(FileRunAPIClient $client, array $params) {
		if (!isset($params['path'])) {
			throw new InvalidArgumentException('Missing parameter "path"!');
		}
		return $client->callAPI('Drive/files/weblink', 'POST', ['form_params' => $params]);
	}

	static function removeWeblink(FileRunAPIClient $client, array $params) {
		if (!isset($params['path'])) {
			throw new InvalidArgumentException('Missing parameter "path"!');
		}
		return $client->callAPI('Drive/files/unweblink', 'POST', ['form_params' => $params]);
	}

	static function star(FileRunAPIClient $client, array $params) {
		if (!isset($params['path'])) {
			throw new InvalidArgumentException('Missing parameter "path"!');
		}
		return $client->callAPI('Drive/files/star', 'POST', ['form_params' => $params]);
	}

	static function unstar(FileRunAPIClient $client, array $params) {
		if (!isset($params['path'])) {
			throw new InvalidArgumentException('Missing parameter "path"!');
		}
		return $client->callAPI('Drive/files/unstar', 'POST', ['form_params' => $params]);
	}

	static function getMetadata(FileRunAPIClient $client, array $params) {
		return $client->callAPI('Drive/files/metadata/get', 'POST', ['form_params' => $params]);
	}

	public static function zip(FileRunAPIClient $client, array $opts) {
		if (!isset($opts['paths'])) {
			throw new InvalidArgumentException('Missing parameter "paths"!');
		}
		if (!is_array($opts['paths'])) {
			throw new InvalidArgumentException('"paths" needs to be an array!');
		}
		if (!isset($opts['target'])) {
			throw new InvalidArgumentException('Missing parameter "target"!');
		}
		return $client->callAPI(
			'Drive/files/zip',
			'POST',
			['form_params' => $opts]
		);
	}

	public static function extract(FileRunAPIClient $client, array $opts) {
		if (!isset($opts['path'])) {
			throw new InvalidArgumentException('Missing parameter "path"!');
		}
		if (!isset($opts['target'])) {
			throw new InvalidArgumentException('Missing parameter "target"!');
		}
		return $client->callAPI(
			'Drive/files/extract',
			'POST',
			['form_params' => $opts]
		);
	}

	public static function metadataSet(FileRunAPIClient $client, array $opts) {
		if (!isset($opts['path'])) {
			throw new InvalidArgumentException('Missing parameter "path"!');
		}
		if (!isset($opts['fields'])) {
			throw new InvalidArgumentException('Missing parameter "fields"!');
		}
		if (!is_array($opts['fields'])) {
			throw new InvalidArgumentException('"fields" parameter needs to be an array!');
		}
		return $client->callAPI(
			'Drive/files/metadata/set',
			'POST',
			['form_params' => $opts]
		);
	}

	public static function getMetadataFields(FileRunAPIClient $client) {
		return $client->callAPI(
			'Drive/files/metadata/fields'
		);
	}
}