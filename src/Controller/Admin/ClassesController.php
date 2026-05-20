<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Model\Table\ClassesTable;
use DateTimeImmutable;
use Throwable;

class ClassesController extends AppController
{
    private const DEFAULT_DURATION_MINUTES = 120;

    private const LOCATION_OPTIONS = [
        'Studio A' => 'Studio A',
        'Studio B' => 'Studio B',
        'Room A' => 'Room A',
        'Room B' => 'Room B',
    ];

    /**
     * Index.
     */
    public function index(): void
    {
        $classesTable = $this->fetchTable('Classes');
        $query = $classesTable->find()
            ->contain(['Courses', 'Teachers', 'Bookings'])
            ->orderBy(['Classes.start_datetime' => 'DESC']);

        $search = $this->request->getQuery('search');
        if ($search) {
            $query->where([
                'OR' => [
                    'Classes.class_code LIKE' => "%{$search}%",
                    'Courses.course_name LIKE' => "%{$search}%",
                    'Teachers.teacher_name LIKE' => "%{$search}%",
                    'Classes.location LIKE' => "%{$search}%",
                ],
            ]);
        }

        $status = $this->request->getQuery('status');
        if ($status && in_array($status, ['scheduled', 'ongoing', 'completed', 'cancelled', 'full'])) {
            $query->where(['Classes.class_status' => $status]);
        }

        $classes = $this->paginate($query, ['limit' => 20]);

        $this->set(compact('classes', 'status', 'search'));
    }

    /**
     * View.
     *
     * @param mixed $id Id.
     */
    public function view(?string $id = null): void
    {
        $classesTable = $this->fetchTable('Classes');
        $class = $classesTable->get($id, contain: ['Courses', 'Teachers', 'Bookings' => ['Students']]);
        $this->set('class', $class);
    }

    /**
     * Add.
     *
     * @return mixed
     */
    public function add()
    {
        $classesTable = $this->fetchTable('Classes');
        $class = $classesTable->newEmptyEntity();
        $courseEntities = $this->fetchActiveCourses($classesTable);

        if ($this->request->is('post')) {
            $class = $classesTable->patchEntity($class, $this->buildClassPayload((array)$this->request->getData(), $classesTable));
            if ($classesTable->save($class)) {
                $this->Flash->success(__(
                    'The class has been saved.',
                ));

                $referer = $this->request->referer(true);
                if ($referer && str_contains($referer, 'availability')) {
                    return $this->redirect(['action' => 'availability']);
                }

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__(
                'The class could not be saved. Please try again.',
            ));
        }

        $courses = $this->buildCourseOptions($courseEntities);
        $courseDurations = $this->buildCourseDurationMap($classesTable, $courseEntities);
        $teachers = $classesTable->Teachers->find('list', keyField: 'teacher_id', valueField: 'teacher_name')
            ->where(['teacher_status' => 'active'])
            ->orderBy(['teacher_name' => 'ASC'])
            ->all();

        $locationOptions = self::LOCATION_OPTIONS;

        $this->set(compact('class', 'courses', 'teachers', 'locationOptions', 'courseDurations'));
    }

    /**
     * Edit.
     *
     * @param mixed $id Id.
     * @return mixed
     */
    public function edit(?string $id = null)
    {
        $classesTable = $this->fetchTable('Classes');
        $class = $classesTable->get($id);
        $courseEntities = $this->fetchActiveCourses($classesTable);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $class = $classesTable->patchEntity($class, $this->buildClassPayload((array)$this->request->getData(), $classesTable, $class));
            if ($classesTable->save($class)) {
                $this->Flash->success(__(
                    'The class has been saved.',
                ));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__(
                'The class could not be saved. Please try again.',
            ));
        }

        $courses = $this->buildCourseOptions($courseEntities);
        $courseDurations = $this->buildCourseDurationMap($classesTable, $courseEntities);
        if ($class->course_id !== null) {
            $courseDurations[(int)$class->course_id] = $this->resolveCourseDurationMinutes(
                $classesTable,
                (int)$class->course_id,
                $class,
            );
        }
        $teachers = $classesTable->Teachers->find('list', keyField: 'teacher_id', valueField: 'teacher_name')
            ->where(['teacher_status' => 'active'])
            ->orderBy(['teacher_name' => 'ASC'])
            ->all();

        $locationOptions = self::LOCATION_OPTIONS;

        $this->set(compact('class', 'courses', 'teachers', 'locationOptions', 'courseDurations'));
    }

    /**
     * Availability.
     *
     * @return mixed
     */
    public function availability()
    {
        $classesTable = $this->fetchTable('Classes');

        $weekOffset = (int)($this->request->getQuery('week') ?? 0);
        $monday = new DateTimeImmutable('monday this week');
        $monday = $monday->modify("{$weekOffset} weeks");

        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $days[] = $monday->modify("+{$i} days");
        }

        $startDate = $monday->format('Y-m-d 00:00:00');
        $endDate = $days[6]->format('Y-m-d 23:59:59');

        $classes = $classesTable->find()
            ->contain(['Courses', 'Teachers', 'Bookings'])
            ->where([
                'Classes.start_datetime >=' => $startDate,
                'Classes.start_datetime <=' => $endDate,
            ])
            ->orderBy(['Classes.start_datetime' => 'ASC'])
            ->all();

        $classesByDay = [];
        foreach ($days as $day) {
            $classesByDay[$day->format('Y-m-d')] = [];
        }
        foreach ($classes as $class) {
            $dayKey = $class->start_datetime->format('Y-m-d');
            if (isset($classesByDay[$dayKey])) {
                $classesByDay[$dayKey][] = $class;
            }
        }

        $allCourses = $this->fetchActiveCourses($classesTable);
        $courses = $this->buildCourseOptions($allCourses);
        $courseDurations = $this->buildCourseDurationMap($classesTable, $allCourses);
        $teachers = $classesTable->Teachers->find('list', keyField: 'teacher_id', valueField: 'teacher_name')
            ->where(['teacher_status' => 'active'])
            ->orderBy(['teacher_name' => 'ASC'])
            ->all();

        $scheduledCourseIds = [];
        foreach ($classes as $class) {
            $scheduledCourseIds[$class->course_id] = true;
        }

        $locationOptions = self::LOCATION_OPTIONS;

        $this->set(compact(
            'classesByDay',
            'days',
            'courses',
            'teachers',
            'weekOffset',
            'allCourses',
            'scheduledCourseIds',
            'locationOptions',
            'courseDurations',
        ));
    }

    /**
     * Delete.
     *
     * @param mixed $id Id.
     * @return mixed
     */
    public function delete(?string $id = null)
    {
        $this->request->allowMethod(['post', 'delete']);

        $classesTable = $this->fetchTable('Classes');
        $class = $classesTable->get($id);
        if ($classesTable->delete($class)) {
            $this->Flash->success(__(
                'The class has been deleted.',
            ));
        } else {
            $this->Flash->error(__(
                'The class could not be deleted. Please try again.',
            ));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Build class payload.
     *
     * @param mixed $data Data.
     * @param mixed $classesTable Classestable.
     * @param mixed $existingClass Existingclass.
     */
    private function buildClassPayload(array $data, ClassesTable $classesTable, ?object $existingClass = null): array
    {
        $data['class_name'] = trim((string)($data['class_name'] ?? ''));
        $data['location'] = trim((string)($data['location'] ?? ''));
        $data['notes'] = trim((string)($data['notes'] ?? '')) ?: null;
        $selectedCourseId = isset($data['course_id']) ? (int)$data['course_id'] : null;
        $existingCourseId = $existingClass?->course_id !== null ? (int)$existingClass->course_id : null;
        $shouldGenerateCode = $existingClass === null
            || $selectedCourseId === null
            || $selectedCourseId !== $existingCourseId
            || trim((string)($data['class_code'] ?? '')) === '';

        if ($selectedCourseId && $shouldGenerateCode) {
            $data['class_code'] = $this->generateClassCode(
                $classesTable,
                $selectedCourseId,
                $existingClass?->class_id ? (int)$existingClass->class_id : null,
            );
        }

        if ($selectedCourseId && !empty($data['start_datetime'])) {
            $durationMinutes = $this->resolveCourseDurationMinutes($classesTable, $selectedCourseId, $existingClass);
            $calculatedEnd = $this->calculateEndDateTime((string)$data['start_datetime'], $durationMinutes);
            if ($calculatedEnd !== null) {
                $data['end_datetime'] = $calculatedEnd;
            }
        }

        return $data;
    }

    /**
     * @return \Cake\Collection\CollectionInterface<int, object>
     */
    private function fetchActiveCourses(ClassesTable $classesTable)
    {
        return $classesTable->Courses->find()
            ->where(['is_active' => true])
            ->orderBy(['course_name' => 'ASC'])
            ->all();
    }

    /**
     * @param iterable<object> $courseEntities
     * @return array<int, string>
     */
    private function buildCourseOptions(iterable $courseEntities): array
    {
        $options = [];
        foreach ($courseEntities as $course) {
            $options[(int)$course->course_id] = (string)$course->course_name;
        }

        return $options;
    }

    /**
     * @param iterable<object> $courseEntities
     * @return array<int, int>
     */
    private function buildCourseDurationMap(ClassesTable $classesTable, iterable $courseEntities): array
    {
        $durations = [];
        foreach ($courseEntities as $course) {
            $durations[(int)$course->course_id] = $this->resolveCourseDurationMinutes($classesTable, (int)$course->course_id);
        }

        return $durations;
    }

    /**
     * Resolve course duration minutes.
     *
     * @param mixed $classesTable Classestable.
     * @param mixed $courseId Courseid.
     * @param mixed $existingClass Existingclass.
     */
    private function resolveCourseDurationMinutes(
        ClassesTable $classesTable,
        int $courseId,
        ?object $existingClass = null,
    ): int {
        if ($existingClass !== null && (int)$existingClass->course_id === $courseId) {
            $existingDuration = $this->extractDurationMinutes(
                $existingClass->start_datetime ?? null,
                $existingClass->end_datetime ?? null,
            );
            if ($existingDuration !== null) {
                return $existingDuration;
            }
        }

        $latestClass = $classesTable->find()
            ->select(['course_id', 'start_datetime', 'end_datetime'])
            ->where(['Classes.course_id' => $courseId])
            ->orderBy(['Classes.start_datetime' => 'DESC'])
            ->first();

        if ($latestClass !== null) {
            $durationMinutes = $this->extractDurationMinutes($latestClass->start_datetime ?? null, $latestClass->end_datetime ?? null);
            if ($durationMinutes !== null) {
                return $durationMinutes;
            }
        }

        $course = $classesTable->Courses->find()
            ->select(['course_id', 'course_type'])
            ->where(['Courses.course_id' => $courseId])
            ->first();

        return $this->defaultDurationMinutesForCourse($course);
    }

    /**
     * Extract duration minutes.
     *
     * @param mixed $start Start.
     * @param mixed $end End.
     */
    private function extractDurationMinutes(mixed $start, mixed $end): ?int
    {
        if (!$start || !$end || !method_exists($start, 'getTimestamp') || !method_exists($end, 'getTimestamp')) {
            return null;
        }

        $duration = (int)round(($end->getTimestamp() - $start->getTimestamp()) / 60);
        if ($duration < 30) {
            return null;
        }

        return min($duration, 480);
    }

    /**
     * Calculate end date time.
     *
     * @param mixed $startInput Startinput.
     * @param mixed $durationMinutes Durationminutes.
     */
    private function calculateEndDateTime(string $startInput, int $durationMinutes): ?string
    {
        try {
            $start = new DateTimeImmutable($startInput);
        } catch (Throwable) {
            return null;
        }

        return $start->modify(sprintf('+%d minutes', max(30, $durationMinutes)))->format('Y-m-d H:i:s');
    }

    /**
     * Default duration minutes for course.
     *
     * @param mixed $course Course.
     */
    private function defaultDurationMinutesForCourse(?object $course): int
    {
        $courseType = strtolower((string)($course->course_type ?? ''));

        return match ($courseType) {
            'pottery', 'knitting' => self::DEFAULT_DURATION_MINUTES,
            default => self::DEFAULT_DURATION_MINUTES,
        };
    }

    /**
     * Generate class code.
     *
     * @param mixed $classesTable Classestable.
     * @param mixed $courseId Courseid.
     * @param mixed $ignoreClassId Ignoreclassid.
     */
    private function generateClassCode(ClassesTable $classesTable, int $courseId, ?int $ignoreClassId = null): string
    {
        $course = $classesTable->Courses->get($courseId);
        $prefix = $this->buildClassCodePrefix($course);

        $query = $classesTable->find()
            ->select(['class_code'])
            ->where(['Classes.class_code LIKE' => $prefix . '-%']);

        if ($ignoreClassId !== null) {
            $query->where(['Classes.class_id !=' => $ignoreClassId]);
        }

        $existingCodes = $query
            ->enableHydration(false)
            ->all()
            ->extract('class_code')
            ->toList();

        $maxSuffix = 0;
        foreach ($existingCodes as $code) {
            if (preg_match('/-(\d{3})$/', (string)$code, $matches) === 1) {
                $maxSuffix = max($maxSuffix, (int)$matches[1]);
            }
        }

        return sprintf('%s-%03d', $prefix, $maxSuffix + 1);
    }

    /**
     * Build class code prefix.
     *
     * @param mixed $course Course.
     */
    private function buildClassCodePrefix(object $course): string
    {
        $typePrefix = match (strtolower((string)($course->course_type ?? ''))) {
            'pottery' => 'POT',
            'knitting' => 'KNT',
            default => strtoupper(substr(preg_replace('/[^A-Za-z]/', '', (string)$course->course_name), 0, 3) ?: 'CLS'),
        };

        $levelPrefix = match (strtolower((string)($course->course_level ?? ''))) {
            'beginner' => 'BEG',
            'intermediate' => 'INT',
            'advanced' => 'ADV',
            'all_levels' => 'ALL',
            default => 'GEN',
        };

        return $typePrefix . '-' . $levelPrefix;
    }
}
