<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subfolder extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'parent_folder_id', 'name', 'subfolder_path'];

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

    // Define relationship for nested subfolders
    public function subfolders()
    {
        return $this->hasMany(Subfolder::class, 'parent_folder_id');
    }

    // Define relationship for files
    public function files()
    {
        return $this->hasMany(UsersFolderFile::class, 'users_folder_id'); // Adjust as needed
    }


    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($subfolder) {
            UsersFolderFile::where('subfolder_id', $subfolder->id)->delete();
        });
    }
}
