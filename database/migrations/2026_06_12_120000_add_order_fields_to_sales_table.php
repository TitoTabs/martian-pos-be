<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('customer_name')->default('Walk-in')->after('payment_method');
            $table->string('order_type')->default('dine_in')->after('customer_name');
            $table->text('notes')->nullable()->after('order_type');
            $table->string('status')->default('pending')->after('notes');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn(['customer_name', 'order_type', 'notes', 'status']);
        });
    }
};
