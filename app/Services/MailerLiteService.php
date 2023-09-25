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
    $this->mailerLite = new MailerLite(['api_key' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiI0IiwianRpIjoiY2JiNTM0MWFiMGIzNjkzZjliOTE5ODVlOWZlODg2MDJlMmMyNGRhZWFiNDk1ZDVkZDAzOTdlY2JmOGY5ODFlN2EwNjJhNTcyZTYwYWI3MmQiLCJpYXQiOjE2OTUzMTMxNzMuMjk4NDYzLCJuYmYiOjE2OTUzMTMxNzMuMjk4NDY2LCJleHAiOjQ4NTA5ODY3NzMuMjkzNTg3LCJzdWIiOiI2MzU3OTQiLCJzY29wZXMiOltdfQ.HMt8ILdawxH4Rtx4m3ulJReRcwQzBZ1SG4dpZvAcOMxGBkaxPMfmaCrE9SZbkCJxKmeDL2B8EqTf4qnzxI5qOlnqUgmPfgXYxFakc40klKXWLjgnrdQL7_dkha2iInRDOWVIxiH9zj8GZ-NgIWvRPzg5JqHRwtaDLsg1pq7tuRSpK_UH26UlxVF8jjwDgKpk3Wo1Xz9rvRq6p8xR5Vz-hXm0rSXDvpoME-0A0_lstpsoU93QD6YGbwBSbFx4TGLSr8DGMz1Z3jiGEFCO9V42I5k_0VcgOpwPuowHCaOkCaFd9MDit_T-9W_UE_7-gmY_OhWfCuSrDtBA0wKBkfN7pWzDH2KDnFhzSlniH5wOYGVgVCR80mmIHRNg7J8jVhD2PZrr9hfo7RYiNbNckibRH800i352Z-MeF9bzF5f0rAkHQyd26HOjUCOwBUGFOCjKD3-SU_vxZMGqh3JtFiZfhpTcoCWFPMMprWhRrlKYlprwUBXVEr8NStGN5p7dYlQrV3z_jMq7wZhBu3KR45Q72qEFDJEbxmNlG1sJqYy0sl3XlaQcud3COWdfL_hr0eW3d0ZQPB50Z4E9IEeFjrJyf1vBoKmhEiW5ykK0sKSHhKqTWIWLiLhgQ8wkvm4voCv1bsB1efoubusvkvmY2ECnf_YLqzP0-vcHIXlCr6llnvc']);
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