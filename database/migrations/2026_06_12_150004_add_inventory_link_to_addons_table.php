<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('addons', function (Blueprint $table) {
            $table->foreignId('inventory_item_id')->nullable()->after('price')
                ->constrained()->nullOnDelete();
            $table->decimal('quantity_used', 12, 2)->nullable()->after('inventory_item_id');
        });
    }

    public function down(): void
    {
        Schema::table('addons', function (Blueprint $table) {
            $table->dropConstrainedForeignId('inventory_item_id');
            $table->dropColumn('quantity_used');
        });
    }
};
