<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service\Cms;

use App\Service\Cms\LockOutcome;
use App\Service\Cms\SectionLockService;
use Cake\Chronos\Chronos;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;

class SectionLockServiceTest extends TestCase
{
    protected array $fixtures = [
        'app.Users',
        'app.SitePages',
        'app.SiteMedia',
        'app.PageSections',
        'app.PageSectionLocks',
    ];

    private SectionLockService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SectionLockService();
    }

    public function testAcquireOnFreeSection(): void
    {
        $outcome = $this->service->acquire(1, 1);

        $this->assertSame(LockOutcome::ACQUIRED, $outcome->status);
        $this->assertSame(1, $outcome->holderId);
    }

    public function testReAcquireBySameUserReturnsAlreadySelf(): void
    {
        $this->service->acquire(1, 1);
        $second = $this->service->acquire(1, 1);

        $this->assertSame(LockOutcome::ALREADY_SELF, $second->status);
    }

    public function testAcquireByDifferentUserReturnsHeldByOther(): void
    {
        $this->service->acquire(1, 1);
        $second = $this->service->acquire(1, 2);

        $this->assertSame(LockOutcome::HELD_BY_OTHER, $second->status);
        $this->assertSame(1, $second->holderId);
        $this->assertNotNull($second->expiresAt);
    }

    public function testExpiredLockIsAutoReleased(): void
    {
        $this->service->acquire(1, 1);
        $locks = $this->fetchTable('PageSectionLocks');
        $row = $locks->find()->where(['section_id' => 1])->first();
        $row->set('expires_at', DateTime::now()->subMinutes(1));
        $locks->save($row);

        $outcome = $this->service->acquire(1, 2);
        $this->assertSame(LockOutcome::ACQUIRED, $outcome->status);
        $this->assertSame(2, $outcome->holderId);
    }

    public function testHeartbeatExtendsExpiry(): void
    {
        $start = Chronos::create(2026, 5, 11, 12, 0, 0);
        Chronos::setTestNow($start);

        $this->service->acquire(1, 1);
        $locks = $this->fetchTable('PageSectionLocks');
        $original = $locks->find()->where(['section_id' => 1])->first();
        $originalExpiry = $original->get('expires_at')->format('Y-m-d H:i:s');

        Chronos::setTestNow($start->addMinutes(2));
        $extended = $this->service->heartbeat(1, 1);
        $this->assertTrue($extended);

        $reloaded = $locks->find()->where(['section_id' => 1])->first();
        $this->assertGreaterThan($originalExpiry, $reloaded->get('expires_at')->format('Y-m-d H:i:s'));

        Chronos::setTestNow(Chronos::now());
    }

    public function testHeartbeatByNonHolderFails(): void
    {
        $this->service->acquire(1, 1);
        $this->assertFalse($this->service->heartbeat(1, 2));
    }

    public function testReleaseByHolderRemovesLock(): void
    {
        $this->service->acquire(1, 1);
        $this->service->release(1, 1);

        $locks = $this->fetchTable('PageSectionLocks');
        $remaining = $locks->find()->where(['section_id' => 1])->count();
        $this->assertSame(0, $remaining);
    }

    public function testReleaseByNonHolderIsNoop(): void
    {
        $this->service->acquire(1, 1);
        $this->service->release(1, 99);

        $locks = $this->fetchTable('PageSectionLocks');
        $remaining = $locks->find()->where(['section_id' => 1])->count();
        $this->assertSame(1, $remaining);
    }

    public function testForceTakeReplacesHolder(): void
    {
        $this->service->acquire(1, 1);
        $outcome = $this->service->forceTake(1, 2);

        $this->assertSame(LockOutcome::ACQUIRED, $outcome->status);
        $this->assertSame(2, $outcome->holderId);

        $locks = $this->fetchTable('PageSectionLocks');
        $row = $locks->find()->where(['section_id' => 1])->first();
        $this->assertSame(2, (int)$row->get('locked_by_id'));
    }

    public function testCurrentHolderReturnsInfoOrNull(): void
    {
        $this->assertNull($this->service->currentHolder(1));
        $this->service->acquire(1, 1);
        $info = $this->service->currentHolder(1);
        $this->assertNotNull($info);
        $this->assertSame(1, $info->holderId);
    }
}
