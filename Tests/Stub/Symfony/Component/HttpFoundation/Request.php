<?php
namespace Symfony\Component\HttpFoundation;

class ParameterBag
{
    /** @param array<string, mixed> $params */
    public function __construct(private array $params = []) {}
    /** @return array<string, mixed> */
    public function all(): array { return $this->params; }
    public function get(string $key, mixed $default = null): mixed { return $this->params[$key] ?? $default; }
}

class Request
{
    public ParameterBag $request;
    public ParameterBag $query;
    private string $method;
    private string $content = '';

    /** @param array<string, mixed> $query @param array<string, mixed> $request */
    public function __construct(array $query = [], array $request = [], string $method = 'GET', string $content = '')
    {
        $this->query = new ParameterBag($query);
        $this->request = new ParameterBag($request);
        $this->method = strtoupper($method);
        $this->content = $content;
    }

    /** @param array<string, mixed> $parameters */
    public static function create(string $uri, string $method = 'GET', array $parameters = []): self
    {
        return new self([], $parameters, $method);
    }

    public function isMethod(string $method): bool
    {
        return strtoupper($method) === $this->method;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getContent(): string
    {
        return $this->content;
    }
}
