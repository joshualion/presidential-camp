<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
class SMTPSettingsSeeder extends Seeder
{
    /**
     * Run the seeder.
     */
    public function run(): void
    {
        $now = Carbon::now();
        
        DB::table('s_m_t_p_settings')->insert([
            'mailer' => 'smtp',
            'host' => 'smtp.gmail.com',
            'port' => '587', // You can use 587 for TLS or 465 for SSL
            'username' => 'youremail@gmail.com', // Replace with your Gmail email
            'password' => 'your-app-password', // Replace with your App Password
            'encryption' => 'tls', // Use 'ssl' if you prefer SSL encryption
            'from_address' => 'youremail@gmail.com', // Replace with your Gmail email
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

