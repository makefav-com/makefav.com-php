<?php

declare(strict_types=1);

namespace MakeFav;

/**
 * API error. getCode() is the HTTP status, getErrorCode() the API error code
 * (unauthorized, forbidden, rate_limited, invalid_image, file_too_large, …).
 */
final class MakeFavException extends \RuntimeException
{
    /** @var string */
    private $errorCode;

    public function __construct(string $message, int $httpStatus, string $errorCode)
    {
        parent::__construct($message, $httpStatus);
        $this->errorCode = $errorCode;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }
}
