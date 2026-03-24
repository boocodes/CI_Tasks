<?php

use Task5\Application\Model\Task;
use PHPUnit\Framework\TestCase;


class GetTasksTest extends TestCase
{
    private Task $task;

    protected function setUp(): void
    {
        parent::setUp();
        $this->task = new Task();
    }
    public function testMain(): void
    {
        $result = $this->task->getAll(null, null);
        $this->assertIsArray($result);
    }
}
