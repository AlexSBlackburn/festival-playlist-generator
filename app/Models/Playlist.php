<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Playlist extends Model
{
    use HasFactory;

    protected function bands(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => collect(json_decode($value, true)),
            set: fn ($value) => $value->toJson(),
        );
    }
}
