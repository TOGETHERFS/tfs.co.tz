<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Client extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'client_id',
        'first_name',
        'last_name',
        'phone',
        'email',
        'address',
        'region',
        'district',
        'ward',
        'street',
        'date_of_birth',
        'gender',
        'id_number',
        'status',
        'branch_name',
        'loan_officer',
        'photo_path',
        'marital_status',
        'occupation',
        'monthly_income',
        'employer',
        'employment_type',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relationship',
        'branch_id',
        'loan_officer_id',
    ];

    /**
     * Boot the model and register event listeners.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($client) {
            if (empty($client->client_id)) {
                $client->client_id = self::generateClientId($client->tenant_id);
            }
        });
    }

    /**
     * Generate a unique borrower/client ID.
     * Uses MAX query for the next available number.
     */
    public static function generateClientId(?int $tenantId = null): string
    {
        $prefix = 'BRW';
        $year = date('Y');
        
        // Use raw SQL to get MAX in a single query - more reliable for concurrent inserts
        $pattern = $prefix . $year . '%';
        $result = \DB::selectOne(
            "SELECT MAX(CAST(SUBSTRING(client_id, 8) AS UNSIGNED)) as max_num 
             FROM clients 
             WHERE client_id LIKE ?",
            [$pattern]
        );

        $newNumber = ($result && $result->max_num) ? $result->max_num + 1 : 1;

        return $prefix . $year . str_pad($newNumber, 5, '0', STR_PAD_LEFT);
    }
    
    /**
     * Create a client with retry on duplicate client_id.
     */
    public static function createWithRetry(array $data, int $maxRetries = 10): self
    {
        $lastException = null;
        
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                return self::create($data);
            } catch (\Illuminate\Database\QueryException $e) {
                // Check if it's a duplicate key error (MySQL error 1062)
                if ($e->errorInfo[1] == 1062 && str_contains($e->getMessage(), 'client_id')) {
                    $lastException = $e;
                    usleep(50000 * $attempt); // Exponential backoff: 50ms, 100ms, 150ms...
                    continue;
                }
                throw $e;
            }
        }
        
        throw $lastException ?? new \Exception('Failed to create client after ' . $maxRetries . ' retries');
    }

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    /**
     * Get the loans for the client.
     */
    public function loans()
    {
        return $this->hasMany(Loan::class);
    }

    /**
     * Get the active loans for the client.
     */
    public function activeLoans()
    {
        return $this->loans()->whereIn('status', ['disbursed', 'active']);
    }

    /**
     * Get the repayments for the client.
     */
    public function repayments()
    {
        return $this->hasManyThrough(Repayment::class, Loan::class);
    }

    /**
     * Scope a query to only include active clients.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Get the client's full name.
     */
    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * Get total loan amount.
     */
    public function getTotalLoanAmountAttribute()
    {
        return $this->loans()->sum('principal');
    }

    /**
     * Get total outstanding balance.
     */
    public function getTotalOutstandingAttribute()
    {
        return $this->activeLoans()->sum('total_amount') - 
               $this->repayments()->sum('amount');
    }

    /**
     * Check if client has active loans.
     */
    public function hasActiveLoans()
    {
        return $this->activeLoans()->exists();
    }

    /**
     * Get groups the client belongs to.
     */
    public function groups()
    {
        return $this->belongsToMany(Group::class, 'group_client');
    }
}