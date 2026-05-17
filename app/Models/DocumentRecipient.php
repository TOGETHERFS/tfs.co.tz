<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentRecipient extends Model
{
    protected $table = 'document_recipients';

    protected $fillable = [
        'document_id', 'recipient_id', 'is_read', 'read_at',
    ];

    protected $casts = [
        'is_read'  => 'boolean',
        'read_at'  => 'datetime',
    ];

    public function document()
    {
        return $this->belongsTo(StaffDocument::class, 'document_id');
    }

    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }
}
