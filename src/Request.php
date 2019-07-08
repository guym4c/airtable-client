<?php

namespace Guym4c\Airtable;

use GuzzleHttp;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7;
use Stiphle\Throttle;

class Request {

    const THROTTLER_ID = 'airtable';

    /** @var Airtable */
    private $airtable;

    /** @var GuzzleHttp\Client */
    private $http;

    /** @var Throttle\LeakyBucket */
    private $throttle;

    /** @var Psr7\Request */
    private $request;

    /** @var array */
    private $options;

    /** @var bool */
    private $list;

    public function __construct(string $table, string $method, bool $list = false, string $uri = '', array $query = [], array $body = []) {

        $this->request = new Psr7\Request($method,
            sprintf('%s/%s/%s/%s',
                Airtable::API_ENDPOINT,
                $this->airtable->getBaseId(),
                $table,
                $uri));

        if ($uri = '')
            $this->request->withUri(new Psr7\Uri(
                substr($this->request->getUri(), 0, -1)));

        $this->request->withAddedHeader('Authorization', 'Bearer ' . $this->airtable->getKey());

        $this->list = $list;

        if (!empty($query))
            $this->options['query'] = $query;

        if (!empty($body))
            $this->options['json'] = $body;

        if ($method != 'GET')
            $this->request->withAddedHeader('Content-Type', 'application/json');
    }

    /**
     * @return Response
     * @throws GuzzleException
     */
    public function getResponse(): Response {

        usleep($this->getRateLimitWaitTime() * 1000); // microseconds
        return new Response($this->http->send($this->request, $this->options), $this->list);
    }

    private function getRateLimitWaitTime(): int {
        return $this->throttle->throttle(self::THROTTLER_ID, 5, 1000);
    }


}