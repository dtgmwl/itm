<?php

namespace App\Filament\Resources\Tasks;

use App\Enums\TaskPriority;
use App\Enums\TaskSource;
use App\Enums\TaskStatus;
use App\Filament\Resources\Tasks\Pages;
use App\Filament\Resources\Tasks\RelationManagers\LogsRelationManager;
use App\Filament\Resources\Tasks\RelationManagers\CommentsRelationManager;
use App\Models\Task;
use App\Services\TaskService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\{DatePicker, Placeholder, RichEditor, Select, Textarea, TextInput};
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\{Group, Section};
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Schemas\Components\Utilities\{Get, Set};
use App\Models\User;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static string|null|\UnitEnum $navigationGroup = 'Task Management';

     public static function getUpdateProgressAction(): Action
    {
        return Action::make('updateProgress')
            ->label('Update')
            ->icon('heroicon-m-arrow-path')
            ->color('warning')
            ->authorize('updateStatus')
            ->form([
                Section::make('Info')->schema([
                    Placeholder::make('last_note')->label('Catatan Terakhir')
                        ->content(function ($record) {
                            // Fix Lemot: Gak perlu refresh(), langsung ambil dari collection
                            return $record?->logs->first()?->note ?? 'Belum ada';
                        }),
                    Placeholder::make('last_updated')->label('Updated')
                        ->content(function ($record) {
                            return $record?->logs->first()?->created_at?->format('d M Y H:i') ?? '-';
                        }),
                ])->columns(2),

                Section::make('Update')->schema([
                    Select::make('status')
                        ->label('Status Baru')
                        // ================= SMART DROPDOWN =================
                        ->options(function ($record) {
                            if (!$record) return TaskStatus::class;

                            $allowedEnums = $record->status->allowedTransitions();
                            $allowedEnums[] = $record->status;

                            $options = [];
                            foreach ($allowedEnums as $enum) {
                                $options[$enum->value] = $enum->getLabel();
                            }

                            return $options;
                        })
                        ->default(fn($r) => $r ? $r->status->value : TaskStatus::Open->value)
                        ->required()
                        ->disableOptionWhen(fn ($value, $record) => $record && $record->status->value === $value)
                        ->helperText('Opsi dibatasi sesuai aturan SOP transisi status.'),

                    Textarea::make('note')
                        ->label('Catatan')
                        ->required()
                        ->maxLength(255),
                ]),
            ])
            ->action(function($data, $record){
                try {
                    $svc = app(TaskService::class);
                    $status = $data['status'] instanceof TaskStatus ? $data['status'] : TaskStatus::tryFrom($data['status']);

                    $svc->updateStatus($record, $status, auth()->user(), $data['note']);

                    Notification::make()->title('Progress updated')->success()->send();

                } catch (\Exception $e) {
                    Notification::make()
                        ->title('Gagal Update!')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Group::make()->schema([
                Section::make('Task Details')->schema([
                    TextInput::make('title')->rule('required')->maxLength(255),
                    RichEditor::make('description')->columnSpanFull(),
                ]),
            ])->columnSpan(2),

            Group::make()->schema([
                Section::make('Settings')->schema([
                    Select::make('status')
                        ->options(TaskStatus::class)
                        ->default(TaskStatus::Open->value)
                        ->required()
                        ->live()
                        ->hiddenOn('create'),

                    Select::make('priority')
                        ->options(TaskPriority::class)
                        ->default(TaskPriority::Medium->value)
                        ->required(),

                    Select::make('task_source')
                        ->label('Sumber Tugas')
                        ->options(TaskSource::class)
                        ->required()
                        ->default(function () {
        $user = auth()->user();

        // Cek dari hierarki paling tinggi dulu
        if ($user->hasRole('admin')) {
            return TaskSource::ExternalInstruction->value; // Lain / Eksternal
        }

        if ($user->isHeadDepartment()) {
            return TaskSource::HodInstruction->value; // KABID
        }

        if ($user->isStaff()) {
            return TaskSource::SelfInitiative->value; // Inisiatif Sendiri
        }

        // Fallback default kalau rolenya gak ada yang match
        return TaskSource::Routine->value;
    }),

                    Select::make('assignees')
                        ->label('Ditugaskan Kepada')
                        ->multiple() // INI KUNCI UTAMANYA! Otomatis jadi dropdown multi-select
                        ->relationship('assignees', 'name', function ($query) {
                            $query->whereDoesntHave('roles', fn ($q) => $q->where('name', 'admin'));
                        })
                        ->searchable()
                        ->preload()
                        ->live()
                        ->required()
                        ->default(function () {
                            $user = auth()->user();
                            if ($user->isStaff()) {
                                return [$user->id];
                            }
                            return [];
                        })
                        ->afterStateUpdated(function (array $state, Set $set, Get $get) {
                        // Karena sekarang $state adalah array berisi kumpulan ID staf
                            if (!empty($state)) {
                            // Ambil departemen dari staf pertama yang dipilih
                                $user = \App\Models\User::find($state[0]);
                                if ($user && $user->department_id) {
                                    $set('department_id', $user->department_id);
                                }

                                // Jika status masih Open, otomatis ganti ke Assigned karena sudah ada orangnya
                                $currentStatus = $get('status');
                                if ($currentStatus == TaskStatus::Open) {
                                    $set('status', TaskStatus::Assigned->value);
                                }
                            } else {
                                $set('department_id', null);
                            }
                        })
                        ->rules([
                            fn () => function (string $attribute, $value, \Closure $fail) {
                                $user = auth()->user();
                                if (!$user->hasRole('staff')) {
                                    return;
                                }

                                $assigneeIds = (array) $value;
                                if (empty($assigneeIds)) return;

                                $hasHod = \App\Models\User::whereIn('id', $assigneeIds)
                                    ->role('head_department')
                                    ->exists();

                                if ($hasHod && !in_array($user->id, $assigneeIds)) {
                                    $fail('Staf tidak diperbolehkan menugaskan HOD kecuali untuk kolaborasi (Staf pembuat harus ikut serta sebagai assignee).');
                                }
                            },
                        ]),

                    Select::make('department_id')
                        ->relationship('department','name')
                        ->required()
                        ->live()
                        ->default(function () {
                            $user = auth()->user();
                            if ($user->isStaff() && $user->department_id) {
                                return $user->department_id;
                            }
                            return null;
                        }),

                    DatePicker::make('due_date')
                        ->native(false)
                        ->required()
                        ->default(fn() => request()->query('due_date')),
                ]),
            ])->columnSpan(1),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->sortable()->weight('bold')->limit(40),
                TextColumn::make('status')->badge(),
                TextColumn::make('priority')->badge(),
                TextColumn::make('task_source')
                    ->label('Sumber')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state?->getLabel() ?? '-')
                    ->color(fn ($state) => $state?->getColor() ?? 'gray')
                    ->icon(fn ($state) => $state?->getIcon()),
                TextColumn::make('assignees_list') // Sengaja pake nama palsu biar kita bisa custom isinya
    ->label('Assignees')
    ->badge()
    ->getStateUsing(function (Task $record) {
        $names = [];

        // 1. Ambil dari sistem baru (Tabel Pivot - Multiple Staff)
        foreach ($record->assignees as $assignee) {
            // Ambil kata pertama aja (Nama depan)
            $names[] = explode(' ', $assignee->name)[0];
        }

        // 2. Ambil dari sistem lama (Kalau array kosong, berarti ini task jadul)
        if (empty($names) && $record->assignedTo) {
            $names[] = explode(' ', $record->assignedTo->name)[0];
        }

        return empty($names) ? 'Unassigned' : $names;
    })
    ->searchable(query: function (Builder $query, string $search) {
        // Biar tetep bisa dicari lewat kotak Search Filament (gabungin 2 relasi)
        $query->whereHas('assignees', function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%");
        })->orWhereHas('assignedTo', function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%");
        });
    })
    ->hidden(function () {
        $user = auth()->user();
        if (method_exists($user, 'hasRole') && $user->hasRole('admin')) return false;
        if (method_exists($user, 'isHeadDepartment') && $user->isHeadDepartment()) return false;
        return true;
    }),

                TextColumn::make('due_date')->date()->sortable()
                    ->color(fn(?Task $r) => $r?->isOverdue() ? 'danger' : 'gray'),
                TextColumn::make('new_status')
                    ->label('Progress')
                    ->getStateUsing(function (Task $record) {
                        // Fix Lemot: hapus tanda kurung di logs()
                        return $record->logs->first()?->note ?? '-';
                    })
                    ->limit(20)
                    ->tooltip(fn(Task $record) => $record->logs->first()?->note),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(TaskStatus::class),
                Tables\Filters\SelectFilter::make('task_source')
                    ->label('Sumber Tugas')
                    ->options(TaskSource::class),
                Tables\Filters\SelectFilter::make('assignees')
                    ->label('Ditugaskan Kepada')
                    ->multiple() // Bisa filter lebih dari 1 staf
                    ->relationship('assignees', 'name')
                    ->searchable()
                    ->preload()
                    // Opsional: Sembunyiin juga filternya kalau dia Staf (ngapain filter orang lain kan?)
                    ->hidden(function () {
                        $user = auth()->user();
                        if (method_exists($user, 'hasRole') && $user->hasRole('admin')) return false;
                        if (method_exists($user, 'isHeadDepartment') && $user->isHeadDepartment()) return false;
                        return true;
                    }),
                Tables\Filters\Filter::make('overdue')->toggle()
                    ->query(fn(Builder $q)=>$q->where('due_date','<',now())->whereNotIn('status',['completed','cancelled'])),
            ])
            ->actions([
                static::getUpdateProgressAction()
                ->hidden(fn() => auth()->user()->isHeadDepartment()),

                \Filament\Actions\Action::make('discussion')
                    ->label('Chat')
                    ->icon('heroicon-m-chat-bubble-left-right')
                    ->color('info')
                    ->modalWidth('lg')
                    ->modalHeading(fn($record) => 'Ruang Diskusi: ' . $record->title)
                    ->modalContent(fn($record) => view('livewire.task-discussion-container', ['taskId' => $record->id]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(false),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [
            LogsRelationManager::class,
            CommentsRelationManager::class,
            RelationManagers\AttachmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'=>Pages\ListTasks::route('/'),
            'create'=>Pages\CreateTask::route('/create'),
            'view'=>Pages\ViewTask::route('/{record}'),
            'edit'=>Pages\EditTask::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['logs' => function ($q) {
            $q->latest();
        }, 'assignees', 'assignedTo']);

        $user = auth()->user();

        if (method_exists($user, 'hasRole') && $user->hasRole('admin')) {
            return $query;
        }

        if (method_exists($user, 'isHeadDepartment') && $user->isHeadDepartment()) {
            return $query->where(function ($q) use ($user) {
                $q->where('department_id', $user->department_id)
                  ->orWhereNull('department_id');
            });
        }

        return $query->where(function (Builder $q) use ($user) {
            $q->where('assigned_to', $user->id)
            ->orWhereHas('assignees', function (Builder $q2) use ($user) {
                $q2->where('users.id', $user->id);
            })
            ->orWhereNull('department_id');
        });
    }
}


