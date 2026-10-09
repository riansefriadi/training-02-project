<?php

namespace App\Exceptions;

use RuntimeException;

// Pelanggaran business rule dengan kode ERR dari docs/design/02-rules.md.
class OrderException extends RuntimeException
{
    public function __construct(public readonly string $errorCode, string $message)
    {
        parent::__construct($message);
    }
}
