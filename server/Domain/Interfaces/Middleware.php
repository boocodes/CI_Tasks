<?php

namespace Task5\Domain\Interfaces;


use Task5\Infrastructure\Request\Request;

interface Middleware
{
    public function run(Request $request): bool;
}