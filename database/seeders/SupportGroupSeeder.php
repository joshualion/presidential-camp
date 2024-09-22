<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SupportGroup;
use Illuminate\Support\Str;

class SupportGroupSeeder extends Seeder
{
    public function run()
    {
        $supportGroups = [
            [
                'uuid' => (string) Str::uuid(), // Generate UUID here
                'name' => 'Media & ICT',
                'description' => 'Support group for media and information communication technology.',
            ],
            [
                'uuid' => (string) Str::uuid(), // Generate UUID here
                'name' => 'Strategic Communication',
                'description' => 'Support group for strategic communication planning and execution.',
            ],
            [
                'uuid' => (string) Str::uuid(), // Generate UUID here
                'name' => 'Grassroots Mobilization',
                'description' => 'Support group focused on grassroots mobilization and organization.',
            ],
            [
                'uuid' => (string) Str::uuid(), // Generate UUID here
                'name' => 'Diaspora Engagement',
                'description' => 'Support group dedicated to engaging with the diaspora community.',
            ],
            [
                'uuid' => (string) Str::uuid(), // Generate UUID here
                'name' => 'Research Team',
                'description' => 'Support group responsible for conducting research and analysis.',
            ],
            [
                'uuid' => (string) Str::uuid(), // Generate UUID here
                'name' => 'Women Wing',
                'description' => 'Support group for women empowerment and engagement.',
            ],
            [
                'uuid' => (string) Str::uuid(), // Generate UUID here
                'name' => 'Youth Wing',
                'description' => 'Support group focused on youth engagement and participation.',
            ],
            [
                'uuid' => (string) Str::uuid(), // Generate UUID here
                'name' => 'Decide Later',
                'description' => 'Support group placeholder for undecided groups.',
            ],
        ];

        foreach ($supportGroups as $group) {
            SupportGroup::create($group);
        }
    }
}
