<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\CustomerCommunication;
use Filament\Forms;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class CommunicationCenter extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationGroup = 'Communication';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Communication Center';

    protected static string $view = 'filament.pages.communication-center';

    public static function canAccess(): bool
    {
        return in_array(auth()->user()?->role, ['owner', 'manager', 'advisor']);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(CustomerCommunication::query()->with(['customer', 'sentBy']))
            ->columns([
                Tables\Columns\TextColumn::make('customer.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('communication_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'call' => 'info',
                        'sms' => 'success',
                        'email' => 'primary',
                        'whatsapp' => 'success',
                        'in_person' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('direction')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'inbound' ? 'info' : 'success'),
                Tables\Columns\TextColumn::make('subject'),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
                Tables\Columns\TextColumn::make('sentBy.name')
                    ->label('Sent By'),
                Tables\Columns\TextColumn::make('sent_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('communication_type')
                    ->options([
                        'call' => 'Call',
                        'sms' => 'SMS',
                        'email' => 'Email',
                        'whatsapp' => 'WhatsApp',
                        'in_person' => 'In Person',
                    ]),
                Tables\Filters\SelectFilter::make('direction')
                    ->options([
                        'inbound' => 'Inbound',
                        'outbound' => 'Outbound',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'sent' => 'Sent',
                        'delivered' => 'Delivered',
                        'failed' => 'Failed',
                        'read' => 'Read',
                    ]),
                Tables\Filters\SelectFilter::make('customer_id')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Customer'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->model(CustomerCommunication::class)
                    ->form([
                        Forms\Components\Select::make('customer_id')
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
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
                            ->rows(4),
                    ])
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['tenant_id'] = auth()->user()?->tenant_id;
                        $data['sent_by'] = auth()->id();
                        $data['status'] = 'pending';

                        return $data;
                    }),
            ]);
    }
}
