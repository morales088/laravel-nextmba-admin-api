<?php

namespace App\Services;

use App\Models\Student;
use Illuminate\Support\Facades\File;

class StudentService
{
  public static function addToCsv($studentId) {

    $student = Student::where('id', $studentId)->first();

    $csvData = [
      $student->id,
      $student->email
    ];

    $csvRow = implode(',', $csvData);

    $csvFilePath = public_path('csv/students_to_add.csv');

    if (!File::exists($csvFilePath)) {
      $csvFile = fopen($csvFilePath, 'w'); // Open the CSV file for writing
      fclose($csvFile);
    }

    // Append the student data to the CSV file
    $csvFile = fopen($csvFilePath, 'a'); // Open the CSV file for appending
    fwrite($csvFile, $csvRow . "\n"); // Append the student data as a new row
    fclose($csvFile);
  }
}