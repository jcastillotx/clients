<?php

use App\Models\Document;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('documents') || ! Schema::hasTable('document_versions')) {
            return;
        }

        Document::query()
            ->whereNull('current_version_id')
            ->orderBy('id')
            ->chunkById(200, function ($docs) {
                foreach ($docs as $doc) {
                    $id = DB::table('document_versions')->insertGetId([
                        'document_id' => $doc->id,
                        'version' => 1,
                        'provider' => 'local',
                        'provider_file_id' => null,
                        'file_path' => $doc->file_path,
                        'file_name' => $doc->original_filename,
                        'mime_type' => $doc->mime_type,
                        'file_size' => (int) ($doc->file_size ?? 0),
                        'checksum' => null,
                        'text_snapshot' => null,
                        'created_by' => $doc->uploaded_by,
                        'created_at' => $doc->created_at,
                        'updated_at' => $doc->updated_at,
                    ]);

                    DB::table('documents')->where('id', $doc->id)->update([
                        'current_version_id' => $id,
                    ]);
                }
            });
    }

    public function down(): void
    {
        // no-op (data migration)
    }
};
