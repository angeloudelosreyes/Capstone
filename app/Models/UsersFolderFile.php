<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsersFolderFile extends Model
{
    use HasFactory;

    protected $table = 'users_folder_files';

    protected $fillable = [
        'users_id',
        'users_folder_id',
        'subfolder_id',
        'files',
        'size',
        'extension',
        'protected',
        'password',
        'created_at',
        'updated_at',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class, 'users_id');
    }

    public function folder()
    {
        return $this->belongsTo(UsersFolder::class, 'users_folder_id');
    }

    // Define the relationship to the UsersFolderShareable model
    public function shareableFolder()
    {
        return $this->belongsTo(UsersFolderShareable::class, 'users_folder_shareable_id');
    }
}
