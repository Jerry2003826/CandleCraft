<?php
/**
 * @var \App\View\AppView $this
 * @var iterable $bookings
 * @var array $resources
 * @var \App\Model\Entity\Student $student
 */
$this->assign('title', 'Learning Center');
?>

<div class="card">
    <div class="card-header">
        <h3>Learning Center</h3>
    </div>
    <div class="card-body">
        <?php if ($bookings->isEmpty()): ?>
            <div class="empty-state">
                <div class="icon">&#x1F4DA;</div>
                <p>You need to book a class first to access learning resources.</p>
                <a href="<?= $this->Url->build(['prefix' => false, 'controller' => 'Courses', 'action' => 'index']) ?>" class="btn btn-sm btn-primary" style="margin-top: 10px;">Browse Courses</a>
            </div>
        <?php elseif (empty($resources)): ?>
            <div class="empty-state">
                <div class="icon">&#x1F4C1;</div>
                <p>No learning resources have been uploaded for your classes yet.</p>
            </div>
        <?php else: ?>
            <?php foreach ($bookings as $booking): ?>
                <?php $classResources = $resources[$booking->class_id] ?? null; ?>
                <?php if ($classResources): ?>
                    <section class="portal-class-card" style="margin-bottom: 16px;">
                        <div class="portal-class-card__header">
                            <div>
                                <p class="portal-class-card__eyebrow">
                                    <?= h($booking->class_entity ? $booking->class_entity->class_code : 'Class') ?>
                                </p>
                                <h3>
                                    <?= h($booking->class_entity && $booking->class_entity->course
                                        ? $booking->class_entity->course->course_name
                                        : 'Class Resources') ?>
                                </h3>
                            </div>
                        </div>

                        <div class="resource-list">
                            <?php foreach ($classResources as $resource): ?>
                                <div class="portal-status-row" style="border-bottom: 1px solid #eee; padding: 10px 0;">
                                    <div>
                                        <strong><?= h($resource->resource_name) ?></strong>
                                        <p style="color: #666; font-size: 0.9em; margin: 4px 0;">
                                            <?= h($resource->resource_description ?? '') ?>
                                        </p>
                                        <span class="badge badge-<?= h($resource->resource_type === 'video' ? 'confirmed' : ($resource->resource_type === 'document' ? 'pending' : 'new-messages')) ?>">
                                            <?= ucfirst(h($resource->resource_type)) ?>
                                        </span>
                                    </div>
                                    <div>
                                        <?php if ($resource->resource_type === 'link' && $resource->resource_url): ?>
                                            <a href="<?= h($resource->resource_url) ?>" target="_blank" class="btn btn-sm btn-primary">Open Link</a>
                                        <?php elseif ($resource->file_path): ?>
                                            <a href="<?= $this->Url->build('/' . $resource->file_path) ?>" target="_blank" class="btn btn-sm btn-primary">Download</a>
                                        <?php else: ?>
                                            <a href="<?= $this->Url->build(['action' => 'view', $resource->resource_id]) ?>" class="btn btn-sm btn-primary">View</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
