<?php

namespace App\Console\Commands;

use App\Models\Student;
use MailerLite\MailerLite;
use App\Models\SubscriberGroup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UnassignStudentsToGroups extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'students:unassign-groups';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Unassign students to subscriber groups';

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
        try {

            $twentyFourHoursAgo = now()->subHours(24);

            // Get students with updated courses in the last 24 hours
            $studentsWithUpdatedCourses = Student::whereHas('courses', function ($query) use ($twentyFourHoursAgo) {
                $query->where('updated_at', '>=', $twentyFourHoursAgo);
            })->get();

            foreach ($studentsWithUpdatedCourses as $student) {
                sleep(10); // delay per student
                foreach ($student->courses as $course) {
                    if ($course->status === 0) {
                        // Retrieve all subscriber group records based on course IDs
                        $allGroupCourses = SubscriberGroup::where('course_id', $course->courseId)->get();

                        foreach ($allGroupCourses as $group) {

                            if (!filter_var(mb_strtolower($student->email), FILTER_VALIDATE_EMAIL)) {
                                Log::notice("Skipping student with invalid email: {$student->email}");
                                continue;
                            }
    
                            if (empty($student->email)) {
                                Log::notice("Skipping student with no email.");
                                continue;
                            }

                            $normalizedEmail = mb_strtolower($student->email);

                            $subscriber = ['email' => $normalizedEmail];
                            $subscriber = $this->mailerLite->subscribers->find($subscriber['email']);

                            $subscriberId = $subscriber['body']['data']['id'];
                            $groupId = $group->mailerlite_group_id;

                            // Unassign the subscriber from the group
                            $this->mailerLite->groups->unAssignSubscriber($groupId, $subscriberId);
                        }
                    }
                }
                
                Log::info("Student: {$student->email} processed successfully.");
            }

            Log::info('Students have been unassigned to subscriber groups.');

        } catch (\Exception $e) {

            Log::error('An error occurred: ' . $e->getMessage());
        }
        
    }
}
