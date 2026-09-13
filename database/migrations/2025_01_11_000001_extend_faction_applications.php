<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_form_fields', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->string('type'); // text | textarea | select | checkbox
            $table->json('options')->nullable(); // choices for 'select'
            $table->boolean('is_required')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        $now = now();
        DB::table('application_form_fields')->insert([
            'label' => 'Miért szeretnél csatlakozni hozzánk?', 'type' => 'textarea',
            'options' => null, 'is_required' => true, 'sort_order' => 1,
            'created_at' => $now, 'updated_at' => $now,
        ]);

        // SQLite enforces enum columns via a CHECK constraint that can't be altered in place —
        // drop and re-add 'status' as a plain string (validated at the app layer instead) so
        // NEEDS_CHANGES and any future statuses don't need a schema change.
        $existing = DB::table('faction_applications')->get();

        Schema::table('faction_applications', function (Blueprint $table) {
            $table->dropColumn(['message', 'status']);
        });

        Schema::table('faction_applications', function (Blueprint $table) {
            $table->string('status')->default('PENDING')->after('user_id');
            $table->json('answers')->nullable()->after('user_id');
            $table->text('review_note')->nullable()->after('status');
            $table->json('fields_needing_changes')->nullable()->after('review_note');
            $table->json('proposed_slots')->nullable()->after('fields_needing_changes');
            $table->date('confirmed_date')->nullable()->after('proposed_slots');
            $table->time('confirmed_start_time')->nullable()->after('confirmed_date');
            $table->time('confirmed_end_time')->nullable()->after('confirmed_start_time');
            $table->foreignId('scheduled_by')->nullable()->after('confirmed_end_time')->constrained('users')->nullOnDelete();
            $table->timestamp('scheduled_at')->nullable()->after('scheduled_by');
        });

        foreach ($existing as $row) {
            DB::table('faction_applications')->where('id', $row->id)->update([
                'status' => in_array($row->status, ['PENDING', 'APPROVED', 'REJECTED']) ? $row->status : 'PENDING',
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('faction_applications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('scheduled_by');
            $table->dropColumn(['answers', 'review_note', 'fields_needing_changes', 'proposed_slots', 'confirmed_date', 'confirmed_start_time', 'confirmed_end_time', 'scheduled_at']);
            $table->text('message')->nullable();
        });
        Schema::dropIfExists('application_form_fields');
    }
};
