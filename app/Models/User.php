<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'dni',
        'role',
        'active',
        'password',
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
            'active' => 'boolean',
        ];
    }

    // Relaciones
    public function timeEntries()
    {
        return $this->hasMany(TimeEntry::class);
    }

    public function uploadedDocuments()
    {
        return $this->hasMany(Document::class, 'uploaded_by');
    }

    public function assignedDocuments()
    {
        return $this->belongsToMany(Document::class);
    }

    public function requests()
    {
        return $this->hasMany(Request::class);
    }

    public function reviewedRequests()
    {
        return $this->hasMany(Request::class, 'reviewed_by');
    }

    public function incidents()
    {
        return $this->hasMany(Incident::class);
    }

    public function resolvedIncidents()
    {
        return $this->hasMany(Incident::class, 'resolved_by');
    }

    // Helper methods
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isTrabajador()
    {
        return $this->role === 'trabajador';
    }
}
