<?php

namespace App\Console\Commands;

use App\Models\Student;
use MailerLite\MailerLite;
use App\Models\SubscriberGroup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AssignStudentsToGroups extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'students:assign-groups';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign students to subscriber groups';

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
        $rateLimit = 60;
        $perMinute = 60;
        $allowedRequests = $rateLimit / $perMinute;
        $chunkSize = 30; // Number of students to process in each chunk

        Student::where('id', '=', '7824')->chunk($chunkSize, function ($students) use ($allowedRequests) {
            $requestsMade = 0;

            foreach ($students as $student) {
                // Check if you've reached the allowed rate limit
                if ($requestsMade >= $allowedRequests) {
                    sleep(3); 
                    $requestsMade = 0; // Reset requests counter
                }

                // Retrieve unique course IDs associated with the student
                $uniqueCourseIds = $student->courses->pluck('courseId')->unique()->values()->toArray();

                // Upsert a new subscriber
                $subscriber = ['email' => $student->email];
                $this->mailerLite->subscribers->create($subscriber);

                $subscriber = $this->mailerLite->subscribers->find($subscriber['email']);
                $subscriberId = $subscriber['body']['data']['id'];

                // Check if student has a pro account
                if ($student->account_type === 3) {
                    $this->mailerLite->groups->assignSubscriber(
                        env('PRO_ACCOUNTS_GROUP_ID'), $subscriberId
                    );
                }

                // Check if student is deactivated
                if ($student->status !== 0) {
                    // Retrieve all subscriber group records based on course IDs
                    $allGroupCourses = SubscriberGroup::whereIn('course_id', $uniqueCourseIds)->get();

                    foreach ($allGroupCourses as $group) {
                        // Assign the subscriber email to the group
                        $this->mailerLite->groups->assignSubscriber($group->mailerlite_group_id, $subscriberId);
                    }

                } else {

                    $this->mailerLite->subscribers->delete($subscriberId);
                }
                // Update the requests counter
                $requestsMade++;

                $this->info("Student: {$student->email} processed successfully.");
            }
        });

        $this->info('All students have been assigned to subscriber groups.');
    }

}
