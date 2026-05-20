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

    public function testIndexRendersAvailabilityDateRangeFields(): void
    {
        $this->loginAsTeacher();

        $this->get('/teacher/availability');

        $this->assertResponseOk();
        $this->assertResponseContains('name="slots[0][valid_from]"');
        $this->assertResponseContains('name="slots[0][valid_until]"');
        $this->assertResponseContains('Set the date range this weekly availability applies to.');
    }

    public function testAvailabilityDateRangePersistsWithSlot(): void
    {
        $this->loginAsTeacher();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $table = FactoryLocator::get('Table')->get('TeacherAvailabilities');

        $this->post('/teacher/availability/edit', [
            'slots' => [
                [
                    'day_of_week' => 3,
                    'start_time' => '13:00',
                    'end_time' => '15:00',
                    'valid_from' => '2026-06-01',
                    'valid_until' => '2026-08-31',
                ],
            ],
        ]);

        $this->assertRedirect('/teacher/availability');
        $slot = $table->find()
            ->where(['teacher_id' => 1, 'day_of_week' => 3])
            ->firstOrFail();

        $this->assertSame('2026-06-01', $slot->valid_from?->format('Y-m-d'));
        $this->assertSame('2026-08-31', $slot->valid_until?->format('Y-m-d'));
    }

    public function testBlockedDateRejectsUnrealisticFutureYear(): void
    {
        $this->loginAsTeacher();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $blockedDates = FactoryLocator::get('Table')->get('TeacherBlockedDates');
        $before = $blockedDates->find()->where(['teacher_id' => 1])->count();

        $this->post('/teacher/availability/add-blocked-date', [
            'blocked_date' => '2101-01-01',
            'reason' => 'Impossible calendar year',
        ]);

        $after = $blockedDates->find()->where(['teacher_id' => 1])->count();

        $this->assertRedirectContains('/teacher/availability?tab=blocked');
        $this->assertSame($before, $after);
    }
}
