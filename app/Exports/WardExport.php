<?php

namespace App\Exports;


use Maatwebsite\Excel\Concerns\WithHeadings;

class WardExport implements  WithHeadings
{
   
     /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'name',        // Name of the polling unit
            'local_government', // Local government name
            'state',           // State name
            'user_id',     // Optional user ID (nullable)
        ];
    }
    
}
