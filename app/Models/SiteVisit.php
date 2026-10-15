<?php

namespace App\Models;

use App\Casts\DateOnly;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * One unique website visitor on one day.
 */
#[Fillable(['visit_date', 'visitor_hash', 'hits', 'landing_path'])]
class SiteVisit extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'visit_date' => DateOnly::class,
            'hits' => 'integer',
        ];
    }
}
