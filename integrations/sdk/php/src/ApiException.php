<?php

namespace ShortlSdk;

/**
 * Thrown for any non-2xx API response or transport error. The HTTP status is
 * available via getCode(); the decoded JSON body (if any) via getResponse().
 */
class ApiException extends \RuntimeException
{
    private array $response;

    public function __construct(string $message, int $status = 0, array $response = [])
    {
        parent::__construct($message, $status);
        $this->response = $response;
    }

    /**
     * The decoded JSON error body, e.g. ['message' => ..., 'errors' => ...].
     */
    public function getResponse(): array
    {
        return $this->response;
    }
}
