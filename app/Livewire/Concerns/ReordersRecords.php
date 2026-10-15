<?php

namespace App\Livewire\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Move a record one place up or down in a list ordered by its "sort" column.
 */
trait ReordersRecords
{
    protected function moveRecord(Model $record, int $direction): void
    {
        $items = $record->newQuery()->orderBy('sort')->orderBy('id')->get();
        $index = $items->search(fn (Model $item): bool => $item->is($record));
        $target = $index + ($direction < 0 ? -1 : 1);

        if ($index === false || $target < 0 || $target >= $items->count()) {
            return;
        }

        $ordered = $items->values()->all();
        [$ordered[$index], $ordered[$target]] = [$ordered[$target], $ordered[$index]];

        DB::transaction(function () use ($ordered): void {
            foreach ($ordered as $position => $item) {
                if ($item->sort !== $position + 1) {
                    $item->forceFill(['sort' => $position + 1])->saveQuietly();
                }
            }
        });
    }
}
