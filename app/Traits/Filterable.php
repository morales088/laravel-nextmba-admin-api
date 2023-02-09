<?php

namespace App\Traits;

use Carbon\Carbon;

trait Filterable {

  public function filterData($query, $status, $dateRange, $email, $column) {
    
    // Filter by column and its status
    if ($status !== null) {
      $query = $query->where($column, $status);
    }

    // Filter by date range
    if ($dateRange) {
      $startDate = Carbon::now()->subDays($dateRange)->startOfDay();
      $query = $query->whereBetween('created_at', [$startDate, Carbon::now()]);
    }

    // Filter by email
    if ($email) {
      $query = $query->whereHas('student', function ($q) use ($email) {
        $q->where('email', 'like', '%'.$email.'%');
      });
    }
    
    return $query;
  }
}