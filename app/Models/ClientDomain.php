<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClientDomain extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'virtual_machine_id', 'hosting_account_id',
        'domain', 'is_subdomain', 'type', 'target_ip', 'target_port',
        'forward_scheme', 'www_redirect',
        'ssl_enabled', 'npm_proxy_id', 'ssl_certificate_id', 'ssl_expires_at',
        'dns_ok', 'dns_checked_at',
    ];

    protected function casts(): array
    {
        return [
            'is_subdomain'   => 'boolean',
            'www_redirect'   => 'boolean',
            'ssl_enabled'    => 'boolean',
            'dns_ok'         => 'boolean',
            'ssl_expires_at' => 'datetime',
            'dns_checked_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $cd) {
            $cd->is_subdomain ??= self::detectSubdomain($cd->domain);
        });
    }

    /**
     * A domain with 3+ labels is a subdomain (www.example.com treated as apex).
     * Edge case: example.co.uk incorrectly returns true — acceptable for FR market.
     */
    public static function detectSubdomain(string $domain): bool
    {
        $d = preg_replace('/^www\./i', '', strtolower(trim($domain)));
        return substr_count($d, '.') >= 2;
    }

    public function user(): BelongsTo        { return $this->belongsTo(User::class); }
    public function virtualMachine(): BelongsTo { return $this->belongsTo(VirtualMachine::class); }
    public function hostingAccount(): BelongsTo { return $this->belongsTo(HostingAccount::class); }

    /** All domain names that NPM should serve (with www if redirect enabled). */
    public function allDomainNames(): array
    {
        $names = [$this->domain];
        if ($this->www_redirect) {
            $base = ltrim($this->domain, 'w.');
            $www  = 'www.' . $base;
            if ($www !== $this->domain) $names[] = $www;
        }
        return $names;
    }
}
