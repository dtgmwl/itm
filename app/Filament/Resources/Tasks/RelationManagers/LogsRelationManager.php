<?php

namespace App\Filament\Resources\Tasks\RelationManagers;

use App\Filament\Resources\Tasks\TaskResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class LogsRelationManager extends RelationManager
{
    protected static string $relationship = 'logs';

    /* protected static ?string $relatedResource = TaskResource::class; */

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->dateTime()
                    ->label('Waktu'),
                TextColumn::make('note')
                    ->label('Catatan'),
                TextColumn::make('new_status')
                    ->label('Update Terakhir')
            ]);
    }
}
