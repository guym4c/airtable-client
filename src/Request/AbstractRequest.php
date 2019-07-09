<?php

namespace Guym4c\Airtable\Request;

use Guym4c\Airtable\Airtable;
use Guym4c\Airtable\AirtableApiException;
use GuzzleHttp;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7;
use Stiphle\Throttle;
use Teapot\StatusCode;
use Teapot\StatusCode\All;

abstract class AbstractRequest {

    const THROTTLER_ID = 'airtable';

    /** @var Airtable */
    protected $airtable;

    /** @var GuzzleHttp\Client */
    protected $http;

    /** @var Throttle\LeakyBucket */
    protected $throttle;

    /** @var Psr7\Request */
    protected $request;

    /** @var array */
    protected $options;

    /** @var string */
    protected $table;

    /** @var bool */
    protected $error = false;

    public function __construct(Airtable $airtable, string $table, string $method, string $uri = '', array $query = [], array $body = []) {

        $this->airtable = $airtable;
        $this->table = $table;

        $this->request = new Psr7\Request($method,
            sprintf('%s/%s/%s/%s',
                Airtable::API_ENDPOINT,
                $this->airtable->getBaseId(),
                $this->table,
                $uri));

        if ($uri = '')
            $this->request->withUri(new Psr7\Uri(
                substr($this->request->getUri(), 0, -1)));

        $this->request->withAddedHeader('Authorization', 'Bearer ' . $this->airtable->getKey());

        if (!empty($query))
            $this->options['query'] = $query;

        if (!empty($body))
            $this->options['json'] = $body;

        if ($method != 'GET')
            $this->request->withAddedHeader('Content-Type', 'application/json');
    }

    public abstract function getResponse();

    /**
     * @return array JSON
     * @throws AirtableApiException
     */
    protected function execute(): array {

        try {
            $response = $this->http->send($this->request, $this->options);
        } catch (GuzzleException $e) {
            throw AirtableApiException::fromGuzzle($e);
        }

        $json = json_decode($response->getBody()->getContents(), true);

        if ($response->getStatusCode() != StatusCode::OK) {
            throw new AirtableApiException($json);
        }

        return $json;
    }

    protected function getRateLimitWaitTime(): int {
        return $this->throttle->throttle(self::THROTTLER_ID, 5, 1000);
    }


}