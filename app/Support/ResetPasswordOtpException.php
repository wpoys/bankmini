<?php

namespace App\Support;

use RuntimeException;

class ResetPasswordOtpException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $field,
        public readonly int $status = 422,
        public readonly ?string $fieldMessage = null,
    ) {
        parent::__construct($message);
    }

    /**
     * Isi payload "errors" sesuai format validasi Laravel.
     */
    public function errors(): array
    {
        return [$this->field => [$this->fieldMessage ?? $this->getMessage()]];
    }
}
