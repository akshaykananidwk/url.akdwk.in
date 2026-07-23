<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SsoProvider extends Model
{
    protected $fillable = [
        'name', 'slug', 'type', 'client_id', 'client_secret', 'issuer',
        'authorize_url', 'token_url', 'userinfo_url', 'scopes', 'active',
    ];

    protected $hidden = ['client_secret'];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'client_secret' => 'encrypted'];
    }

    /** Resolve OIDC endpoints from the issuer's discovery document if not set. */
    public function endpoints(): array
    {
        if ($this->authorize_url && $this->token_url && $this->userinfo_url) {
            return [
                'authorize' => $this->authorize_url,
                'token' => $this->token_url,
                'userinfo' => $this->userinfo_url,
            ];
        }

        if ($this->issuer) {
            try {
                $doc = \Illuminate\Support\Facades\Http::timeout(6)
                    ->get(rtrim($this->issuer, '/') . '/.well-known/openid-configuration')->json();

                return [
                    'authorize' => $doc['authorization_endpoint'] ?? null,
                    'token' => $doc['token_endpoint'] ?? null,
                    'userinfo' => $doc['userinfo_endpoint'] ?? null,
                ];
            } catch (\Throwable) {
            }
        }

        return ['authorize' => null, 'token' => null, 'userinfo' => null];
    }
}
