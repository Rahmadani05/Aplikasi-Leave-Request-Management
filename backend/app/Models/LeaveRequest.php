<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    protected $fillable = [
        'user_id', 'leave_type_id', 'start_date', 'end_date', 'total_days', 
        'reason', 'status', 'responded_by', 'admin_notes', 'responded_at', 'deleted_by'
    ];

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }
}
