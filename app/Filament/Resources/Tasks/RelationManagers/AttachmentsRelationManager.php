<?php

namespace App\Filament\Resources\Tasks\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Actions\CreateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Model;

class AttachmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'attachments';
    protected static ?string $title = 'File Lampiran';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('original_name')
            ->columns([
                Tables\Columns\TextColumn::make('original_name')
                    ->label('Nama File')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('uploader.name')
                    ->label('Oleh')
                    ->sortable(),

                Tables\Columns\TextColumn::make('file_size')
                    ->label('Ukuran')
                    ->formatStateUsing(fn ($state) => number_format($state / 1024, 1) . ' KB'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu Unggah')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->headerActions([
                Action::make('tambah_file')
                    ->label('Tambah File')
                    ->icon('heroicon-m-plus')
                    ->form([
                        Forms\Components\FileUpload::make('file_path')
                            ->label('Pilih File')
                            ->required()
                            ->disk('public')
                            ->directory('task-attachments')
                            ->maxSize(10240)
                            ->storeFileNamesIn('original_name')
                            ->columnSpanFull(),
                    ])
                    ->action(function (array $data, RelationManager $livewire) {
                        $data['uploaded_by'] = auth()->id();
                        $data['disk'] = 'public';

                        if (isset($data['file_path'])) {
                            $data['file_size'] = Storage::disk('public')->size($data['file_path']);
                            $data['mime_type'] = Storage::disk('public')->mimeType($data['file_path']);
                        }

                        $record = $livewire->getOwnerRecord()->attachments()->create($data);

                        event(new \App\Events\TaskAttachmentUploaded($livewire->getOwnerRecord(), auth()->user(), $record));

                        \Filament\Notifications\Notification::make()
                            ->title('File berhasil diunggah')
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Action::make('view')
                    ->label('Lihat')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn (Model $record) => route('attachments.show', $record))
                    ->openUrlInNewTab(),

                Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(function (Model $record) {
                        return Storage::disk($record->disk)->download($record->file_path, $record->original_name);
                    }),

                DeleteAction::make()
                    ->after(function (Model $record) {
                        if (Storage::disk($record->disk)->exists($record->file_path)) {
                            Storage::disk($record->disk)->delete($record->file_path);
                        }
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
