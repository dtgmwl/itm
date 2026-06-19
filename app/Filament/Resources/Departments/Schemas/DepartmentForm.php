<?php

namespace App\Filament\Resources\Departments\Schemas;

use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class DepartmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Department Information')
                    ->description('Details about the department.')
                    ->schema([
                        TextInput::make('code')
                            ->label('Department Code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->placeholder('e.g. IT-01'),
                        TextInput::make('name')
                            ->label('Department Name')
                            ->required()
                            ->placeholder('e.g. Information Technology'),
                    ])->columns(2),
            ]);
    }
}
