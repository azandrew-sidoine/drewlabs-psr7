<?php

namespace Drewlabs\Psr7\Exceptions;

use Psr\Http\Client\NetworkExceptionInterface;

class NetworkException extends RequestException implements NetworkExceptionInterface
{
}
