<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\ClassesTable;
use Cake\TestSuite\TestCase;

class ClassesTableTest extends TestCase
{
    protected ClassesTable $Classes;

    protected array $fixtures = [
        'app.Classes',
        'app.Courses',
        'app.Teachers',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('Classes') ? [] : ['className' => ClassesTable::class];
        $this->Classes = $this->getTableLocator()->get('Classes', $config);
    }

    protected function tearDown(): void
    {
        unset($this->Classes);

        parent::tearDown();
    }

    public function testSaveRejectsTeacherTimeClash(): void
    {
        $class = $this->Classes->newEntity([
            'class_code' => 'POT-BEG-001',
            'course_id' => 2,
            'teacher_id' => 1,
            'start_datetime' => '2026-05-01 11:00:00',
            'end_datetime' => '2026-05-01 13:00:00',
            'location' => 'Room A',
            'capacity' => 12,
            'class_status' => 'scheduled',
        ]);

        $this->assertFalse((bool)$this->Classes->save($class));
        $this->assertArrayHasKey('teacher_id', $class->getErrors());
    }

    public function testSaveRejectsLocationTimeClash(): void
    {
        $class = $this->Classes->newEntity([
            'class_code' => 'KNT-BEG-001',
            'course_id' => 2,
            'teacher_id' => 2,
            'start_datetime' => '2026-05-01 10:30:00',
            'end_datetime' => '2026-05-01 11:30:00',
            'location' => 'Studio A',
            'capacity' => 12,
            'class_status' => 'scheduled',
        ]);

        $this->assertFalse((bool)$this->Classes->save($class));
        $this->assertArrayHasKey('location', $class->getErrors());
    }
}
