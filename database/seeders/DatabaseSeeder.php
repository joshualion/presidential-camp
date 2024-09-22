<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        //Register the seeder files
        $this->call(CountrySeeder::class);
        $this->call(RegionSeeder::class);
        $this->call(StateSeeder::class);
        $this->call(AbiaLGAsTableSeeder::class);
        $this->call(AdamawaLGAsTableSeeder::class);
        $this->call(AnambraLGAsTableSeeder::class);
        $this->call(AkwaIbomLGAsTableSeeder::class);
        $this->call(BayelsaLGAsTableSeeder::class);
        $this->call(BenueLGAsTableSeeder::class);
        $this->call(BornoLGAsTableSeeder::class);
        $this->call(CrossRiverLGAsTableSeeder::class);
        $this->call(DeltaLGAsTableSeeder::class);
        $this->call(EbonyiLGAsTableSeeder::class);
        $this->call(EdoLGAsTableSeeder::class);
        $this->call(EkitiLGAsTableSeeder::class);
        $this->call(EnuguLGAsTableSeeder::class);
        $this->call(GombeLGAsTableSeeder::class);
        $this->call(ImoLGAsTableSeeder::class);
        $this->call(JigawaLGAsTableSeeder::class);
        $this->call(KadunaLGAsTableSeeder::class);
        $this->call(KanoLGAsTableSeeder::class);
        $this->call(KatsinaLGAsTableSeeder::class);
        $this->call(KebbiLGAsTableSeeder::class);
        $this->call(KogiLGAsTableSeeder::class);
        $this->call(KwaraLGAsTableSeeder::class);
        $this->call(LagosLGAsTableSeeder::class);
        $this->call(NasarawaLGAsTableSeeder::class);
        $this->call(NigerLGAsTableSeeder::class);
        $this->call(OgunLGAsTableSeeder::class);
        $this->call(OndoLGAsTableSeeder::class);
        $this->call(OsunLGAsTableSeeder::class);
        $this->call(OyoLGAsTableSeeder::class);
        $this->call(PlateauLGAsTableSeeder::class);
        $this->call(RiversLGAsTableSeeder::class);
        $this->call(SokotoLGAsTableSeeder::class);
        $this->call(TarabaLGAsTableSeeder::class);
        $this->call(YobeLGAsTableSeeder::class);
        $this->call(ZamfaraLGAsTableSeeder::class);
        $this->call(CRSCalMunicipalWards::class);
        $this->call(CRSCalSouthWards::class);
        $this->call(CalMunWardSixPUSeederTable::class);

        //other seeders
        $this->call(AgeGradeTableSeeder::class);
        $this->call(SupportGroupSeeder::class);
        $this->call(ReligionTableSeeder::class);
        $this->call(UsersTableSeeder::class);

        // User::factory(10)->create();
        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }
}
