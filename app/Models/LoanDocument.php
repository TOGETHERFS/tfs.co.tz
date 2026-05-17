<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;

class LoanDocument extends BaseModel
{
    use HasFactory;

    const TYPE_LOAN_CONTRACT = 'loan_contract';
    const TYPE_SPOUSE_CONSENT = 'spouse_consent';
    const TYPE_GUARANTOR_FORM = 'guarantor_form';
    const TYPE_COLLATERAL = 'collateral';
    const TYPE_OTHER = 'other';

    const DOCUMENT_TYPES = [
        self::TYPE_LOAN_CONTRACT => 'Loan Contract',
        self::TYPE_SPOUSE_CONSENT => 'Spouse Consent',
        self::TYPE_GUARANTOR_FORM => 'Guarantor Form',
        self::TYPE_COLLATERAL => 'Collateral',
        self::TYPE_OTHER => 'Other',
    ];

    const MAX_FILE_SIZE = 1024000; // 1000MB in KB
    const MAX_ATTACHMENTS = 10;

    protected $fillable = [
        'tenant_id',
        'loan_id',
        'document_type',
        'uploaded_by',
        'original_name',
        'file_name',
        'mime_type',
        'size',
        'path',
        'description',
    ];

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getDocumentTypeLabelAttribute()
    {
        return self::DOCUMENT_TYPES[$this->document_type] ?? 'Unknown';
    }

    public function getFileSizeFormattedAttribute()
    {
        $bytes = $this->size;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' bytes';
    }
}