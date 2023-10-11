<?php

namespace App\Console\Commands;

use App\Models\Student;
use MailerLite\MailerLite;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class ExportStudentDataToCSV extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:student-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export student data to a CSV file';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $last24Hours = now()->subHours(24);
        $chunkSize = 10;

        $csvFilePath = public_path('csv/students_to_add.csv');
        $csvData = [];

        // Process students created or updated in the last 24 hours
        Student::where(function ($query) use ($last24Hours) {
            $query->where('created_at', '>=', $last24Hours);
        })->chunk($chunkSize, function ($students) use (&$csvData) { // Pass $csvData by reference

            foreach ($students as $student) {

                if (!filter_var(mb_strtolower($student->email), FILTER_VALIDATE_EMAIL)) {
                    Log::notice("Skipping student with invalid email: {$student->email}");
                    continue;
                }

                $normalizedEmail = mb_strtolower($student->email);
                $studentId = $student->id;

                $rowData = [
                    'student_id' => $studentId,
                    'student_email' => $normalizedEmail
                ];

                $csvData[] = $rowData;
                
                Log::info("Student: {$student->email} added to csv successfully.");
            }
        });

        // Append new data to the CSV file
        $csvFile = fopen($csvFilePath, 'a');

        foreach ($csvData as $row) {
            fputcsv($csvFile, $row);
        }
        
        fclose($csvFile);
        
        Log::info("CSV updated successfully.");
    }

}
