<?php

namespace Task5\Infrastructure\Response;


class Response
{

    public function __contruct()
    {
    }
    public function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        echo json_encode($data);
    }
    public function jsonWithValidate(array $data, int $statusCode = 200): void
    {
        if(json_validate(json_encode($data))) {
            http_response_code($statusCode);
            echo json_encode($data);
        }
        else
        {
            http_response_code(400);
            echo json_encode(json_last_error_msg());
        }
    }
}