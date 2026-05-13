<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MailTemplate extends Model
{
    protected $fillable = [
        'key', 'name', 'subject', 'html_content', 'text_content', 'variables', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'variables' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function render(array $data = []): string
    {
        return preg_replace_callback('/\{\{\s*(\w+)\s*\}\}/', function ($m) use ($data) {
            return $data[$m[1]] ?? $m[0];
        }, $this->html_content);
    }

    public function renderSubject(array $data = []): string
    {
        return preg_replace_callback('/\{\{\s*(\w+)\s*\}\}/', function ($m) use ($data) {
            return $data[$m[1]] ?? $m[0];
        }, $this->subject);
    }
}
