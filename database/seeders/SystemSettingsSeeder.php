<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SystemSettingsSeeder extends Seeder
{
    /**
     * Run the seeder.
     *
     *
     */

    public function run(): void
    {
        $now = Carbon::now();
        DB::table('system_settings')->insert([
            'logo' => '',
            'favicon'=> 'favicon.png',
            'system_name' => 'Political Campaign Management Solution',
            'admin_email' => 'info@campaignmanager.ng',
            'system_currency' => 'NGN',
            'company_address' => '123 Almond St, Lagos, Nigeria',
            'company_phone' => '+1234567890',
            'activation_code' => '',
            'facebook' => 'https://www.facebook.com/ultimatepipsfrx',
            'twitter' => 'https://www.twitter.com/ultimatepipsfrx',
            'copyright' => 'Diginet Tech Africa Ltd',
            'disclaimer' => '',
            'tos' => '
            <h3>Terms of Service</h3>
            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nullam nec quam tristique, fermentum quam sit amet, ullamcorper turpis. Sed euismod vel libero id pellentesque. Suspendisse a felis et elit suscipit ultrices. Vivamus suscipit dui id ex tempus malesuada.</p>

            <p>Curabitur id velit eget eros feugiat vehicula. Sed sit amet enim eu elit varius laoreet. Integer non odio a turpis suscipit tincidunt. Vivamus interdum odio ut arcu suscipit, non condimentum justo iaculis.</p>

            <p>Vestibulum euismod sapien quis libero egestas, non fermentum velit commodo. In vel augue eget tellus facilisis dignissim. Nam id vestibulum nulla, eu efficitur nulla. Etiam feugiat nisl nec arcu tincidunt varius.</p>

            <p>Suspendisse potenti. Proin sit amet tincidunt dui. Fusce id justo vel massa luctus elementum non ut massa. Quisque et ex volutpat, pharetra mi at, tristique risus.</p>

            <p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Sed in lorem nec dolor dictum posuere id et odio. Curabitur accumsan dapibus purus, vel tincidunt ligula.</p>

            <p>Donec id tortor ipsum. Maecenas scelerisque fringilla mi non rhoncus. Nullam vitae eleifend odio. Fusce dignissim tortor nec purus tincidunt, et facilisis nisl interdum. Sed vitae scelerisque odio.</p>

            <p>Phasellus a augue ac elit dignissim auctor. In hac habitasse platea dictumst. Morbi vitae sollicitudin dui. Duis laoreet ut ligula eu consectetur. Vestibulum vel mauris et purus facilisis mattis.</p>

            <p>Mauris placerat, odio eget efficitur ultrices, tortor erat mattis odio, ac tristique nulla urna at justo. Vestibulum consectetur sem et nisi ullamcorper, non consequat nulla varius.</p>

            ',
            'privacy_policy' => '
            <h3>Privacy Policy</h3>
            <p>Your privacy is important to us. It is our policy to respect your privacy regarding any information we may collect from you across our website.</p>

            <h3>Information We Collect</h3>
            <p>We may collect personal information such as your name, email address, and other contact details when you interact with our website.</p>

            <h3>How We Use Your Information</h3>
            <p>We may use your personal information to provide you with our services, respond to your inquiries, and improve our website.</p>

            <h3>Cookie Policy</h3>
            <p>Our website may use cookies to improve your experience. You can manage or delete cookies according to your preferences.</p>

            <h3>Third-Party Links</h3>
            <p>Our website may contain links to third-party websites. We have no control over their content or privacy practices and assume no responsibility.</p>

            <h3>Security</h3>
            <p>We take reasonable steps to protect your personal information, but we cannot guarantee its absolute security.</p>

            <h3>Changes to this Policy</h3>
            <p>We may update our privacy policy from time to time. You are encouraged to check this page for any changes.</p>

            <h3>Contact Us</h3>
            <p>If you have any questions about our privacy policy, please contact us at privacy@example.com.</p>

            ',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
