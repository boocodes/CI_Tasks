<?php

namespace Task5\Infrastructure\Request;


use Task5\Domain\Enums\HttpMethods;

class Request
{
    private HttpMethods $method = HttpMethods::GET;
    private array $headers = [];
    private array $config = [];
    private string $rootURL = '';
    private string $fullURL = '';
    private array $query = [];
    private string $endPoint = '';

    private array $body = [];
    private string $id = '';

    public function __construct()
    {
        $this->body = json_decode(file_get_contents('php://input'), true) ?? [];
        $this->headers = $_SERVER;
        $this->rootURL = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://" . $_SERVER['HTTP_HOST'];
        $this->fullURL = $this->rootURL . $_SERVER['REQUEST_URI'];

        $parsedUrl = parse_url($this->fullURL);

        $path = $parsedUrl['path'] ?? '';
        $pathParts = explode('/', trim($path, '/'));


        $this->id = $pathParts[1] ?? '';

        $this->endPoint = ltrim($path, '/');
        $this->endPoint = '/' . $this->endPoint;


        $this->query = [];
        if (isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $this->query);
        }

        $method = $_SERVER['REQUEST_METHOD'];
        if ($_SERVER['REQUEST_METHOD'] === 'GET') $this->method = HttpMethods::GET;
        else if ($_SERVER['REQUEST_METHOD'] === 'POST') $this->method = HttpMethods::POST;
        else if ($_SERVER['REQUEST_METHOD'] === 'PATCH') $this->method = HttpMethods::PATCH;
        else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') $this->method = HttpMethods::DELETE;
        else if($_SERVER['REQUEST_METHOD'] === 'OPTIONS') $this->method = HttpMethods::OPTIONS;

        $this->config = require_once __DIR__ .  '/../../config.php';
    }
    public function getBody()
    {
        return $this->body;
    }
    public function getConfig(): array
    {
        return $this->config;
    }
    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getMethod(): HttpMethods
    {
        return $this->method;
    }

    public function getEndpoint(): string
    {
        return $this->endPoint;
    }
    public function getId(): string
    {
        return $this->id;
    }
    public function getQuery(): array
    {
        return $this->query;
    }
}