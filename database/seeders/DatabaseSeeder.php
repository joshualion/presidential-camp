<?php

namespace Database\Seeders;

use App\Jobs\GenerateFakeUsersJob;
use App\Jobs\GeneratePollingUnitsJob;
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


       /* //LGA Factory
       \App\Models\LocalGovernmentArea::factory()->count(774)->create();

       //Ward factory
       \App\Models\Ward::factory()->count(8809)->create();

       //Polling Unit Factory Dispatcher
       GeneratePollingUnitsJob::dispatch();
       */



        //other seeders
        $this->call(AgeGradeTableSeeder::class);
        $this->call(SupportGroupSeeder::class);
        $this->call(ReligionTableSeeder::class);
        $this->call(PoliticalPartiesSeeder::class);



        // Settings Seeders
        $this->call(SystemSettingsSeeder::class);
        $this->call(SMTPSettingsSeeder::class);
        $this->call(PaymentGatewaySeeder::class);
        $this->call(CommunityRulesSeeder::class);

        // Roles & Permissions
        $this->call(RolesTableSeeder::class);
        $this->call(PermissionsTableSeeder::class);

        //Product seeders
        $this->call(ProductCategoriesSeeder::class);
        $this->call(ProductsSeeder::class);

        //Users
        $this->call(UsersTableSeeder::class);

       // $this->call(RolePermissionTableSeeder::class);

        // User::factory(10)->create();
        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }
}
