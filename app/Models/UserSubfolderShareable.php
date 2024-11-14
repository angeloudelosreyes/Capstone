<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSubfolderShareable extends Model
{
    use HasFactory;

    protected $table = 'user_subfolder_shareable';

    protected $fillable = [
        'user_id',
        'recipient_id',
        'subfolder_id',
    ];

    /**
     * Get the user who shared the subfolder.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the recipient user with whom the subfolder is shared.
     */
    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    /**
     * Get the subfolder that is being shared.
     */
    public function subfolder()
    {
        return $this->belongsTo(Subfolder::class, 'subfolder_id');
    }
}
