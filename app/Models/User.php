<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The connection name for the model.
     *
     * @var string
     */
    protected $connection = 'sso_db';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'users_employee';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'email',
        'password',
        'employee_id',
        'name',
        'department',
        'position',
        'manager_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
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
            'password' => 'hashed',
        ];
    }

    /**
     * Find a user by username for authentication
     */
    public static function findByUsername(string $username): ?User
    {
        return static::where('username', $username)->first();
    }

    /**
     * Get the username field name for authentication
     *
     * @return string
     */
    public function getAuthIdentifierName()
    {
        return 'username';
    }
}
