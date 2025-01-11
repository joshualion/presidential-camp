<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;


use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductsSeeder extends Seeder
{
    public function run()
    {
        DB::table('products')->insert([
            [
                'id' => 1,
                'name' => 'General Campaign Donation',
                'description' => 'Support our campaign efforts with a general donation. Every contribution makes a difference!',
                'slug' => 'general-campaign-donation',
                'price' => 1000.00,
                'discount' => 0.00,
                'image' => 'donation_202501041231.png',
                'status' => 'active',
                'category_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'id' => 2,
                'name' => 'Campaign T-Shirt',
                'description' => 'Show your support with our official campaign T-shirt, available in various sizes.',
                'slug' => 'campaign-t-shirt',
                'price' => 5000.00,
                'discount' => 500.00,
                'image' => 'tshirt_202501041232.png',
                'status' => 'inactive',
                'category_id' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            
            [
                'id' => 3,
                'name' => 'Fundraising Gala Ticket',
                'description' => 'Join us at our exclusive fundraising gala. Enjoy an evening of networking and campaign updates.',
                'slug' => 'fundraising-gala-ticket',
                'price' => 20000.00,
                'discount' => 2000.00,
                'image' => 'gala_202501041233.png',
                'status' => 'inactive',
                'category_id' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],


            [
                'id' => 4,
                'name' => 'Volunteer Kit',
                'description' => 'Essential kit for campaign volunteers, including a T-shirt, cap, and ID badge.',
                'slug' => 'volunteer-kit',
                'price' => 12000.00,
                'discount' => 2000.00,
                'image' => 'volunteer_kit_202501041234.png',
                'status' => 'inactive',
                'category_id' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'id' => 5,
                'name' => 'Campaign Flyers (Pack of 100)',
                'description' => 'High-quality campaign flyers to spread the message and engage voters.',
                'slug' => 'campaign-flyers',
                'price' => 10000.00,
                'discount' => 500.00,
                'image' => 'flyers_202501041235.png',
                'status' => 'inactive',
                'category_id' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'id' => 6,
                'name' => 'Digital Advertising Slot',
                'description' => 'Boost your campaign visibility with a one-week advertising slot on our platform. Designed for candidates in the same party, this slot helps promote your candidacy, recruit agents and volunteers, and engage voters. Ideal for candidates contesting for governorship, senate, house of reps, state assembly, chairmanship, or ward councilor positions.',
                'slug' => 'digital-advertising-slot',
                'price' => 50000.00,
                'discount' => 5000.00,
                'image' => 'advertising_slot_202501041236.png',
                'status' => 'inactive',
                'category_id' => 6,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            

            [
                'id' => 7,
                'name' => 'Silver Sponsorship Package',
                'description' => 'Become a campaign sponsor and enjoy exclusive recognition as part of our Silver package.',
                'slug' => 'silver-sponsorship-package',
                'price' => 100000.00,
                'discount' => 0.00,
                'image' => 'silver_sponsorship_202501041237.png',
                'status' => 'inactive',
                'category_id' => 7,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            
            
            
            
            
        ]);
    }
}

