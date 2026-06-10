<?php

namespace App\Support;

class NigeriaRegionMap
{
    private const MAP = [
        'Abia' => 'South East',
        'Adamawa' => 'North East',
        'Akwa Ibom' => 'South South',
        'Anambra' => 'South East',
        'Bauchi' => 'North East',
        'Bayelsa' => 'South South',
        'Benue' => 'North Central',
        'Borno' => 'North East',
        'Cross River' => 'South South',
        'Delta' => 'South South',
        'Ebonyi' => 'South East',
        'Edo' => 'South South',
        'Ekiti' => 'South West',
        'Enugu' => 'South East',
        'Fct' => 'North Central',
        'Federal Capital Territory' => 'North Central',
        'Gombe' => 'North East',
        'Imo' => 'South East',
        'Jigawa' => 'North West',
        'Kaduna' => 'North West',
        'Kano' => 'North West',
        'Katsina' => 'North West',
        'Kebbi' => 'North West',
        'Kogi' => 'North Central',
        'Kwara' => 'North Central',
        'Lagos' => 'South West',
        'Nasarawa' => 'North Central',
        'Niger' => 'North Central',
        'Ogun' => 'South West',
        'Ondo' => 'South West',
        'Osun' => 'South West',
        'Oyo' => 'South West',
        'Plateau' => 'North Central',
        'Rivers' => 'South South',
        'Sokoto' => 'North West',
        'Taraba' => 'North East',
        'Yobe' => 'North East',
        'Zamfara' => 'North West',
    ];

    public static function regionFor(?string $state): string
    {
        $state = InecNameFormatter::display($state);

        return self::MAP[$state] ?? 'Unknown';
    }
}
