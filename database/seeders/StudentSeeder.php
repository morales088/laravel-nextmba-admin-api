<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use DB;

class StudentSeeder extends Seeder
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
            \App\Models\Student::factory(1)->create(['userId' => $value->id]);
        }
    }
}
