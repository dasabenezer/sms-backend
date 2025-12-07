<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'subdomain',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'pincode',
        'board',
        'affiliation_number',
        'logo',
        'subscription_plan',
        'trial_ends_at',
        'subscription_ends_at',
        'max_students',
        'is_active',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'subscription_ends_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function students()
    {
        return $this->hasMany(Student::class);
    }

    public function staff()
    {
        return $this->hasMany(Staff::class);
    }

    public function isOnTrial()
    {
        return $this->trial_ends_at && now()->lte($this->trial_ends_at);
    }

    public function hasActiveSubscription()
    {
        return $this->subscription_ends_at && now()->lte($this->subscription_ends_at);
    }

    public function isActive()
    {
        return $this->is_active && ($this->isOnTrial() || $this->hasActiveSubscription());
    }
}
