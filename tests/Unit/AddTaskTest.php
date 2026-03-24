<?php 

use Task5\Application\Model\Task;
use PHPUnit\Framework\TestCase;
use Task5\Domain\Enums\TaskStatus;

class AddTaskTest extends TestCase
{
    private Task $task;

    protected function setUp(): void
    {
        parent::setUp();
        $this->task = new Task();
    }

    public function testMain(): void
    {
        $newTaskData = 
        [
            'title' => 'From test case!',
            'description' => 'Description test case',
            'status' => TaskStatus::NEW->value,
        ];
        $result = $this->task->add($newTaskData);
        $this->assertEquals($newTaskData['title'], $result['title']);
    }
}
