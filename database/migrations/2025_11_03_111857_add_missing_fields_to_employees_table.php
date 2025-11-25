<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Only add fields that don't exist in the current migration

            // Company & Insurance Documents (NEW)
            if (!Schema::hasColumn('employees', 'iloe_insurance_expiry_date')) {
                $table->date('iloe_insurance_expiry_date')->nullable()->after('driving_license_expiry_date');
            }

            if (!Schema::hasColumn('employees', 'vtnx_trade_license_renewal_date')) {
                $table->date('vtnx_trade_license_renewal_date')->nullable()->after('iloe_insurance_expiry_date');
            }

            if (!Schema::hasColumn('employees', 'po_box_renewal_date')) {
                $table->date('po_box_renewal_date')->nullable()->after('vtnx_trade_license_renewal_date');
            }

            if (!Schema::hasColumn('employees', 'soe_card_renewal_date')) {
                $table->date('soe_card_renewal_date')->nullable()->after('po_box_renewal_date');
            }

            if (!Schema::hasColumn('employees', 'dcd_card_renewal_date')) {
                $table->date('dcd_card_renewal_date')->nullable()->after('soe_card_renewal_date');
            }

            if (!Schema::hasColumn('employees', 'voltronix_est_card_renewal_date')) {
                $table->date('voltronix_est_card_renewal_date')->nullable()->after('dcd_card_renewal_date');
            }

            if (!Schema::hasColumn('employees', 'warehouse_ejari_renewal_date')) {
                $table->date('warehouse_ejari_renewal_date')->nullable()->after('voltronix_est_card_renewal_date');
            }

            if (!Schema::hasColumn('employees', 'camp_ejari_renewal_date')) {
                $table->date('camp_ejari_renewal_date')->nullable()->after('warehouse_ejari_renewal_date');
            }

            if (!Schema::hasColumn('employees', 'workman_insurance_expiry_date')) {
                $table->date('workman_insurance_expiry_date')->nullable()->after('camp_ejari_renewal_date');
            }

            if (!Schema::hasColumn('employees', 'etisalat_contract_expiry_date')) {
                $table->date('etisalat_contract_expiry_date')->nullable()->after('workman_insurance_expiry_date');
            }

            // Additional Details (NEW)
            if (!Schema::hasColumn('employees', 'dewa_details')) {
                $table->text('dewa_details')->nullable()->after('etisalat_contract_expiry_date');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumnIfExists([
                'iloe_insurance_expiry_date',
                'vtnx_trade_license_renewal_date',
                'po_box_renewal_date',
                'soe_card_renewal_date',
                'dcd_card_renewal_date',
                'voltronix_est_card_renewal_date',
                'warehouse_ejari_renewal_date',
                'camp_ejari_renewal_date',
                'workman_insurance_expiry_date',
                'etisalat_contract_expiry_date',
                'dewa_details',
            ]);
        });
    }
};
