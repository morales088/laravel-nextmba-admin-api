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
        $this->mailerLite = new MailerLite(['api_key' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiI0IiwianRpIjoiY2JiNTM0MWFiMGIzNjkzZjliOTE5ODVlOWZlODg2MDJlMmMyNGRhZWFiNDk1ZDVkZDAzOTdlY2JmOGY5ODFlN2EwNjJhNTcyZTYwYWI3MmQiLCJpYXQiOjE2OTUzMTMxNzMuMjk4NDYzLCJuYmYiOjE2OTUzMTMxNzMuMjk4NDY2LCJleHAiOjQ4NTA5ODY3NzMuMjkzNTg3LCJzdWIiOiI2MzU3OTQiLCJzY29wZXMiOltdfQ.HMt8ILdawxH4Rtx4m3ulJReRcwQzBZ1SG4dpZvAcOMxGBkaxPMfmaCrE9SZbkCJxKmeDL2B8EqTf4qnzxI5qOlnqUgmPfgXYxFakc40klKXWLjgnrdQL7_dkha2iInRDOWVIxiH9zj8GZ-NgIWvRPzg5JqHRwtaDLsg1pq7tuRSpK_UH26UlxVF8jjwDgKpk3Wo1Xz9rvRq6p8xR5Vz-hXm0rSXDvpoME-0A0_lstpsoU93QD6YGbwBSbFx4TGLSr8DGMz1Z3jiGEFCO9V42I5k_0VcgOpwPuowHCaOkCaFd9MDit_T-9W_UE_7-gmY_OhWfCuSrDtBA0wKBkfN7pWzDH2KDnFhzSlniH5wOYGVgVCR80mmIHRNg7J8jVhD2PZrr9hfo7RYiNbNckibRH800i352Z-MeF9bzF5f0rAkHQyd26HOjUCOwBUGFOCjKD3-SU_vxZMGqh3JtFiZfhpTcoCWFPMMprWhRrlKYlprwUBXVEr8NStGN5p7dYlQrV3z_jMq7wZhBu3KR45Q72qEFDJEbxmNlG1sJqYy0sl3XlaQcud3COWdfL_hr0eW3d0ZQPB50Z4E9IEeFjrJyf1vBoKmhEiW5ykK0sKSHhKqTWIWLiLhgQ8wkvm4voCv1bsB1efoubusvkvmY2ECnf_YLqzP0-vcHIXlCr6llnvc']);
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $chunkSize = 10;
        // $lastSevenDays = now()->subDay(30);
        
        $csvData = []; // Initialize an array to store CSV data
        // $students = Student::limit(10)->get();
        
        Student::limit(20)->chunk($chunkSize, function ($students) use (&$csvData) { // Pass $csvData by reference

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
                        'student_status' => $student->status,
                        'student_course_id' => $course->courseId,
                        'student_course_status' => $course->status,
                    ];

                    // Check if the course has an expiration date and if it's expired
                    if ($course->expirationDate && now()->isAfter($course->expirationDate)) {
                        $rowData['student_course_expiration'] = 1;
                    } else {
                        $rowData['student_course_expiration'] = 0;
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

        // Write data rows
        foreach ($csvData as $row) {
            fputcsv($csvFile, $row);
        }
        
        fclose($csvFile);
        
        Log::info("CSV file {$csvFileName} created successfully.");
    }

}
