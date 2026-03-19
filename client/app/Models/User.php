<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    protected $table = 'users';
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = ['user', 'hashParola', 'email', 'nume', 'telefon', 'nivel_acces',
                           'expeditor_id', 'print_awb', 
                           'activ', 'selectie_puncte_de_lucru', 'preturi', 'recantarite',
                           'importcsv', 'show_master_clienti', 'def_sms', 
                           'def_obsv'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'hashParola',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'hashParola' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'activ' => 'boolean',
            'selectie_puncte_de_lucru' => 'boolean',
            'importcsv' => 'boolean',
            'preturi' => 'boolean',
            'show_master_clienti' => 'boolean',
            'def_sms' => 'boolean',
            'recantarite' => 'boolean',
        ];
    }

    /**
     * Get the password for authentication.
     */
    public function getAuthPassword(): ?string
    {
        return $this->hashParola;
    }
}
