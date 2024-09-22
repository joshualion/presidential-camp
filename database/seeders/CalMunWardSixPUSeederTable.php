<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;

class CalMunWardSixPUSeederTable extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $pollingUnitsData = [
            ['name' => 'NSISUK NUR. SCHOOL', 'ward_id' => 6, 'uuid' => (string) Str::uuid(), 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'NSISUK JUNCTION NEAR APOSTOLIC CHURCH', 'ward_id' => 6, 'uuid' => (string) Str::uuid(), 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => "NEAR GOD'S HERITAGE CENTRE, MCC ROAD", 'ward_id' => 6, 'uuid' => (string) Str::uuid(), 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'IBOK STREET/CAMEROUN CONS.', 'ward_id' => 6, 'uuid' => (string) Str::uuid(), 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'MIDDLE OF NSEMO BY PRY SCH ANNEX, NSEMO STREET', 'ward_id' => 6, 'uuid' => (string) Str::uuid(), 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'TOWN HALL AKAI EFFA', 'ward_id' => 6, 'uuid' => (string) Str::uuid(), 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'NEAR RCC JUNCTION', 'ward_id' => 6, 'uuid' => (string) Str::uuid(), 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'OBORI DRIVE NEAR ZION ROCK CHURCH', 'ward_id' => 6, 'uuid' => (string) Str::uuid(), 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'NAVY QUARTER', 'ward_id' => 6, 'uuid' => (string) Str::uuid(), 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'ODUKPANI RD/IKOT EKA-IDEM', 'ward_id' => 6, 'uuid' => (string) Str::uuid(), 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'IKOT EKA-IDEM NEAR SANTIFIED MOUNT ZION', 'ward_id' => 6, 'uuid' => (string) Str::uuid(), 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'PRIMARY SCHOOL, ISHIE TOWN I', 'ward_id' => 6, 'uuid' => (string) Str::uuid(), 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'PRIMARY SCHOOL, ISHIE TOWN II', 'ward_id' => 6, 'uuid' => (string) Str::uuid(), 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'ISHIE TOWN HALL', 'ward_id' => 6, 'uuid' => (string) Str::uuid(), 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'OPEN SPACE ASARI ESO LAYOUT /ADRIAN SUITES', 'ward_id' => 6, 'uuid' => (string) Str::uuid(), 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'OPEN SPACE ASARI ESO LAYOUT II/CEMENTRY', 'ward_id' => 6, 'uuid' => (string) Str::uuid(), 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'OPEN SPACE BY PIKK MASTER HOTEL', 'ward_id' => 6, 'uuid' => (string) Str::uuid(), 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'OPEN SPACE ASARI ESO LAYOUT/UDIBA CRESCENT JUNC', 'ward_id' => 6, 'uuid' => (string) Str::uuid(), 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'OPEN SPACE ASARI ESO LAYOUT /MCC JUNC.', 'ward_id' => 6, 'uuid' => (string) Str::uuid(), 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => "OPEN SPACE NEW GOD'S HERITAGE", 'ward_id' => 6, 'uuid' => (string) Str::uuid(), 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'OPEN SPACE EFFIONG BASSEY/ JOHNSON ISHIE', 'ward_id' => 6, 'uuid' => (string) Str::uuid(), 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'OPEN SPACE IKOT UDUAK/IKOT EYO SQUARE', 'ward_id' => 6, 'uuid' => (string) Str::uuid(), 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'OPEN SPACE EDEM EFFIONG/OTU STREET', 'ward_id' => 6, 'uuid' => (string) Str::uuid(), 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'OPEN SPACE ETAP AYIP LAYOUT/NTUFAM FRANCIS EFFIOM', 'ward_id' => 6, 'uuid' => (string) Str::uuid(), 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'OPEN SPACE MCC ROAD/GOODLUCK JONATHAN BYE PASS', 'ward_id' => 6, 'uuid' => (string) Str::uuid(), 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'OPEN SPACE POWERLINE/CHIEF OKOKON ETON CLOSE JUNC.', 'ward_id' => 6, 'uuid' => (string) Str::uuid(), 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'CALABAR CALABAR OPEN SPACE, RCC JUNC. REDEEMED CHURCH', 'ward_id' => 6, 'uuid' => (string) Str::uuid(), 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'OPEN SPACE IKOT ABASI OBORI/ ASSEMBLIES OF GOD CHURCH JUNC.', 'ward_id' => 6, 'uuid' => (string) Str::uuid(), 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'OPEN SPACE OBORI DRIVE/BEEBOBSCO', 'ward_id' => 6, 'uuid' => (string) Str::uuid(), 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'OPEN SPACE OTU STREET/ISHIE LAYOUT', 'ward_id' => 6, 'uuid' => (string) Str::uuid(), 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'OPEN SPACE ISHIE LAYOUT OFF EFFIOM ETTE/SHINNING LIGHT ACADEMY', 'ward_id' => 6, 'uuid' => (string) Str::uuid(), 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'OPEN SPACE ISHIE SQUARE 1', 'ward_id' => 6, 'uuid' => (string) Str::uuid(), 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'OPEN SPACE ISHIE SQUARE 11', 'ward_id' => 6, 'uuid' => (string) Str::uuid(), 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'OPEN SPACE AKAI STREET OFFIONG', 'ward_id' => 6, 'uuid' => (string) Str::uuid(), 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            // ... Add more data
        ];

        foreach ($pollingUnitsData as $data) {
            DB::table('polling_units')->insert($data);
        }
    }
}



