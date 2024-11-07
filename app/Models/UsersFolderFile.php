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
        'files',
        'size',
        'extension',
        'protected',
        'password',
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
}
