<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mechanic extends Model
{
    protected $fillable = ['name', 'specialty', 'experience', 'photo', 'status'];

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function feedback(): HasMany
    {
        return $this->hasMany(Feedback::class);
    }
}
