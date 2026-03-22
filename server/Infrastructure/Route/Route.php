<?php

namespace Task5\Infrastructure\Route;

use Task5\Domain\Interfaces\Middleware;
use Task5\Infrastructure\Request\Request;

class Route
{
    private Request $request;
    private string $pattern = '';
    private array $paramNames = [];
    private array $optionalParams = [];

    private string $url;
    private $callback;
    private array $middlewares = [];

    public function __construct(string $url, callable $callback, array $middlewares, Request $request)
    {
        $this->url = $url;
        $this->callback = $callback;
        $this->middlewares = $middlewares;
        $this->request = $request;
        $this->prepareUrl();
    }

    private function prepareUrl(): void
    {
        $pattern = $this->url;

        preg_match_all('/\{([a-zA-Z0-9_]+)\}/', $pattern, $matches);
        $this->paramNames = $matches[1] ?? [];

        preg_match_all('/\{([a-zA-Z0-9_]+)\?\}/', $pattern, $optionalMatches);
        $this->optionalParams = $optionalMatches[1] ?? [];
        $this->paramNames = array_merge($this->paramNames, $this->optionalParams);
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([^/]+)', $pattern);

        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\?\}/', '([^/]*)', $pattern);

        $this->pattern = '#^' . str_replace('/', '\/', $pattern) . '$#';
    }
    private function runMiddleware(): bool
    {
        $flag = true;
        foreach ($this->middlewares as $middleware) {
            if($middleware instanceof Middleware) {
                $flag = $middleware->run($this->request);
            }
        }
        return $flag;
    }
    public function matchUrl(string $url): array|null
    {
        if(preg_match($this->pattern, $url, $matches)) {
            array_shift($matches);
            $params = [];
            foreach($this->paramNames as $index => $name) {
                $value = $matches[$index] ?? null;
                if(in_array($name, $this->optionalParams) && $value === '') {
                    $params[$name] = null;
                }
                else
                {
                    $params[$name] = $value;
                }
            }
            return $params;
        }
        else
        {
            return null;
        }
    }

    public function execute($request, array $params = [])
    {

        if($this->runMiddleware($request))
        {
            $args = [$request];
            foreach($this->paramNames as $paramName) {
                $args[] = $params[$paramName] ?? null;
            }
            return call_user_func_array($this->callback, $args);
        }

    }
}