<?php

namespace App\Services;

use App\Models\Student;
use MailerLite\MailerLite;
use App\Models\SubscriberGroup;

class MailerLiteService
{
  protected $mailerLite;

  public function __construct()
  {
    $this->mailerLite = new MailerLite(['api_key' => env('MAILERLITE_API_KEY')]);
  }

  public function createGroup($course)
  {
    $data = [
      'name' => $course->name .= ' Students',
    ];
    
    $createdGroup = $this->mailerLite->groups->create($data);

    $groupId = $createdGroup['body']['data']['id'];
    $groupName = $createdGroup['body']['data']['name'];

    $subscriber_group = SubscriberGroup::create([
      'course_id' => $course->id,
      'mailerlite_group_name' => $groupName,
      'mailerlite_group_id' => $groupId
    ]);


    return true;

  }

  // public function addSubscriber($email) {
  //   $subscriber = $this->mailerLite->subscribers->create(['email' => $email]);
  //   // dd($subscriber);
    
  //   if ($subscriber) {
  //     $this->addSubscriberToGroup($email);
  //   }
  // }

  // public function addSubscriberToGroup($email)
  // {
  //   // $email = 'anointedtoilet@gmail.com';
  //   // $email = 'jaymel.tapel@gmail.com';
  //       // $this->mailerLiteService->createGroup();
  //   // $this->mailerLiteService->addSubscriber('anointedtoilet@gmail.com');
  //   // $this->mailerLiteService->addSubscriberToGroup();

  //   // create a new subscriber
  //   $subscriber = [
  //     'email' => $email,
  //     // include additional subscriber fields here
  //   ];

  //   // upsert subscriber
  //   $this->mailerLite->subscribers->create($subscriber);
  //   // fetch subscriber
  //   $subscriber = $this->mailerLite->subscribers->find($subscriber['email']);
  //   $subscriberId = $subscriber['body']['data']['id'];

  //   // Add the subscriber to the specified group
  //   $groupId = env('TEST_GROUP_ID');
  //   $this->mailerLite->groups->assignSubscriber($groupId, $subscriberId);

  //   return true;
  // }

  public function addSubscriberToGroup($email) {

    $student = Student::with('courses')->where('email', $email)->first();

    if ($student) {
      // Retrieve unique course IDs associated with the student
      $uniqueCourseIds = $student->courses->pluck('courseId')->unique()->values()->toArray();

      $subscriber = ['email' => $student->email];

      // Retrieve all subscriber group records based on course IDs
      $allGroupCourses = SubscriberGroup::whereIn('course_id', $uniqueCourseIds)->get();
      
      foreach ($allGroupCourses as $group) {
          // Assign the subscriber email to the group
          $this->mailerLite->groups->assignSubscriber(
            $group->mailerlite_group_id, $subscriber['email']
          );
      }

      if ($student->account_type === 3) {
        $this->mailerLite->groups->assignSubscriber(
          env('PRO_ACCOUNTS_GROUP_ID'), $subscriber['email']
        );
      }

    } else {
      Log::error("Student with email $email not found.");
    }
  }

}