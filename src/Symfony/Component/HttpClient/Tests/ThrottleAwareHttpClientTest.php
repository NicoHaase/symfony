<?php

declare(strict_types=1);

namespace Symfony\Component\HttpClient\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpClient\ThrottleAwareHttpClient;

class ThrottleAwareHttpClientTest extends TestCase
{
    public function testThrottlingClientWaitsForSpecifiedTime()
    {
        $isFirstRequest = true;
        $firstAccessTimestamp = -1;

        $throttlingFactory = static function (string $method, string $url, array $options = []) use (&$isFirstRequest, &$firstAccessTimestamp) {
            if ($isFirstRequest) {
                $isFirstRequest = false;
                $firstAccessTimestamp = time();

                return new MockResponse('', ['http_code' => 429, 'response_headers' => ['HTTP/1.1 429 Too Many Requests', 'Retry-After: 5']]);
            }

            $currentAccessTimestamp = time();

            if ($currentAccessTimestamp - $firstAccessTimestamp < 5) {
                self::fail('Not waiting enough');
            }

            return new MockResponse();
        };

        $innerClient = new MockHttpClient($throttlingFactory);
        $throttleAwareClient = new ThrottleAwareHttpClient($innerClient);

        $response = $throttleAwareClient->request('GET', 'http://example.com/');

        $this->assertEquals(200, $response->getStatusCode());
    }
}
