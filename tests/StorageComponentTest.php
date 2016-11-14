<?php
// vim: sw=4:ts=4:noet:sta:

use PHPUnit\Framework\TestCase;
use alexsalt\swiftstorage\StorageComponent;
use GuzzleHttp\Client;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;
use GuzzleHttp\Stream\StreamInterface;

class StorageComponentTest extends TestCase {

	public function testInit() {
	}

	public function testPut() {
		$storage = $this->getMockBuilder(StorageComponent::class)
			->disableOriginalConstructor()
			->setMethods([ 'ensureAuth', 'getClient', 'getFileHandle' ])
			->getMock();

		$guzzleMock = $this->createMock(Client::class);
		$responseMock = new Response(201);
		$fpMock = fopen('/dev/null', 'r');

		$storage->expects($this->once())
			->method('ensureAuth');

		$storage->expects($this->once())
			->method('getClient')
			->willReturn($guzzleMock);

		$storage->expects($this->once())
			->method('getFileHandle')
			->willReturn($fpMock);

		$guzzleMock->expects($this->once())
			->method('put')
			->with('dst', [ 'body' => $fpMock ])
			->willReturn($responseMock);

		$result = $storage->put('dst', 'srcf');

		$this->assertTrue($result);
	}

	public function testGet() {
		$storage = $this->getMockBuilder(StorageComponent::class)
			->disableOriginalConstructor()
			->setMethods([ 'ensureAuth', 'getClient', 'getFileHandle' ])
			->getMock();

		$guzzleMock = $this->createMock(Client::class);
		$stream = Stream::factory('resultstream');
		$responseMock = new Response(200, [ ], $stream);

		$storage->expects($this->once())
			->method('ensureAuth');

		$storage->expects($this->once())
			->method('getClient')
			->willReturn($guzzleMock);

		$guzzleMock->expects($this->once())
			->method('get')
			->with('testfile')
			->willReturn($responseMock);

		$result = $storage->get('testfile');
		$this->assertInstanceOf(StreamInterface::class, $result);
		$this->assertEquals('resultstream', (string)$result);
	}

	public function testGetAsString() {
		$storage = $this->getMockBuilder(StorageComponent::class)
			->disableOriginalConstructor()
			->setMethods([ 'ensureAuth', 'getClient', 'getFileHandle' ])
			->getMock();

		$guzzleMock = $this->createMock(Client::class);
		$stream = Stream::factory('resultstream');
		$responseMock = new Response(200, [ ], $stream);

		$storage->expects($this->once())
			->method('ensureAuth');

		$storage->expects($this->once())
			->method('getClient')
			->willReturn($guzzleMock);

		$guzzleMock->expects($this->once())
			->method('get')
			->with('testfile')
			->willReturn($responseMock);

		$result = $storage->getAsString('testfile');
		$this->assertInternalType('string', $result);
		$this->assertEquals('resultstream', $result);
	}

	public function testHeaders() {
		$storage = $this->getMockBuilder(StorageComponent::class)
			->disableOriginalConstructor()
			->setMethods([ 'ensureAuth', 'getClient', 'getFileHandle' ])
			->getMock();

		$guzzleMock = $this->createMock(Client::class);
		$responseMock = new Response(200, [ 'x-a' => 1, 'x-b' => 2 ]);

		$storage->expects($this->once())
			->method('ensureAuth');

		$storage->expects($this->once())
			->method('getClient')
			->willReturn($guzzleMock);

		$guzzleMock->expects($this->once())
			->method('head')
			->with('testfile')
			->willReturn($responseMock);

		$result = $storage->headers('testfile');
		$this->assertInternalType('array', $result);
		$expected = [ 'x-a' => 1, 'x-b' => 2 ];
		$this->assertEquals($expected, $result);
	}

	public function testExists() {
		$storage = $this->getMockBuilder(StorageComponent::class)
			->disableOriginalConstructor()
			->setMethods([ 'ensureAuth', 'getClient', 'getFileHandle' ])
			->getMock();

		$guzzleMock = $this->createMock(Client::class);
		$responseMock = new Response(200);

		$storage->expects($this->once())
			->method('ensureAuth');

		$storage->expects($this->once())
			->method('getClient')
			->willReturn($guzzleMock);

		$guzzleMock->expects($this->once())
			->method('head')
			->with('testfile')
			->willReturn($responseMock);

		$this->assertTrue($storage->exists('testfile'));
	}

	public function testExistsNot() {
		$storage = $this->getMockBuilder(StorageComponent::class)
			->disableOriginalConstructor()
			->setMethods([ 'ensureAuth', 'getClient', 'getFileHandle' ])
			->getMock();

		$guzzleMock = $this->createMock(Client::class);
		$responseMock = new Response(404);

		$storage->expects($this->once())
			->method('ensureAuth');

		$storage->expects($this->once())
			->method('getClient')
			->willReturn($guzzleMock);

		$guzzleMock->expects($this->once())
			->method('head')
			->with('testfile')
			->willReturn($responseMock);

		$this->assertFalse($storage->exists('testfile'));
	}

	public function testAuthenticate() {
		$storage = $this->getMockBuilder(StorageComponent::class)
			->disableOriginalConstructor()
			->setMethods([ 'createClient', 'createAuthClient' ])
			->getMock();

		$authClientMock = $this->createMock(Client::class);
		$authResponse = new Response(200, [
			'X-Storage-URL' => 'testurl',
			'X-Storage-Token' => 'testkey',
		]);

		$storage->expects($this->once())
			->method('createAuthClient')
			->willReturn($authClientMock);

		$authClientMock->expects($this->once())
			->method('get')
			->with('authurl', [
				'timeout' => 5,
				'headers' => [
					'X-Auth-User' => 'testuser',
					'X-Auth-Key' => 'testpassword',
				],
			])
			->willReturn($authResponse);

		$storage->expects($this->once())
			->method('createClient')
			->with('testurl', 'testkey');

		$storage->authUrl = 'authurl';
		$storage->username = 'testuser';
		$storage->password = 'testpassword';
		$storage->authenticate();
	}

	public function testEnsureAuth() {
		$storage = $this->getMockBuilder(StorageComponent::class)
			->disableOriginalConstructor()
			->setMethods([ 'authenticate' ])
			->getMock();

		$storage->expects($this->once())
			->method('authenticate');

		$storage->ensureAuth();
	}
}
