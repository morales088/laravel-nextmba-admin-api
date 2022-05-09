<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StudentcourseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = \App\Models\Student::get();

        foreach ($user as $key => $value) {
            
            \App\Models\Studentcourse::create(
                [
                    'studentId' => $value->id,
                    'courseId' => \App\Models\Course::first()->id,
                ]
            );
            
            \App\Models\Studentcourse::create(
                [
                    'studentId' => $value->id,
                    'courseId' => \App\Models\Course::skip(1)->first()->id,
                ]
            );

            // \App\Models\Studentcourse::factory(2)->create(['userId' => $value->id]);
        }
    }
}
