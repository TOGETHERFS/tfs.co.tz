<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffDocument extends Model
{
    protected $table = 'staff_documents';

    protected $fillable = [
        'tenant_id', 'uploaded_by', 'title', 'description',
        'file_path', 'file_name', 'file_size', 'mime_type',
    ];

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function recipients()
    {
        return $this->hasMany(DocumentRecipient::class, 'document_id');
    }

    public function recipientUsers()
    {
        return $this->belongsToMany(User::class, 'document_recipients', 'document_id', 'recipient_id')
            ->withPivot('is_read', 'read_at')
            ->withTimestamps();
    }
}
