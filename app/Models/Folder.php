<?php

namespace App\Models;

use App\Http\Controllers\FolderController;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Folder extends Model
{
    use HasFactory;

    protected $table = 'users_folder';

    protected $fillable = [
        'users_id',
        'title'
    ];


    public function files()
    {
        return $this->hasMany(FolderController::class, 'users_folder_id');
    }

    public function subfolders()
    {
        return $this->hasMany(Subfolder::class, 'parent_folder_id');
    }
}
