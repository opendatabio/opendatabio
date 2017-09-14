<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\HasAuthLevels;

class Project extends Model
{
    use HasAuthLevels;

    const PRIVACY_AUTH = 0;
    const PRIVACY_REGISTERED = 1;
    const PRIVACY_PUBLIC = 2;
    const PRIVACY_LEVELS = [Project::PRIVACY_AUTH, Project::PRIVACY_REGISTERED, Project::PRIVACY_PUBLIC];

    const VIEWER = 0;
    const COLLABORATOR = 1;
    const ADMIN = 2;

    protected $fillable = ['name', 'notes', 'privacy'];

    public function plants() {
        return $this->hasMany(Plant::class);
    }
    public function vouchers() {
        return $this->hasMany(Voucher::class);
    }
}
