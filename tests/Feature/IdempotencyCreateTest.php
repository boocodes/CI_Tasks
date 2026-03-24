<?php

use Task5\Application\Model\Task;
use PHPUnit\Framework\TestCase;
use Task5\Domain\Enums\TaskStatus;

class IdempotencyCreateTest extends TestCase
{
    private Task $task;

    protected function setUp(): void
    {
        parent::setUp();
        $this->task = new Task();
    }

    public function testMain(): void
    {
        $currentTaskData = $this->task->getAll(null, null);
        $initialTaskDataSize = count($currentTaskData);
        $newTaskData = 
        [
            'title' => 'From test case!',
            'description' => 'Description test case',
            'status' => TaskStatus::NEW->value,
        ];
        $_SERVER['Idempotency-key'] = "12345";
        $result1 = $this->task->add($newTaskData);
        $result2 = $this->task->add($newTaskData);
        $newTaskDataArray = $this->task->getAll(null, null);
        $newTaskDataSize = $initialTaskDataSize + 1;
        $this->assertEquals($newTaskDataSize, count($newTaskDataArray));
        $this->assertEquals($result1, $result2);
    }
}
