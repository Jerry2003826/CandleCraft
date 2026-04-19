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
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Learning Center</h5>
    </div>
    <div class="card-body">
        <?php if ($bookings->isEmpty()): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-book" style="font-size: 48px;"></i>
                <p class="mt-3">You need to book a class first to access learning resources.</p>
                <a href="<?= $this->Url->build(['prefix' => 'Student', 'controller' => 'Courses', 'action' => 'index']) ?>" class="btn btn-sm btn-primary mt-2">Browse Courses</a>
            </div>
        <?php elseif (empty($resources)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-folder" style="font-size: 48px;"></i>
                <p class="mt-3">No learning resources have been uploaded for your classes yet.</p>
            </div>
        <?php else: ?>
            <?php foreach ($bookings as $booking): ?>
                <?php $classResources = $resources[$booking->class_id] ?? null; ?>
                <?php if ($classResources): ?>
                    <div class="card mb-3 border">
                        <div class="card-header bg-light">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-journal-text text-muted"></i>
                                <div>
                                    <small class="text-muted"><?= h($booking->class_entity ? $booking->class_entity->class_code : 'Class') ?></small>
                                    <h6 class="mb-0"><?= h($booking->class_entity && $booking->class_entity->course ? $booking->class_entity->course->course_name : 'Class Resources') ?></h6>
                                </div>
                            </div>
                        </div>
                        <div class="list-group list-group-flush">
                            <?php foreach ($classResources as $resource): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?= h($resource->resource_name) ?></strong>
                                        <?php if ($resource->resource_description): ?>
                                            <p class="text-muted small mb-0"><?= h($resource->resource_description) ?></p>
                                        <?php endif; ?>
                                        <span class="badge bg-secondary mt-1"><?= ucfirst(h($resource->resource_type)) ?></span>
                                    </div>
                                    <div>
                                        <?php if ($resource->resource_type === 'link' && $resource->resource_url): ?>
                                            <a href="<?= h($resource->resource_url) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-primary"><i class="bi bi-box-arrow-up-right"></i> Open</a>
                                        <?php elseif ($resource->file_path): ?>
                                            <a href="<?= $this->Url->build(['action' => 'download', $resource->resource_id]) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-download"></i> Download</a>
                                        <?php else: ?>
                                            <a href="<?= $this->Url->build(['action' => 'view', $resource->resource_id]) ?>" class="btn btn-sm btn-outline-primary">View</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
