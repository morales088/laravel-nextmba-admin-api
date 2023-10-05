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

    protected $mailerLite; 

    public function __construct()
    {
        parent::__construct();
        $this->mailerLite = new MailerLite(['api_key' => env('MAILERLITE_API_KEY')]);
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $chunkSize = 100;
        // $lastSevenDays = now()->subDay(30);
        
        $csvData = []; // Initialize an array to store CSV data
        
        // Process students created or updated in the last 24 hours
        // Student::where(function ($query) use ($lastSevenDays) {
        //     $query->where('created_at', '>=', $lastSevenDays)
        //     ->orWhere('updated_at', '>=', $lastSevenDays);
        Student::chunk($chunkSize, function ($students) use (&$csvData) { // Pass $csvData by reference

            foreach ($students as $student) {

                if (!filter_var(mb_strtolower($student->email), FILTER_VALIDATE_EMAIL)) {
                    Log::notice("Skipping student with invalid email: {$student->email}");
                    continue;
                }

                if (empty($student->email)) {
                    Log::notice("Skipping student with no email.");
                    continue;
                }

                $normalizedEmail = mb_strtolower($student->email);

                // Retrieve unique course IDs associated with the student
                $uniqueCourses = $student->courses->unique('courseId');

                // Collect data for CSV
                foreach ($uniqueCourses as $course) {
                    $rowData = [
                        'student_email' => $normalizedEmail,
                        'student_account_type' => $student->account_type,
                        'student_status' => $student->account_type,
                        'student_course_id' => $course->courseId,
                        'student_course_status' => $course->status,
                    ];

                    // Check if the course has an expiration date and if it's expired
                    if ($course->expirationDate && now()->isAfter($course->expirationDate)) {
                        $rowData['student_course_status'] = 'expired';
                    } else {
                        $rowData['student_course_status'] = 'active';
                    }

                    $csvData[] = $rowData;
                }
                
                Log::info("Student: {$student->email} processed successfully.");
            }
        });


        $csvDirectory = public_path('csv');
        $csvFileName = 'students_data.csv';
        $csvFilePath = $csvDirectory . '/' . $csvFileName;

        // Create the directory if it doesn't exist
        if (!File::isDirectory($csvDirectory)) {
            File::makeDirectory($csvDirectory, 0755, true);
        }

        $csvFile = fopen($csvFilePath, 'a');

        // Write the header row
        // fputcsv($csvFile, ['student email', 'student account_type', 'student status', 'student courseId', 'student courseId status']);
        
        // Write data rows
        foreach ($csvData as $row) {
            fputcsv($csvFile, $row);
        }
        
        fclose($csvFile);
        
        Log::info("CSV file {$csvFileName} created successfully.");
    }

}
