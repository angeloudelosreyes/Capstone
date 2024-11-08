<?php

namespace App\Models;

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

    // Boot method to add a model event listener for cascading deletion
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($subfolder) {
            // Delete all nested subfolders and their subfolders recursively
            $subfolder->subfolders()->each(function ($child) {
                $child->delete();
            });

            // Optionally delete associated files as well
            $subfolder->files()->delete();
        });
    }
}
