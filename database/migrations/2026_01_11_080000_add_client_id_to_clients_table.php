<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('client_id', 20)->nullable()->unique()->after('tenant_id');
        });

        // Generate client_id for existing clients
        $clients = DB::table('clients')->whereNull('client_id')->orderBy('id')->get();
        foreach ($clients as $client) {
            $clientId = $this->generateClientId($client->tenant_id);
            DB::table('clients')->where('id', $client->id)->update(['client_id' => $clientId]);
        }
    }

    /**
     * Generate a unique client ID.
     */
    private function generateClientId(int $tenantId): string
    {
        $prefix = 'BRW';
        $year = date('Y');
        
        $lastClient = DB::table('clients')
            ->where('tenant_id', $tenantId)
            ->where('client_id', 'like', $prefix . $year . '%')
            ->orderBy('client_id', 'desc')
            ->first();

        if ($lastClient && $lastClient->client_id) {
            $lastNumber = (int) substr($lastClient->client_id, -5);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . $year . str_pad($newNumber, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('client_id');
        });
    }
};
