<?php

declare(strict_types=1);

namespace App\Filament\Resources\VehicleResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ServiceRemindersRelationManager extends RelationManager
{
    protected static string $relationship = 'serviceReminders';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('reminder_type')
                    ->required()
                    ->maxLength(255),
                Forms\Components\DatePicker::make('due_date'),
                Forms\Components\TextInput::make('due_mileage')
                    ->numeric(),
                Forms\Components\Textarea::make('service_description')
                    ->columnSpanFull(),
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'sent' => 'Sent',
                        'completed' => 'Completed',
                    ])
                    ->default('pending'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reminder_type'),
                Tables\Columns\TextColumn::make('due_date')
                    ->date(),
                Tables\Columns\TextColumn::make('due_mileage')
                    ->suffix(' km'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'sent' => 'info',
                        'completed' => 'success',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('due_date')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
