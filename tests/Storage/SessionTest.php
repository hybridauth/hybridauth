<?php

namespace HybridauthTest\Hybridauth\Storage;

use Hybridauth\Storage\Session;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Covers;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

session_start(); // they will hate me for this..

#[CoversClass(Session::class)]
class SessionTest extends TestCase
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function some_random_session_data(): array
    {
        return [
            'string values' => ['foo', 'bar'],
            'numeric key' => [1234, 'bar'],
            'numeric value' => ['foo', 1234],

            'international strings' => ['Bonjour', '안녕하세요'],
            'more international strings' => ['ஹலோ', 'Γεια σας'],

            'array value' => ['array', [1, 2, 3]],
            'json string' => ['string', json_encode(new \stdClass())],
            'object value' => ['object', new \stdClass()],

            'request token' => ['provider.token.request_token', '9DYPEJ&qhvhP3eJ!'],
            'oauth token' => ['provider.token.oauth_token', '80359084-clg1DEtxQF3wstTcyUdHF3wsdHM'],
            'oauth token secret' => ['provider.token.oauth_token_secret', 'qiHTi1znz6qiH3tTcyUdHnz6qiH3tTcyUdH3xW3wsDvV08e']
        ];
    }

    #[Test]
    public function test_instance_of(): void
    {
        $storage = new Session();

        $this->assertInstanceOf('\\Hybridauth\\Storage\\StorageInterface', $storage);
    }

    #[Test]
    /** @dataProvider some_random_session_data */
    #[DataProvider('some_random_session_data')]
    #[Covers([Session::class, 'get'])]
    #[Covers([Session::class, 'set'])]
    public function testSetAndGetData($key, $value): void
    {
        $storage = new Session();

        $storage->set($key, $value);

        $data = $storage->get($key);

        $this->assertEquals($value, $data);
    }

    #[Test]
    /** @dataProvider some_random_session_data */
    #[DataProvider('some_random_session_data')]
    #[Covers([Session::class, 'delete'])]
    public function testDeleteData($key, $value): void
    {
        $storage = new Session();

        $storage->set($key, $value);

        $storage->delete($key);

        $data = $storage->get($key);

        $this->assertNull($data);
    }

    #[Test]
    /** @dataProvider some_random_session_data */
    #[DataProvider('some_random_session_data')]
    #[Covers([Session::class, 'clear'])]
    public function testClearData($key, $value): void
    {
        $storage = new Session();
        $storage->set($key, $value);
        $storage->clear();
        $data = $storage->get($key);
        $this->assertNull($data);
    }

    #[Test]
    #[Covers([Session::class, 'clear'])]
    public function testClearDataBulk(): void
    {
        $storage = new Session();
        $testData = self::some_random_session_data();

        // Set all test data
        foreach ($testData as $testCase) {
            list($key, $value) = $testCase;
            $storage->set($key, $value);
        }

        $storage->clear();

        // Check each key is now null
        foreach ($testData as $testCase) {
            list($key, $value) = $testCase;
            $data = $storage->get($key);
            $this->assertNull($data, "Data for key '$key' should be null after clear()");
        }
    }

    #[Test]
    #[DataProvider('some_random_session_data')]
    /** @dataProvider some_random_session_data */
    #[Covers([Session::class, 'deleteMatch'])]
    public function testDeleteMatchData($key, $value): void
    {
        $storage = new Session();
        $storage->set($key, $value);
        $storage->deleteMatch('provider.token.');
        $data = $storage->get('provider.token.request_token');
        $this->assertNull($data);
    }
}
