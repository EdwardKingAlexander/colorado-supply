<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Add order_number if it doesn't exist
            if (!Schema::hasColumn('orders', 'order_number')) {
                $table->string('order_number', 50)->unique()->after('id');
            }

            // Modify existing columns
            if (Schema::hasColumn('orders', 'customer_id')) {
                $table->foreignId('customer_id')->nullable()->change();
            }

            if (Schema::hasColumn('orders', 'quote_id')) {
                $table->foreignId('quote_id')->nullable()->change();
            }

            // Add cash/card guest fields
            if (!Schema::hasColumn('orders', 'cash_card_name')) {
                $table->string('cash_card_name')->nullable()->after('quote_id');
            }
            if (!Schema::hasColumn('orders', 'cash_card_email')) {
                $table->string('cash_card_email')->nullable()->after('cash_card_name');
            }
            if (!Schema::hasColumn('orders', 'cash_card_phone')) {
                $table->string('cash_card_phone')->nullable()->after('cash_card_email');
            }
            if (!Schema::hasColumn('orders', 'cash_card_company')) {
                $table->string('cash_card_company')->nullable()->after('cash_card_phone');
            }

            // Add contact info snapshot
            if (!Schema::hasColumn('orders', 'contact_name')) {
                $table->string('contact_name')->nullable()->after('cash_card_company');
            }
            if (!Schema::hasColumn('orders', 'contact_email')) {
                $table->string('contact_email')->nullable()->after('contact_name');
            }
            if (!Schema::hasColumn('orders', 'contact_phone')) {
                $table->string('contact_phone')->nullable()->after('contact_email');
            }
            if (!Schema::hasColumn('orders', 'company_name')) {
                $table->string('company_name')->nullable()->after('contact_phone');
            }

            // Add address fields
            if (!Schema::hasColumn('orders', 'billing_address')) {
                $table->json('billing_address')->nullable()->after('company_name');
            }
            if (!Schema::hasColumn('orders', 'shipping_address')) {
                $table->json('shipping_address')->nullable()->after('billing_address');
            }

            // Ensure commercial fields exist
            if (!Schema::hasColumn('orders', 'po_number')) {
                $table->string('po_number')->nullable()->after('shipping_address');
            }
            if (!Schema::hasColumn('orders', 'job_number')) {
                $table->string('job_number')->nullable()->after('po_number');
            }
            if (!Schema::hasColumn('orders', 'notes')) {
                $table->text('notes')->nullable()->after('job_number');
            }
            if (!Schema::hasColumn('orders', 'internal_notes')) {
                $table->text('internal_notes')->nullable()->after('notes');
            }

            // Add detailed totals
            if (!Schema::hasColumn('orders', 'subtotal')) {
                $table->decimal('subtotal', 12, 2)->default(0)->after('internal_notes');
            }
            if (!Schema::hasColumn('orders', 'tax_total')) {
                $table->decimal('tax_total', 12, 2)->default(0)->after('subtotal');
            }
            if (!Schema::hasColumn('orders', 'shipping_total')) {
                $table->decimal('shipping_total', 12, 2)->default(0)->after('tax_total');
            }
            if (!Schema::hasColumn('orders', 'discount_total')) {
                $table->decimal('discount_total', 12, 2)->default(0)->after('shipping_total');
            }
            if (!Schema::hasColumn('orders', 'grand_total')) {
                $table->decimal('grand_total', 12, 2)->default(0)->after('discount_total');
            }

            // Add tax rate
            if (!Schema::hasColumn('orders', 'tax_rate')) {
                $table->decimal('tax_rate', 5, 2)->default(0)->after('grand_total');
            }

            // Update status columns
            if (!Schema::hasColumn('orders', 'payment_status')) {
                $table->string('payment_status')->default('unpaid')->after('status');
            }
            if (!Schema::hasColumn('orders', 'fulfillment_status')) {
                $table->string('fulfillment_status')->default('unfulfilled')->after('payment_status');
            }

            // Add date fields
            if (!Schema::hasColumn('orders', 'confirmed_at')) {
                $table->timestamp('confirmed_at')->nullable()->after('fulfillment_status');
            }
            if (!Schema::hasColumn('orders', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('confirmed_at');
            }
            if (!Schema::hasColumn('orders', 'fulfilled_at')) {
                $table->timestamp('fulfilled_at')->nullable()->after('paid_at');
            }
            if (!Schema::hasColumn('orders', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('fulfilled_at');
            }

            // Add metadata
            if (!Schema::hasColumn('orders', 'meta')) {
                $table->json('meta')->nullable()->after('cancelled_at');
            }

            // Add ownership fields
            if (!Schema::hasColumn('orders', 'created_by')) {
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->after('meta');
            }
            if (!Schema::hasColumn('orders', 'updated_by')) {
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete()->after('created_by');
            }

            // Add soft deletes
            if (!Schema::hasColumn('orders', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }

            // Drop old columns that are no longer needed
            if (Schema::hasColumn('orders', 'walk_in_label')) {
                $table->dropColumn([
                    'walk_in_label',
                    'walk_in_org',
                    'walk_in_contact_name',
                    'walk_in_email',
                    'walk_in_phone',
                    'walk_in_billing_json',
                    'walk_in_shipping_json'
                ]);
            }

            if (Schema::hasColumn('orders', 'payment_method')) {
                $table->dropColumn('payment_method');
            }

            if (Schema::hasColumn('orders', 'order_total')) {
                $table->dropColumn('order_total');
            }
        });

        // Add indexes after all columns are in place
        // Using try-catch since indexes might already exist
        try {
            Schema::table('orders', function (Blueprint $table) {
                $table->index('order_number');
            });
        } catch (\Exception $e) {
            // Index already exists, skip
        }

        try {
            Schema::table('orders', function (Blueprint $table) {
                $table->index('customer_id');
            });
        } catch (\Exception $e) {
            // Index already exists, skip
        }

        try {
            Schema::table('orders', function (Blueprint $table) {
                $table->index('quote_id');
            });
        } catch (\Exception $e) {
            // Index already exists, skip
        }

        try {
            Schema::table('orders', function (Blueprint $table) {
                $table->index('status');
            });
        } catch (\Exception $e) {
            // Index already exists, skip
        }

        try {
            Schema::table('orders', function (Blueprint $table) {
                $table->index('payment_status');
            });
        } catch (\Exception $e) {
            // Index already exists, skip
        }

        try {
            Schema::table('orders', function (Blueprint $table) {
                $table->index('fulfillment_status');
            });
        } catch (\Exception $e) {
            // Index already exists, skip
        }

        try {
            Schema::table('orders', function (Blueprint $table) {
                $table->index('created_at');
            });
        } catch (\Exception $e) {
            // Index already exists, skip
        }
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Remove new columns
            $table->dropColumn([
                'order_number',
                'cash_card_name',
                'cash_card_email',
                'cash_card_phone',
                'cash_card_company',
                'contact_name',
                'contact_email',
                'contact_phone',
                'company_name',
                'billing_address',
                'shipping_address',
                'internal_notes',
                'subtotal',
                'tax_total',
                'shipping_total',
                'discount_total',
                'grand_total',
                'tax_rate',
                'payment_status',
                'fulfillment_status',
                'confirmed_at',
                'paid_at',
                'fulfilled_at',
                'cancelled_at',
                'meta',
                'created_by',
                'updated_by',
                'deleted_at',
            ]);

            // Restore old columns
            $table->string('walk_in_label')->nullable();
            $table->string('walk_in_org')->nullable();
            $table->string('walk_in_contact_name')->nullable();
            $table->string('walk_in_email')->nullable();
            $table->string('walk_in_phone')->nullable();
            $table->json('walk_in_billing_json')->nullable();
            $table->json('walk_in_shipping_json')->nullable();
            $table->enum('payment_method', ['credit_card', 'debit_card', 'online_portal']);
            $table->decimal('order_total', 12, 2)->default(0.00);
        });
    }
};
