<?php

use App\Models\EmailOtp;
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
        Schema::table('email_otps', function (Blueprint $table) {
            $table->string('purpose', 64)->default(EmailOtp::PURPOSE_EMAIL_VERIFICATION)->after('email');
            $table->index(['email', 'purpose', 'used_at'], 'email_otps_email_purpose_used_at_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_otps', function (Blueprint $table) {
            $table->dropIndex('email_otps_email_purpose_used_at_idx');
            $table->dropColumn('purpose');
        });
    }
};
