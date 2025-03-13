<?php


namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\Schema;
use App\Models\ProductCategory;



class ProductCategoriesSeeder extends Seeder
{
    public function run()
    {
        DB::table('product_categories')->insert([
            [
                'id' => 1,
                'name' => 'Donations',
                'description' => 'Manage and track donations to support campaign activities and initiatives.',
                'slug' => 'campaign-donations',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            

            [
                'id' => 2,
                'name' => 'Merchandise',
                'description' => 'Sell campaign-branded merchandise to supporters and raise funds for your campaign.',
                'slug' => 'campaign-merchandise',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'id' => 3,
                'name' => 'Event Tickets',
                'description' => 'Sell tickets to campaign events, rallies, and fundraisers.',
                'slug' => 'event-tickets',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'id' => 4,
                'name' => 'Volunteer Kits',
                'description' => 'Provide essential kits for campaign volunteers to facilitate their activities.',
                'slug' => 'volunteer-kits',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            
            [
                'id' => 5,
                'name' => 'Campaign Materials',
                'description' => 'Offer printed and digital materials like flyers, posters, and banners to supporters.',
                'slug' => 'campaign-materials',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            
            [
                'id' => 6,
                'name' => 'Advertising Slots',
                'description' => 'Purchase advertising slots for promoting campaign messages and policies.',
                'slug' => 'advertising-slots',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'id' => 7,
                'name' => 'Sponsorship Packages',
                'description' => 'Allow sponsors to contribute to campaigns in exchange for recognition.',
                'slug' => 'sponsorship-packages',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            
        ]);
    }
}
