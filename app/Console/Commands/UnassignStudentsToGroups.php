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
        $this->mailerLite = new MailerLite(['api_key' => env('MAILERLITE_API_KEY')]);
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
