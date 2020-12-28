<?php

namespace Guym4c\Airtable\Request;

use Guym4c\Airtable\Airtable;
use Guym4c\Airtable\AirtableApiException;
use GuzzleHttp;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7;
use Stiphle\Storage\DoctrineCache as ThrottleStorage;
use Stiphle\Throttle;
use Teapot\StatusCode;

abstract class AbstractRequest {

    private const THROTTLER_ID = self::class;

    protected Airtable $airtable;

    protected GuzzleHttp\Client $http;

    protected Throttle\LeakyBucket $throttle;

    protected Psr7\Request $request;

    protected array $options = [];

    protected string $table;

    protected bool $error = false;

    public function __construct(
        Airtable $airtable,
        string $table,
        string $method,
        string $uri = '',
        array $query = [],
        array $body = []
    ) {
        $this->airtable = $airtable;
        $this->table = $table;

        $this->http = new GuzzleHttp\Client();
        $this->throttle = new Throttle\LeakyBucket();

        if (!empty($airtable->getCache())) {
            $this->throttle->setStorage(new ThrottleStorage($airtable->getCache()));
        }

        $this->request = new Psr7\Request($method,
            implode('/', [
                $this->airtable->getApiEndpoint(),
                $this->airtable->getBaseId(),
                $this->table,
                $uri,
            ]),
            array_merge(
                $this->airtable->getHeaders(),
                ['Authorization' => "Bearer {$this->airtable->getKey()}"]
            )
        );

        if ($uri === '') {
            $this->request = $this->request->withUri(new Psr7\Uri(
                substr($this->request->getUri(), 0, -1)));
        }

        if (!empty($query)) {
            $this->options['query'] = $query;
        }

        if (!empty($body)) {
            $this->options['json'] = $body;
        }

        if ($method != 'GET') {
            /** @noinspection PhpFieldAssignmentTypeMismatchInspection */
            $this->request = $this->request
                ->withHeader('Content-Type', 'application/json');
        }
    }

    public abstract function getResponse();

    /**
     * @return array JSON
     * @throws AirtableApiException
     */
    protected function execute(): array {

        if ($this->airtable->isRateLimited()) {
            $this->wait();
        }

        try {
            $response = $this->http->send($this->request, $this->options);
        } catch (GuzzleException $e) {
            throw AirtableApiException::fromGuzzle($e);
        }

        $responseBody = (string) $response->getBody();
        $responseCode = $response->getStatusCode();

        if ($responseCode !== StatusCode::OK) {
            throw AirtableApiException::fromErrorResponse($responseCode, $responseBody);
        }

        return json_decode($responseBody, true);
    }

    private function wait(): int {
        return $this->throttle->throttle(self::THROTTLER_ID, 5, 1000);
    }
}
