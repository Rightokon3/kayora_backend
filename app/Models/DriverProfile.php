<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class DriverProfile extends Model
{
    protected $fillable = [
        'driver_id', 'middle_name', 'gender', 'date_of_birth', 'marital_status',
        'alternative_phone', 'home_address', 'city', 'state',
        'emergency_contact_name', 'emergency_contact_phone', 'profile_image',
        'employee_id', 'blood_group', 'genotype', 'national_id_number',
        'employment_date', 'department', 'branch', 'supervisor',
        'license_number', 'license_expiry', 'license_front_image',
        'license_back_image', 'national_id_image', 'years_of_experience',
        'previous_employer', 'additional_notes',
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }
}
