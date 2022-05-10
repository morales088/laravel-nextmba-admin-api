<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class StudentModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $Student = \App\Models\Student::get();
        $module = \App\Models\Module::get();
        
        foreach ($Student as $key => $value) {

            foreach ($module as $key2 => $value2) {
                \App\Models\Studentmodule::create(
                    [
                        'studentId' => $value->id, 
                        'moduleId' => $value2->id, 
                        'status' => 1,
                        'remarks' => Str::random(10),
                    ]);
            }

        }
    }
}
