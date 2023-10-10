<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Models\Studentcourse;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExportExpiredStudentCourse extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'students:check-course';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for the student courses if expired add it to csv for removing in mailerlite';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $csvFilePath = public_path('csv/students_to_unassign.csv');
        $csvData = [];

        $expiredStudentCourses = Studentcourse::where('expirationDate', '<', now())
            ->where('expirationDate', '>=', now()->subDay()) // Check for records within the last 24 hours
            ->where('status', 0)
            ->get();

        foreach($expiredStudentCourses as $studentCourse) {

            $student = Student::where('id', $studentCourse->studentId)->first();

            $rowData = [
                'student_id' => $student->id,
                'student_email' => $student->email,
                'student_status' => $student->status == 0 ? 'deactivated': 'active',
                'student_course_id' => $studentCourse->courseId
            ];

            $csvData[] = $rowData;
        }

        // Append new data to the CSV file
        $csvFile = fopen($csvFilePath, 'a');

        foreach ($csvData as $row) {
            fputcsv($csvFile, $row);
        }
        
        fclose($csvFile);
        
        Log::info("CSV updated successfully.");
    }
}
