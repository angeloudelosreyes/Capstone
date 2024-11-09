<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsersShareableFile extends Model
{
    use HasFactory;

    protected $table = 'users_shareable_files';

    protected $fillable = [
        'users_id',
        'recipient_id',
        'folder_shareable_id',
    ];

    // Define the relationship to the user who shared the file
    public function user()
    {
        return $this->belongsTo(User::class, 'users_id');
    }

    // Define the relationship to the recipient user
    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    // Define the relationship to the shared folder
    public function folderShareable()
    {
        return $this->belongsTo(UsersFolderShareable::class, 'folder_shareable_id');
    }
    public function shareableFiles()
    {
        return $this->hasMany(UsersFolderFile::class, 'users_folder_shareable_id');
    }
}
