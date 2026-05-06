<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OsTemplate extends Model
{
    protected $fillable = [
        'name', 'description', 'url', 'filename', 'template_type',
        'proxmox_node', 'proxmox_storage', 'proxmox_volume',
        'proxmox_task_id', 'status', 'error_message',
        'size_bytes', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function isReady(): bool
    {
        return $this->status === 'ready';
    }

    public function isDownloading(): bool
    {
        return in_array($this->status, ['pending', 'downloading']);
    }

    public function formattedSize(): string
    {
        if (!$this->size_bytes) {
            return '—';
        }
        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->size_bytes;
        $i = 0;
        while ($size >= 1024 && $i < 3) {
            $size /= 1024;
            $i++;
        }
        return round($size, 1) . ' ' . $units[$i];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('status', 'ready');
    }
}
