<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles; // <--- Tambahkan Library Spatie

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles; // <--- Tambahkan Trait HasRoles

    protected $fillable = [
        'name',
        'username',
        'password',
        'department_id',
        'position_id',
        'is_active',
        'signature_pin',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean', // Casting agar jadi true/false, bukan 1/0
        ];
    }

    /**
     * Relasi: User milik satu Department
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Relasi: User memiliki satu Position (Jabatan Struktural)
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }
}