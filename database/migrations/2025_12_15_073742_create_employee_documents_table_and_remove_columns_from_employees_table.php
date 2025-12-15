<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Remove specified columns from employees table
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'vtnx_trade_license_renewal_date',
                'po_box_renewal_date',
                'voltronix_est_card_renewal_date',
                'warehouse_ejari_renewal_date',
                'camp_ejari_renewal_date',
                'etisalat_contract_expiry_date',
                'dewa_details',
            ]);

            // Add document path columns for each document type
            $table->string('qualification_document')->nullable()->after('year_of_completion');
            $table->string('passport_document')->nullable()->after('passport_expiry_date');
            $table->string('visa_document')->nullable()->after('visa_expiry_date');
            $table->string('visit_document')->nullable()->after('visit_expiry_date');
            $table->string('eid_document')->nullable()->after('eid_expiry_date');
            $table->string('health_insurance_document')->nullable()->after('health_insurance_expiry_date');
            $table->string('driving_license_document')->nullable()->after('driving_license_expiry_date');
            $table->string('iloe_insurance_document')->nullable()->after('iloe_insurance_expiry_date');
            $table->string('soe_card_document')->nullable()->after('soe_card_renewal_date');
            $table->string('dcd_card_document')->nullable()->after('dcd_card_renewal_date');
            $table->string('workman_insurance_document')->nullable()->after('workman_insurance_expiry_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_documents');

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'qualification_document',
                'passport_document',
                'visa_document',
                'visit_document',
                'eid_document',
                'health_insurance_document',
                'driving_license_document',
                'iloe_insurance_document',
                'soe_card_document',
                'dcd_card_document',
                'workman_insurance_document',
            ]);

            $table->date('vtnx_trade_license_renewal_date')->nullable();
            $table->date('po_box_renewal_date')->nullable();
            $table->date('voltronix_est_card_renewal_date')->nullable();
            $table->date('warehouse_ejari_renewal_date')->nullable();
            $table->date('camp_ejari_renewal_date')->nullable();
            $table->date('etisalat_contract_expiry_date')->nullable();
            $table->text('dewa_details')->nullable();
        });
    }
};
