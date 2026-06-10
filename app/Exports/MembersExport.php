<?php
namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MembersExport implements FromCollection, WithHeadings, WithColumnFormatting, WithStyles
{
    protected $members;
    protected $headers;

    public function __construct($members, $headers)
    {
        $this->members = $members;
        $this->headers = $headers;
    }

    public function collection()
    {
        return $this->members->map(function ($member) {
            $row = [];

            foreach ($this->headers as $header) {
                switch ($header) {
                    case 'role':
                        $row['role'] = $member->roles->pluck('name')->implode(', ');
                        break;
                    case 'region':
                        $row['region'] = optional($member->region)->name;
                        break;
                    case 'state':
                        $row['state'] = optional($member->state)->name;
                        break;
                    case 'lga':
                        $row['lga'] = optional($member->lga)->name;
                        break;
                    case 'ward':
                        $row['ward'] = optional($member->ward)->name;
                        break;
                    case 'polling_unit':
                        $row['polling_unit'] = optional($member->pollingUnit)->name;
                        break;
                    case 'phone':
                        // Force Excel to treat as text (leading apostrophe not shown in Excel)
                        $phone = (string) $member->phone;
                        $row['phone'] = !empty($phone) ? "'" . $phone : '';
                        break;
                    case 'bank_account_number':
                    case 'vin':
                        // Also force text for sensitive numeric fields
                        $value = (string) $member->{$header};
                        $row[$header] = !empty($value) ? "'" . $value : '';
                        break;
                    default:
                        $row[$header] = $member->{$header};
                        break;
                }
            }

            return $row;
        });
    }

    public function headings(): array
    {
        // Format headings as ALL CAPS
        return array_map(function ($h) {
            return strtoupper(str_replace('_', ' ', $h));
        }, $this->headers);
    }

    public function columnFormats(): array
    {
        $formats = [];

        // Force text format for phone, vin, and bank_account_number
        foreach ($this->headers as $index => $header) {
            if (in_array($header, ['phone', 'vin', 'bank_account_number'])) {
                $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
                $formats[$columnLetter] = NumberFormat::FORMAT_TEXT;
            }
        }

        return $formats;
    }

    public function styles(Worksheet $sheet)
    {
        // Bold + uppercase headings row
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
