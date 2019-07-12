<?php

namespace Guym4c\Airtable;

use Exception;
use GuzzleHttp\Exception\GuzzleException;

class AirtableApiException extends Exception {

    public static function fromErrorResponse(int $code, string $json):self {
        return new self(\sprintf('Error response with code: %s and body: "%s"', $code, $json));
    }

    public static function fromGuzzle(GuzzleException $e): self {
        return new self($e->getMessage(), $e->getCode(), $e);
    }
}
