<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsersFolder extends Model
{
    use HasFactory;

    protected $table = 'users_folder';

    protected $fillable = ['users_id', 'title'];


    // Define relationship to User
    public function user()
    {
        return $this->belongsTo(User::class, 'users_id');
    }

    // Relationship to files
    public function files()
    {
        return $this->hasMany(UsersFolderFile::class, 'users_folder_id');
    }

    // Relationship to subfolders
    public function subfolders()
    {
        return $this->hasMany(Subfolder::class, 'parent_folder_id');
    }
}