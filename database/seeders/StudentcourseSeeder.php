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
        $user = \App\Models\User::where('role_id', '=', 2)->get();

        foreach ($user as $key => $value) {

            // dd($value->id);
            
            \App\Models\Studentcourse::create(
                [
                    'userId' => $value->id,
                    'courseId' => \App\Models\Course::first()->id,
                ]
            );
            
            \App\Models\Studentcourse::create(
                [
                    'userId' => $value->id,
                    'courseId' => \App\Models\Course::skip(1)->first()->id,
                ]
            );

            // \App\Models\Studentcourse::factory(2)->create(['userId' => $value->id]);
        }
    }
}
