<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Consumer;

use App\Test\TestCase\Controller\AppIntegrationTestCase;

class CoursesControllerTest extends AppIntegrationTestCase
{
    public function testAlreadyBookedClassShowsBookedStatus(): void
    {
        $this->loginAsStudent();

        $this->get('/consumer/courses');

        $this->assertResponseOk();
        $this->assertResponseContains('已订购');
        $this->assertResponseContains('Introduction to Pottery');
    }
}
