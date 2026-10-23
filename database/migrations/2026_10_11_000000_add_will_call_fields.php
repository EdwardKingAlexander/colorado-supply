<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Will call (ai/modules/will-call-notifications-complete, phase 1): pickup details on
 * locations and the pickup method/workflow on orders.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->boolean('pickup_enabled')->default(false)->after('slug');
            $table->json('address')->nullable()->after('pickup_enabled');
            $table->string('phone', 50)->nullable()->after('address');
            $table->text('hours')->nullable()->after('phone');
            $table->text('pickup_instructions')->nullable()->after('hours');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('fulfillment_method', 20)->default('ship')->after('fulfillment_status');
            $table->foreignId('pickup_location_id')->nullable()->after('fulfillment_method')
                ->constrained('locations')->nullOnDelete();
            $table->string('pickup_status', 30)->nullable()->after('pickup_location_id');
            $table->string('pickup_code', 20)->nullable()->unique()->after('pickup_status');
            $table->timestamp('ready_for_pickup_at')->nullable()->after('pickup_code');
            $table->timestamp('picked_up_at')->nullable()->after('ready_for_pickup_at');
            $table->string('picked_up_by')->nullable()->after('picked_up_at');

            $table->index(['fulfillment_method', 'pickup_status']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['fulfillment_method', 'pickup_status']);
            $table->dropConstrainedForeignId('pickup_location_id');
            $table->dropUnique(['pickup_code']);
            $table->dropColumn([
                'fulfillment_method', 'pickup_status', 'pickup_code',
                'ready_for_pickup_at', 'picked_up_at', 'picked_up_by',
            ]);
        });

        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn(['pickup_enabled', 'address', 'phone', 'hours', 'pickup_instructions']);
        });
    }
};
