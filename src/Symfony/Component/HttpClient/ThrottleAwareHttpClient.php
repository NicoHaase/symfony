<?php

declare(strict_types=1);

namespace Symfony\Component\HttpClient;

use Symfony\Component\HttpClient\Response\AsyncContext;
use Symfony\Component\HttpClient\Response\AsyncResponse;
use Symfony\Contracts\HttpClient\ChunkInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ThrottleAwareHttpClient implements HttpClientInterface
{
    use AsyncDecoratorTrait;

    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        $passthru = static function (ChunkInterface $chunk, AsyncContext $context) use ($method, $url, $options) {
            if ($context->getStatusCode() === 429) {
                $context->getResponse()->cancel();
                $context->pause(1);
                $context->replaceRequest($method, $url, $options);
            }
        };

        return new AsyncResponse($this->client, $method, $url, $options, $passthru);
    }
}
