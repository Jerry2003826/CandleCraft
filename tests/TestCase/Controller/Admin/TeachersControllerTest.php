<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Test\TestCase\Controller\AppIntegrationTestCase;
use Cake\Datasource\FactoryLocator;

class TeachersControllerTest extends AppIntegrationTestCase
{
    public function testDeleteTeacherWithAssignedClassesShowsMeaningfulError(): void
    {
        $this->loginAsAdmin();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->enableRetainFlashMessages();

        $this->post('/admin/teachers/delete/1');

        $this->assertRedirect('/admin/teachers');
        $this->assertFlashMessageContains('cannot be deleted because they have assigned classes');

        $teachers = FactoryLocator::get('Table')->get('Teachers');
        $this->assertTrue($teachers->exists(['teacher_id' => 1]));
    }
}
