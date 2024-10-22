<?php

namespace App\Exports;


use Maatwebsite\Excel\Concerns\WithHeadings;

class PollingUnitExport implements  WithHeadings
{
   
     /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'name',        // Name of the polling unit
            'ward_name',   // Ward name where the polling unit is located
            'local_government', // Local government name
            'state',           // State name
            'remarks',     // Optional remarks
            'user_id',     // Optional user ID (nullable)
        ];
    }
    
}
