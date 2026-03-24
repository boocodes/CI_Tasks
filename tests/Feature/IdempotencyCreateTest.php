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
    public function tearDown(): void
    {
        $this->clearData();
        parent::tearDown();
    }
    private function clearData(): void
    {
        $tasksFile = __DIR__ . '/../../tasks.json';
        if (file_exists($tasksFile)) {
            file_put_contents($tasksFile, json_encode([]));
        }
        $idempotencyFile = __DIR__ . '/../../idempotency.json';
        if (file_exists($idempotencyFile)) {
            file_put_contents($idempotencyFile, json_encode([]));
        }
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

        
        $expectedSize = $initialTaskDataSize + 1;
        $this->assertEquals($expectedSize, count($newTaskDataArray));
        $this->assertEquals($result1, $result2);
    }
}
