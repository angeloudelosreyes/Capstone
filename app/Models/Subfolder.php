<?php

namespace App\Models;

use App\Http\Controllers\AccountController;
use App\Http\Controllers\FolderController;
use Faker\Core\File;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subfolder extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'parent_folder_id', 'name'];

    // Define relationship to User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Define relationship to parent folder
    public function parentFolder()
    {
        return $this->belongsTo(Subfolder::class, 'parent_folder_id');
    }

    // Optionally, define relationship for nested subfolders
    public function subfolders()
    {
        return $this->hasMany(Subfolder::class, 'parent_folder_id');
    }
    public function files()
    {
        return $this->hasMany(UsersFolderFile::class, 'users_folder_id'); // Use users_folder_id instead
    }
}
