<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $course = \App\Models\Course::get();

        foreach ($course as $key => $value) {
            \App\Models\Module::factory(1)->create(['courseId' => $value->id]);
        }
    }
}
