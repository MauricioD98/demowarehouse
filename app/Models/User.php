<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $dates = ['deleted_at'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'firstname', 'lastname', 'username', 'email', 'password', 'phone', 'statut', 'avatar', 'role_id', 'is_all_warehouses', 'record_view',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'role_id' => 'integer',
        'statut' => 'integer',
        'is_all_warehouses' => 'integer',
        'record_view' => 'boolean',
    ];

    public function oauthAccessToken()
    {
        return $this->hasMany('\App\Models\OauthAccessToken');
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    /**
     * Get the roles for this user. If role_user pivot is empty but user has role_id (legacy),
     * sync pivot from role_id and return roles so permission checks work.
     */
    public function getEffectiveRoles()
    {
        $roles = $this->roles;
        if ($roles->isEmpty() && $this->role_id) {
            $role = Role::find($this->role_id);
            if ($role) {
                $this->roles()->sync([$this->role_id]);
                $roles = $this->roles()->get();
                $this->setRelation('roles', $roles);
            }
        }
        return $roles;
    }

    public function assignRole(Role $role)
    {
        return $this->roles()->save($role);
    }

    /**
     * Determine if the user has the given role(s).
     * When $role is a collection of Role models (e.g. from Permission->roles),
     * comparison is done by role id so that policy checks work correctly.
     *
     * @param  string|\Illuminate\Support\Collection  $role  Role name or collection of Role models
     * @return bool
     */
    public function hasRole($role)
    {
        $userRoles = $this->getEffectiveRoles();
        if (is_string($role)) {
            return $userRoles->contains('name', $role);
        }

        // Policies pass $permission->roles (collection of Role models). intersect() compares
        // by object identity, so we must compare by id for authorization to work.
        $allowedRoleIds = $role->pluck('id');
        $userRoleIds = $userRoles->pluck('id');

        return $allowedRoleIds->intersect($userRoleIds)->isNotEmpty();
    }

    /**
     * Check if the user has a permission by name (via their role).
     * Matches the same logic used by GetUserAuth so API and policy stay in sync.
     *
     * @param  string  $permissionName  e.g. 'Sales_view', 'Sales_add'
     * @return bool
     */
    public function hasPermissionByName(string $permissionName): bool
    {
        $role = $this->getEffectiveRoles()->first();
        if ($role) {
            // Load permissions if not already loaded
            $role->loadMissing('permissions');
            return $role->inRole($permissionName);
        }
        if ($this->role_id) {
            $role = Role::with('permissions')->find($this->role_id);
            return $role && $role->permissions->contains('name', $permissionName);
        }
        return false;
    }

    public function assignedWarehouses()
    {
        return $this->belongsToMany('App\Models\Warehouse');
    }

    /**
     * Check if user has record_view permission (user-level boolean with backward compatibility)
     *
     * @return bool
     */
    public function hasRecordView()
    {
        // New way: Check user's record_view field (user-level boolean)
        // Backward compatibility: If record_view is null, fall back to role permission check
        if (isset($this->record_view)) {
            return (bool) $this->record_view;
        } else {
            // Fallback to role permission check for backward compatibility
            $role = $this->getEffectiveRoles()->first();
            if ($role) {
                // Load permissions if not already loaded
                $role->loadMissing('permissions');
                return $role->inRole('record_view');
            }
        }

        return false;
    }
}
