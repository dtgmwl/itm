<?php

namespace App\Filament\Resources\Tasks\RelationManagers;

use App\Filament\Resources\Tasks\TaskResource;
use Filament\Forms\Components\Textarea;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class CommentsRelationManager extends RelationManager
{
    protected static string $relationship = 'comments';

    /* protected static ?string $relatedResource = TaskResource::class; */

    protected static ?string $title = 'Diskusi & Komentar';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Textarea::make('comment')
                ->label('Komentar')
                ->placeholder('Tulis komentar atau koordinasi di sini...')
                ->required()
                ->rows(4)
                ->maxLength(65535)
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('comment')

            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Pengirim')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('comment')
                    ->label('Isi Komentar')
                    ->wrap()
                    ->limit(120)
                    ->tooltip(fn ($record) => $record->comment),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->since(),
            ])

            ->defaultSort('created_at', 'desc')

            ->headerActions([
                CreateAction::make()
                    ->label('Tambah Komentar')
                    ->icon('heroicon-m-chat-bubble-left-right')

                    ->mutateFormDataUsing(function (array $data): array {
                        $data['user_id'] = auth()->id();

                        return $data;
                    }),
            ])

            ->actions([
                EditAction::make()
                    ->visible(
                        fn ($record) =>
                            auth()->id() === $record->user_id
                    ),

                DeleteAction::make()
                    ->visible(
                        fn ($record) =>
                            auth()->id() === $record->user_id
                    ),
            ])

            ->emptyStateHeading('Belum ada komentar')
            ->emptyStateDescription(
                'Diskusi task akan muncul di sini.'
            )
            ->emptyStateIcon('heroicon-o-chat-bubble-left-right');
    }
}

