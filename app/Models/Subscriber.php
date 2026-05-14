<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Subscriber extends Model
{
    use HasFactory;
    protected $fillable = ['email'];

    public function ads(): BelongsToMany
    {
        return $this
            ->belongsToMany(Ad::class, 'ad_subscriber')
            ->withPivot('token', 'verified_at')
            ->withTimestamps();
    }
}
