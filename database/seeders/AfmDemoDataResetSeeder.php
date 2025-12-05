<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CompletionFlag;
use App\Models\SisCourseRef;

class AfmDemoDataResetSeeder extends Seeder
{
    /**
     * Reset AFM demo data to clean state
     */
    public function run(): void
    {
        // Remove all completion flags
        CompletionFlag::truncate();
        
        // Remove all course references for demo term
        SisCourseRef::where('term_code', '202410')->delete();
        
        $this->command?->info('AFM demo data reset complete.');
        $this->command?->info('Removed all completion flags and course references for term 202410.');
    }
}
