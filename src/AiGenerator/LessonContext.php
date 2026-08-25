<?php

namespace AIGenerator;

use RuntimeException;

/**
 * Holds the teacher-supplied lesson and teacher instructions that are
 * combined with the template schema when building the AI request.
 */
final class LessonContext
{
    private array $lesson;
    private array $teacher;

    private function __construct(array $lesson, array $teacher)
    {
        $this->lesson = $lesson;
        $this->teacher = $teacher;
    }

    /**
     * Create a lesson context from a lesson.json file.
     *
     * @param string $path Path to the lesson.json file
     * @return self
     * @throws RuntimeException If the file is missing or invalid
     */
    public static function fromFile(string $path): self
    {
        if (!is_file($path)) {
            throw new RuntimeException("Lesson file not found: {$path}");
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException("Unable to read the lesson file: {$path}");
        }

        try {
            $data = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new RuntimeException('The lesson file contains invalid JSON.', 0, $e);
        }

        if (!is_array($data)) {
            throw new RuntimeException('The lesson file must contain a JSON object.');
        }

        return new self(
            $data['lesson'] ?? [],
            $data['teacher'] ?? [],
        );
    }

    public function getLesson(): array
    {
        return $this->lesson;
    }

    public function getTeacher(): array
    {
        return $this->teacher;
    }
}
