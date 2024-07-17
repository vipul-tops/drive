<?php

namespace Googledrive\Uploadcsv\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoogleRefToken extends Model
{
    use HasFactory;

    protected $table = 'grefresh_tokens';

    protected $fillable = ['access_token', 'refresh_token', 'expires_at'];
}
    