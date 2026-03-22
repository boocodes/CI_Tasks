<?php

namespace Task5\Infrastructure\Middleware;

use Task5\Domain\Interfaces\Middleware;
use Task5\Infrastructure\Request\Request;


class BearerToken implements Middleware
{
    public function run(Request $request): bool
    {

        $headerAuth = $request->getHeaders()['HTTP_AUTHORIZATION'] ?? $request->getHeaders()['Authorization'] ?? null;
        if($headerAuth === null || !isset($request->getConfig()['API_KEY'])) {
            header('WWW-Authenticate: Bearer');
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Authentication required']);
            exit(0);
        }

        $headerToken = $request->getHeaders()['HTTP_AUTHORIZATION'] ?? $request->getHeaders()['Authorization'];
        $bearerTokenInput = '';
        $matches = [];
        if(preg_match('/Bearer\s(\S+)/', $headerToken, $matches))
        {
            $bearerTokenInput = $matches[1];
        }
        else{
            return false;
        }
        if($bearerTokenInput !== $request->getConfig()['API_KEY']) {
            header('WWW-Authenticate: Bearer');
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Authorization wrong']);
            exit(0);
        }
        return true;
    }
}