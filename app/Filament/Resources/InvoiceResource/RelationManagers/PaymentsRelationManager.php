<?php

declare(strict_types=1);

namespace App\Filament\Resources\InvoiceResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('payment_date')
                    ->required()
                    ->default(now()),
                Forms\Components\TextInput::make('amount')
                    ->numeric()
                    ->prefix('$')
                    ->required(),
                Forms\Components\Select::make('payment_method')
                    ->options([
                        'cash' => 'Cash',
                        'card' => 'Card',
                        'bank_transfer' => 'Bank Transfer',
                        'cheque' => 'Cheque',
                        'mobile_money' => 'Mobile Money',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('transaction_reference')
                    ->maxLength(255),
                Forms\Components\Textarea::make('notes')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('payment_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->money('USD'),
                Tables\Columns\TextColumn::make('payment_method')
                    ->badge(),
                Tables\Columns\TextColumn::make('transaction_reference'),
                Tables\Columns\TextColumn::make('receivedBy.name')
                    ->label('Received By'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime(),
            ])
            ->defaultSort('payment_date', 'desc')
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['tenant_id'] = auth()->user()?->tenant_id;
                        $data['received_by'] = auth()->id();

                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
