<?php

namespace App\Tables\Servers;

use Forjed\InertiaTable\Column;
use Forjed\InertiaTable\Columns\ActionsColumn;
use Forjed\InertiaTable\Columns\DateTimeColumn;
use Forjed\InertiaTable\Columns\EnumColumn;
use Forjed\InertiaTable\Columns\TextColumn;
use Forjed\InertiaTable\Table;

class ErrorIssueTable extends Table
{
    protected function query(): void
    {
        $this->perPage = config('web.pagination_size');
        $this->query->latest('last_seen_at');
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('exception_class', 'Exception')
                ->sortable(),
            TextColumn::make('normalized_message', 'Message')
                ->sortable(),
            TextColumn::make('events_count', 'Events')
                ->sortable(),
            TextColumn::make('affected_users', 'Users')
                ->sortable(),
            EnumColumn::make('status', 'Status')
                ->sortable(),
            DateTimeColumn::make('first_seen_at', 'First Seen')
                ->sortable()
                ->toLocal(),
            DateTimeColumn::make('last_seen_at', 'Last Seen')
                ->sortable()
                ->toLocal(),
            Column::data('id'),
            Column::data('server_id'),
            ActionsColumn::make(),
        ];
    }
}
