<?php

namespace App\Traits;

use App\Models\JournalEntry;

trait HasOperationalJournals
{
    public function journals()
    {
        return $this->morphMany(JournalEntry::class, 'source');
    }
}
