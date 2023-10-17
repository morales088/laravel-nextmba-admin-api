<?php

namespace App\Console\Commands;

use App\Models\Student;
use MailerLite\MailerLite;
use App\Models\SubscriberGroup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AddStudentToGroups extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'students:add-to-groups';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Upsert student and add to list/groups';

    protected $mailerLite; // Define the property to hold the mailing service instance

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
        // Load and process the CSV file
        $csvFilePath = public_path('csv/students_to_add.csv');

        // Define a new file to store the temp data
        $remainingDataFile = public_path('csv/students_to_add_temp.csv');
        $remainingCsvFile = fopen($remainingDataFile, 'w');

        $csvFile = fopen($csvFilePath, 'r+');

        while (($line = fgets($csvFile)) !== false) {
            $data = str_getcsv(trim($line));

            if ($data === false) {
                break; // End of file
            }

            // Extract data from each row of the CSV
            $studentId = $data[0];
            $studentEmail = $data[1];

            // Retrieve student and unique course associated
            $student = Student::where('id', $studentId)->first();

            if (!filter_var(mb_strtolower($studentEmail), FILTER_VALIDATE_EMAIL)) {
                Log::notice("Skipping invalid email: {$studentEmail}");
                continue;
            }

            $normalizedEmail = mb_strtolower($studentEmail);

            try {
                // Upsert a new subscriber
                $studentSubscriber = ['email' => $normalizedEmail];
                $this->mailerLite->subscribers->create($studentSubscriber);

                // Find the subscriber from mailer lite
                $subscriber = $this->mailerLite->subscribers->find($studentSubscriber['email']);
                $subscriberId = $subscriber['body']['data']['id'];

                // Retrieve unique active course IDs associated with the student
                $uniqueActiveCourses = $student->courses->where('status', 1)->unique('courseId');

                if ($student->status != 0) {
                    foreach ($uniqueActiveCourses as $course) {
                        $studentCourseGroup = SubscriberGroup::where('course_id', $course->courseId)->first();

                        // Add to mailerlite group
                        $this->mailerLite->groups->assignSubscriber(
                            $studentCourseGroup->mailerlite_group_id,
                            $subscriberId
                        );

                        // Check if student account type is pro account
                        if ($student->account_type == 3) {
                            $this->mailerLite->groups->assignSubscriber(
                                env('PRO_ACCOUNTS_GROUP_ID'), $subscriberId
                            );
                        }
                    }
                } else {
                    // Remove student from subscriber list
                    $this->mailerLite->subscribers->delete($subscriberId);
                    Log::info("Removed from subscribers: $studentEmail");
                }

                // Log messages for each processed student
                Log::info("Processed student: $studentEmail");
                
            } catch (\Exception $e) {
                // Handle any exceptions that occur during processing
                Log::error("Error processing student: $studentEmail - " . $e->getMessage());
            }

            // Add a delay of approximately 1 minute (60 seconds)
            sleep(10);
        }

        // Close both CSV files
    fclose($csvFile);
    fclose($remainingCsvFile);

    // Replace the original CSV file with the contents of the remaining data file
    if (file_exists($remainingDataFile)) {
        rename($remainingDataFile, $csvFilePath);
    }

        Log::info("Processed all students in the list.");
    }


}
