<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make()->schema([
                    Section::make('General Information')
                        ->schema([
                            TextInput::make('name')
                                ->required(),
                            TextInput::make('email')
                                ->label('Email Address')
                                ->email()
                                ->required()
                                ->unique(ignoreRecord: true),
                            Select::make('department_id')
                                ->relationship('department', 'name')
                                ->searchable()
                                ->preload(),
                        ])->columns(2),

                    Section::make('Security')
                        ->schema([
                            TextInput::make('password')
                                ->password()
                                ->required(fn (string $context): bool => $context === 'create')
                                ->dehydrated(fn ($state) => filled($state))
                                ->revealable(),
                            Select::make('roles')
                                ->multiple()
                                ->relationship('roles', 'name')
                                ->preload(),
                        ])->columns(2),
                ])->columnSpan(2),

                Group::make()->schema([
                    Section::make('Status & Avatar')
                        ->schema([
                            FileUpload::make('avatar')
                                ->avatar()
                                ->imageEditor()
                                ->directory('avatars'),
                            Toggle::make('is_active')
                                ->label('Active Account')
                                ->default(true)
                                ->required(),
                        ]),
                ])->columnSpan(1),
            ])->columns(3);
    }
}
