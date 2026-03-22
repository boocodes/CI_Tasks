<?php

use Task5\Application\App;
use Task5\Infrastructure\Request\Request;
use Task5\Infrastructure\Response\Response;
use Task5\Application\Model\Task;
use Task5\Infrastructure\Middleware\BearerToken;


require_once __DIR__ . "/vendor/autoload.php";

$appInstance = new App(new Request());

$appInstance->addPostRoute('/tasks', function (Request $request) {
    $result = new Task()->add($request->getBody());
    if (!empty($result)) {
        header('Location: /tasks/' . $result['id']);
        http_response_code(200);
        echo json_encode($result);
    } else {
        http_response_code(422);
    }
}, [new BearerToken()]);
$appInstance->addGetRoute('/tasks', function (Request $request) {
    http_response_code(200);
    $limit = $request->getQuery()['limit'] ?? null;
    $cursor = $request->getQuery()['cursor'] ?? null;
    $status = $request->getQuery()['status'] ?? null;
    
    $result = new Task()->getAll($limit, $cursor);
    
    if ($status && isset($result['items'])) {
        $sortedResult = [];
        foreach ($result['items'] as $task) {
            if ($task['status'] === $status) {
                $sortedResult[] = $task;
            }
        }
        echo json_encode($sortedResult);
    } else {
        echo json_encode($result);
    }
    
});

$appInstance->addGetRoute('/tasks/{id}', function (Request $request, $id) {
    $result = new Task()->getById($id);
    if(empty($result))
    {
        http_response_code(404);
        echo json_encode([]);
    }
    else
    {
        http_response_code(200);
        echo json_encode($result);
    }
});

$appInstance->addPatchRoute('/tasks/{id}', function (Request $request, $id) {
    $result = new Task()->editById($id, $request->getBody());
    if(empty($result))
    {
        http_response_code(422);
    }
    else
    {
        http_response_code(200);
        echo json_encode($result);
    }
}, [new BearerToken()]);


$appInstance->addDeleteRoute('/tasks/{id}', function (Request $request, $id) {
    $result = new Task()->deleteById($id);
    if($result)
    {
        http_response_code(204);
    }
    else
    {
        http_response_code(404);
    }
}, [new BearerToken()]);

$appInstance->run();