<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_campaign_deliveries', function (Blueprint $table) {
            $table->dateTime('shown_locally_at')->nullable()->after('opened_at');
            $table->dateTime('read_at')->nullable()->after('shown_locally_at');
        });

        Schema::table('notification_campaigns', function (Blueprint $table) {
            $table->unsignedInteger('pending_app_pull_count')->default(0)->after('skipped_count');
        });

        // Honest hand-off: old "no push provider" rows become pending app pull.
        DB::table('notification_campaign_deliveries')
            ->where('delivery_status', 'provider_unavailable')
            ->update(['delivery_status' => 'pending_for_app_pull']);
    }

    public function down(): void
    {
        Schema::table('notification_campaign_deliveries', function (Blueprint $table) {
            $table->dropColumn(['shown_locally_at', 'read_at']);
        });

        Schema::table('notification_campaigns', function (Blueprint $table) {
            $table->dropColumn('pending_app_pull_count');
        });
    }
};
