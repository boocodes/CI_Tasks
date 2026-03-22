<?php

namespace Task5\Application;

use Task5\Domain\Enums\HttpMethods;
use Task5\Infrastructure\Request\Request;
use Task5\Infrastructure\Route\Route;

class App
{
    private string $rootStoragePath = __DIR__ . '/../storage/';
    private Request $request;
    private array $getRoutesArray = [];
    private array $postRoutesArray = [];
    private array $patchRoutesArray = [];
    private array $deleteRoutesArray = [];
    private array $optionsRoutesArray = [];
    private $notFoundPageController;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->notFoundPageController = function ()
        {
            http_response_code(404);
        };
    }

    public function addGetRoute(string $url, callable $callback, array $middleware = []): void
    {
        $this->getRoutesArray[] = new Route($url, $callback, $middleware, $this->request);
    }
    public function setNotFoundPageController(callable $callback): void
    {
        $this->notFoundPageController = $callback;
    }
    public function addOptionRoute(string $url, callable $callback, array $middleware = []): void
    {
        $this->optionsRoutesArray[] = new Route($url, $callback, $middleware, $this->request);
    }
    public function addPostRoute(string $url, callable $callback, array $middleware = []): void
    {
        $this->postRoutesArray[] = new Route($url, $callback, $middleware, $this->request);
    }
    public function addPatchRoute(string $url, callable $callback, array $middleware = []): void
    {
        $this->patchRoutesArray[] = new Route($url, $callback, $middleware, $this->request);
    }
    public function addDeleteRoute(string $url, callable $callback, array $middleware = []): void
    {
        $this->deleteRoutesArray[] = new Route($url, $callback, $middleware, $this->request);
    }


    public function run(): void
    {
        header('Access-Control-Allow-Origin: http://localhost:5173');
        header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, Idempotency-Key');
        header('Content-Type: application/json');

        if($this->request->getMethod() === HttpMethods::OPTIONS) {
            http_response_code(204);
            return;
        }

        if($this->request->getMethod() === HttpMethods::GET) {
            foreach ($this->getRoutesArray as $route) {
               $params = $route->matchUrl($this->request->getEndpoint());
               if($params !== null) {
                   $route->execute($this->request, $params);
                   return;
               }
            }
        }
        else if($this->request->getMethod() === HttpMethods::POST) {
            foreach ($this->postRoutesArray as $route) {
                $params = $route->matchUrl($this->request->getEndpoint());
                if($params !== null) {
                    $route->execute($this->request, $params);
                    return;
                }
            }
        }
        else if($this->request->getMethod() === HttpMethods::PATCH) {
            foreach ($this->patchRoutesArray as $route) {
                $params = $route->matchUrl($this->request->getEndpoint());
                if($params !== null) {
                    $route->execute($this->request, $params);
                    return;
                }
            }
        }
        else if($this->request->getMethod() === HttpMethods::DELETE) {
            foreach ($this->deleteRoutesArray as $route) {
                $params = $route->matchUrl($this->request->getEndpoint());
                if($params !== null) {
                    $route->execute($this->request, $params);
                    return;
                }
            }
        }
        call_user_func_array($this->notFoundPageController, [$this->request]);
    }

}