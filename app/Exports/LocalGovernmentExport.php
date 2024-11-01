<?php

namespace App\Exports;


use Maatwebsite\Excel\Concerns\WithHeadings;

class LocalGovernmentExport implements  WithHeadings
{
   
     /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'name',        // Name of the polling unit
            'state',           // State name
            'user_id',     // Optional user ID (nullable)
        ];
    }
    
}
