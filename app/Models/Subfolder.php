<?php

namespace App\Models;

use App\Http\Controllers\AccountController;
use App\Http\Controllers\FolderController;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subfolder extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'parent_folder_id', 'name'];

    // Define relationship to User
    public function user()
    {
        return $this->belongsTo(AccountController::class);
    }

    // Define relationship to parent folder
    public function parentFolder()
    {
        return $this->belongsTo(Folder::class, 'parent_folder_id');
    }

    // Optionally, define relationship for nested subfolders
    public function subfolders()
    {
        return $this->hasMany(Subfolder::class);
    }
}
