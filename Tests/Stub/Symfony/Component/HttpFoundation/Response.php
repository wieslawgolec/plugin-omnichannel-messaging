<?php
namespace Symfony\Component\HttpFoundation;

class Response
{
    public function __construct(
        private string $content = '',
        private int $status = 200,
        private array $headers = [],
    ) {}

    public function getContent(): string|false
    {
        return $this->content;
    }

    public function getStatusCode(): int
    {
        return $this->status;
    }

    public function headers(): array
    {
        return $this->headers;
    }
}
