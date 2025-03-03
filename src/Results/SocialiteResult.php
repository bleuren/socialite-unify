<?php

namespace Bleuren\SocialiteUnify\Results;

use App\Models\User;

class SocialiteResult
{
    public function __construct(
        public readonly string $status,
        public readonly string $message,
        public readonly ?User $user = null,
        public readonly array $context = []
    ) {}

    public static function success(string $message, ?User $user = null, array $context = []): self
    {
        return new self('success', $message, $user, $context);
    }

    public static function error(string $message, array $context = []): self
    {
        return new self('error', $message, null, $context);
    }
} 