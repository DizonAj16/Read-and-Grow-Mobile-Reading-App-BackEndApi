<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Grade;
use App\Models\Task;

class GradeTaskSeeder extends Seeder
{
    public function run(): void
    {
        $grades = [
            ['name' => 'English 1', 'level' => 1, 'tasks' => [1, 2]],
            ['name' => 'English 2', 'level' => 2, 'tasks' => [3]],
            ['name' => 'English 3', 'level' => 3, 'tasks' => [4]],
            ['name' => 'English 4', 'level' => 4, 'tasks' => [5, 6, 7, 8, 9]],
            ['name' => 'English 5', 'level' => 5, 'tasks' => [10, 11, 12, 13]],
        ];

        foreach ($grades as $g) {
            $grade = Grade::create(['name' => $g['name'], 'level' => $g['level']]);
            foreach ($g['tasks'] as $t) {
                Task::create([
                    'grade_id' => $grade->id,
                    'title' => "Task $t",
                    'max_attempts' => 3,
                ]);
            }
        }
    }
}

