<?php

use App\Models\Metafor;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $duplicateSignatures = Metafor::query()
            ->get(['metafor', 'explained_for'])
            ->map(fn (Metafor $metafor): string => Metafor::signature($metafor->metafor, $metafor->explained_for))
            ->duplicates();

        if ($duplicateSignatures->isNotEmpty()) {
            throw new \RuntimeException('Cannot add the metafors signature index while normalized duplicate rows exist.');
        }

        Schema::table('metafors', function (Blueprint $table): void {
            $table->string('signature')->nullable()->after('explained_for');
        });

        Metafor::query()
            ->select(['id', 'metafor', 'explained_for'])
            ->chunkById(100, function ($metafors): void {
                foreach ($metafors as $metafor) {
                    DB::table('metafors')
                        ->where('id', $metafor->id)
                        ->update([
                            'signature' => Metafor::signature($metafor->metafor, $metafor->explained_for),
                        ]);
                }
            });

        Schema::table('metafors', function (Blueprint $table): void {
            $table->unique('signature');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('metafors', function (Blueprint $table): void {
            $table->dropUnique(['signature']);
            $table->dropColumn('signature');
        });
    }
};