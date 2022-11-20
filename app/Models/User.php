<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'user';

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function brand() 
    {
        return $this->belongsTo(Brand::class);
    }

    public function isAdmin()
    {
        return $this->brand_id == NULL;
    }

    public function userBrand()
    {
        if($this->brand()) return $this->brand;
        if(session('brand')) return session('brand');

        return false;
    }

    /**
     * Check if the user has the specified role.
     */
    public function hasRole($role)
    {
        $roles = array_map('trim', explode(',', $this->roles));
        foreach($roles as $r) 
        {
            if($r == 'admin' || $r == $role) 
                return true;
        }

        return false;
    }
}
