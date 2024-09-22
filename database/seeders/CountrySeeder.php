<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CountrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $countries = [
            ['name' => 'Nigeria'],
            ['name' => 'Algeria'],
            ['name' => 'Angola'],
            ['name' => 'Benin'],
            ['name' => 'Botswana'],
            ['name' => 'Burkina Faso'],
            ['name' => 'Burundi'],
            ['name' => 'Cabo Verde'],
            ['name' => 'Cameroon'],
            ['name' => 'Central African Republic'],
            ['name' => 'Chad'],
            ['name' => 'Comoros'],
            ['name' => 'Congo, Democratic Republic of the'],
            ['name' => 'Congo, Republic of the'],
            ['name' => 'Djibouti'],
            ['name' => 'Egypt'],
            ['name' => 'Equatorial Guinea'],
            ['name' => 'Eritrea'],
            ['name' => 'Eswatini'],
            ['name' => 'Ethiopia'],
            ['name' => 'Gabon'],
            ['name' => 'Gambia'],
            ['name' => 'Ghana'],
            ['name' => 'Guinea'],
            ['name' => 'Guinea-Bissau'],
            ['name' => 'Ivory Coast'],
            ['name' => 'Kenya'],
            ['name' => 'Lesotho'],
            ['name' => 'Liberia'],
            ['name' => 'Libya'],
            ['name' => 'Madagascar'],
            ['name' => 'Malawi'],
            ['name' => 'Mali'],
            ['name' => 'Mauritania'],
            ['name' => 'Mauritius'],
            ['name' => 'Morocco'],
            ['name' => 'Mozambique'],
            ['name' => 'Namibia'],
            ['name' => 'Niger'],
            ['name' => 'Rwanda'],
            ['name' => 'Sao Tome and Principe'],
            ['name' => 'Senegal'],
            ['name' => 'Seychelles'],
            ['name' => 'Sierra Leone'],
            ['name' => 'Somalia'],
            ['name' => 'South Africa'],
            ['name' => 'South Sudan'],
            ['name' => 'Sudan'],
            ['name' => 'Tanzania'],
            ['name' => 'Togo'],
            ['name' => 'Tunisia'],
            ['name' => 'Uganda'],
            ['name' => 'Zambia'],
            ['name' => 'Zimbabwe'],
        ];

        foreach ($countries as $country) {
            DB::table('countries')->insert([
                'uuid' => (string) Str::uuid(), // Generate UUID here
                'name' => $country['name'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
