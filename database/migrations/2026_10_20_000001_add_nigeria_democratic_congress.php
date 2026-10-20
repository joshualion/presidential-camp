<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // The updater preserves uploads. Copy only this bundled default, never a custom logo.
        $source = public_path('assets/img/parties/NDC.png');
        $directory = public_path('uploads/system_images/politicalparty');
        $destination = $directory.'/NDC-INEC-2026-10.png';
        if (! is_file($destination)) {
            if (! is_file($source)) {
                throw new RuntimeException('The bundled INEC NDC logo is missing.');
            }
            if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
                throw new RuntimeException('Cannot create the political party image directory.');
            }
            if (! copy($source, $destination)) {
                throw new RuntimeException('Cannot install the default INEC NDC logo.');
            }
        }
        // Preserve an existing NDC record and any owner-supplied logo or party details.
        if (! DB::table('political_parties')->where('acronym', 'NDC')->exists()) {
            DB::table('political_parties')->insert([
                'uuid' => (string) Str::uuid(), 'name' => 'Nigeria Democratic Congress',
                'acronym' => 'NDC', 'slogan' => null, 'logo' => 'NDC-INEC-2026-10.png',
                'user_id' => null, 'created_at' => now(), 'updated_at' => now(),
            ]);
        } else {
            DB::table('political_parties')->where('acronym', 'NDC')
                ->where(function ($query) { $query->whereNull('logo')->orWhere('logo', ''); })
                ->update(['logo' => 'NDC-INEC-2026-10.png', 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        // Party records may already be referenced by elections. Never delete owner data.
    }
};
