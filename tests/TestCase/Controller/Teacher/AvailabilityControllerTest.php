<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Teacher;

use App\Test\TestCase\Controller\AppIntegrationTestCase;
use Cake\Datasource\FactoryLocator;

class AvailabilityControllerTest extends AppIntegrationTestCase
{
    public function testInvalidSlotDoesNotDeleteExistingAvailability(): void
    {
        $this->loginAsTeacher();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $table = FactoryLocator::get('Table')->get('TeacherAvailabilities');
        $before = $table->find()->where(['teacher_id' => 1])->count();

        $this->post('/teacher/availability/edit', [
            'slots' => [
                [
                    'day_of_week' => 1,
                    'start_time' => '09:00',
                    'end_time' => '10:00',
                ],
                [
                    'day_of_week' => 2,
                    'start_time' => '15:00',
                    'end_time' => '14:00',
                ],
            ],
        ]);

        $after = $table->find()->where(['teacher_id' => 1])->count();

        $this->assertResponseOk();
        $this->assertSame($before, $after);
        $this->assertTrue($table->exists(['id' => 1, 'teacher_id' => 1]));
    }
}
