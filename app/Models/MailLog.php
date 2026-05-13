<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MailLog extends Model
{
    protected $fillable = [
        'template_key',
        'to',
        'subject',
        'html_content',
        'status',
        'error',
    ];
}
