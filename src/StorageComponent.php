<?php
// vim: sw=4:ts=4:noet:sta:

namespace alexsalt\swiftstorage;

use yii\base\Component;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\RequestException;

class StorageComponent extends Component {

	/**
	 * @var string auth server url
	 */
	public $authUrl;

	/**
	 * @var string auth server username
	 */
	public $username;

	/**
	 * @var string auth server password
	 */
	public $password;

	/**
	 * @var integer auth timeout in seconds
	 */
	public $authTimeout = 5;

	/**
	 * @var string storage communication timeout
	 */
	public $storageTimeout = 10;

	/**
	 * @var string container name
	 */
	public $container;

	private $_client;

	public function init() {
		parent::init();
		$this->authenticate();
	}

	/**
	 * put file
	 *
	 * @param string $dstPath container/dstPath
	 * @param string|resource $srcFile source file to get
	 * @return boolean
	 * @throws \InvalidArgumentException
	 * @throws StorageException
	 */
	public function put($dstPath, $srcFile) {
		$client = $this->getClient();
		$handle = $this->getFileHandle($srcFile);

		try {
			$response = $client->put($dstPath, [ 'body' => $handle ]);
		} catch (RequestException $e) {
			throw new StorageException('put file error', 0, $e);
		}

		return $response->getStatusCode() == 201;
	}

	/**
	 * get file data
	 *
	 * @param string $filename file path
	 * @return \GuzzleHttp\Stream\StreamInterface поток с файлом
	 * @throws StorageException
	 */
	public function get($filename) {
		$client = $this->getClient();

		try {
			$response = $client->get($filename);
		} catch (RequestException $e) {
			throw new StorageException('get file exception', 0, $e);
		}

		return $response->getBody();
		
	}

	/**
	 * get file data as string
	 *
	 * @param string $filename file path
	 * @return string file data
	 */
	public function getAsString($filename) {
		return (string)$this->get($filename);
	}

	/**
	 * query file headers from storage
	 *
	 * @param string $filename filename to get from container
	 * @return array headers
	 * @throws StorageException
	 */
	public function headers($filename) {
		$client = $this->getClient();

		try {
			$response = $client->head($filename);
		} catch (RequestException $e) {
			throw new StorageException('get headers failed', 0, $e);
		}

		$headers = $response->getHeaders();
		$result = [ ];
		foreach ($headers as $name => $values) {
			$result[$name] = implode(', ', $values);
		}
		return $result;
	}

	/**
	 * check if file exists in container
	 * @param string $filename
	 * @return boolean
	 * @throws StorageException
	 */
	public function exists($filename) {
		$client = $this->getClient();
		try {
			$response = $client->head($filename);
		} catch (RequestException $e) {
			if ($e->getCode() == 404) {
				return false;
			} else {
				throw new StorageException('exists check fail', 0, $e);
			}
		}
		return $response->getStatusCode() == 200;
	}

	/**
	 * authenticate at auth server
	 * @throws StorageAuthException
	 */
	public function authenticate() {
		$authClient = $this->createAuthClient();
		try {
			$response = $authClient->get($this->authUrl, [
				'timeout' => $this->authTimeout,
				'headers' => [
					'X-Auth-User' => $this->username,
					'X-Auth-Key' => $this->password,
				],
			]);
		} catch (RequestException $e) {
			if ($e->getCode() == 403) {
				throw new StorageAuthException('auth fail: invalid credentials', 0, $e);
			} else if ($e->getCode() == 404) {
				throw new StorageAuthException('auth fail: invalid auth url', 0, $e);
			} else {
				throw new StorageAuthException('auth fail: unknown reason', 0, $e);
			}
		}
		$this->_client = $this->createClient($response->getHeader('x-storage-url'), $response->getHeader('x-storage-token'));
	}

	/**
	 * @return \GuzzleHttp\Client
	 */
	protected function createAuthClient() {
		return new HttpClient;
	}

	/**
	 * create storage client
	 *
	 * @param string $storageUrl from x-storage-url
	 * @param string $storageKey storage api token from x-storage-token
	 * @return \GuzzleHttp\Client set-up guzzle client
	 */
	protected function createClient($storageUrl, $storageKey) {
		return new HttpClient([
			'base_url' => $storageUrl . $this->container . '/',
			'defaults' => [
				'timeout' => $this->storageTimeout,
				'headers' => [
					'X-Auth-Token' => $storageKey,
				],
			],
		]);
	}

	public function getClient() {
		return $this->_client;
	}

	public function getFileHandle($srcFile) {
		if (is_string($srcFile)) {
			$file = fopen($srcFile, 'r');
		} else if (is_resource($srcFile)) {
			$file = $srcFile;
		} else {
			throw new \InvalidArgumentException('param $srcFile is invalid');
		}
	}
}
