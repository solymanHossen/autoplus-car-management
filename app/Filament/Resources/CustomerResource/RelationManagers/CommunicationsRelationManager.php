<?php

declare(strict_types=1);

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class CommunicationsRelationManager extends RelationManager
{
    protected static string $relationship = 'communications';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('communication_type')
                    ->options([
                        'call' => 'Call',
                        'sms' => 'SMS',
                        'email' => 'Email',
                        'whatsapp' => 'WhatsApp',
                        'in_person' => 'In Person',
                    ])
                    ->required(),
                Forms\Components\Select::make('direction')
                    ->options([
                        'inbound' => 'Inbound',
                        'outbound' => 'Outbound',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('subject')
                    ->maxLength(255),
                Forms\Components\Textarea::make('message')
                    ->required()
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('communication_type')
                    ->badge(),
                Tables\Columns\TextColumn::make('direction')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'inbound' ? 'info' : 'success'),
                Tables\Columns\TextColumn::make('subject'),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
                Tables\Columns\TextColumn::make('sent_at')
                    ->dateTime(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['sent_by'] = auth()->id();
                        $data['status'] = 'pending';

                        return $data;
                    }),
            ]);
    }
}
