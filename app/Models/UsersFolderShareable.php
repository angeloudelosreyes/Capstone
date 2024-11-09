<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsersFolderShareable extends Model
{
    use HasFactory;

    protected $table = 'users_folder_shareable';

    protected $fillable = [
        'users_id',
        'recipient_id',
        'title',
        'can_edit',
        'can_delete',
    ];


    // Define the relationship to the user who created the folder
    public function creator()
    {
        return $this->belongsTo(User::class, 'users_id');
    }

    // Define the relationship to the user the folder is shared with
    public function sharedWithUser()
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    // Define the relationship to shareable files within this shared folder
    public function shareableFiles()
    {
        return $this->hasMany(UsersShareableFile::class, 'folder_shareable_id');
    }
}
