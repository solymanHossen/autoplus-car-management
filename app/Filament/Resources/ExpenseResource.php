<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ExpenseResource\Pages;
use App\Models\Expense;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?string $navigationGroup = 'Financial';

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasPermission('view-expenses') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\DatePicker::make('expense_date')
                            ->required()
                            ->default(now()),
                        Forms\Components\Select::make('category')
                            ->options([
                                'rent' => 'Rent',
                                'utilities' => 'Utilities',
                                'salary' => 'Salary',
                                'parts_purchase' => 'Parts Purchase',
                                'equipment' => 'Equipment',
                                'marketing' => 'Marketing',
                                'insurance' => 'Insurance',
                                'other' => 'Other',
                            ])
                            ->required(),
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
                            ]),
                        Forms\Components\Select::make('supplier_id')
                            ->relationship('supplier', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('receipt_url')
                            ->label('Receipt')
                            ->directory('receipts')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('expense_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_method')
                    ->badge(),
                Tables\Columns\TextColumn::make('supplier.name'),
                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Created By'),
                Tables\Columns\IconColumn::make('approved_at')
                    ->label('Approved')
                    ->boolean()
                    ->getStateUsing(fn (Expense $record) => $record->approved_at !== null),
            ])
            ->defaultSort('expense_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'rent' => 'Rent',
                        'utilities' => 'Utilities',
                        'salary' => 'Salary',
                        'parts_purchase' => 'Parts Purchase',
                        'equipment' => 'Equipment',
                        'marketing' => 'Marketing',
                        'insurance' => 'Insurance',
                        'other' => 'Other',
                    ]),
                Tables\Filters\SelectFilter::make('payment_method')
                    ->options([
                        'cash' => 'Cash',
                        'card' => 'Card',
                        'bank_transfer' => 'Bank Transfer',
                        'cheque' => 'Cheque',
                        'mobile_money' => 'Mobile Money',
                    ]),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn () => auth()->user()?->hasPermission('edit-expenses')),
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Expense $record) => $record->approved_at === null && in_array(auth()->user()?->role, ['owner', 'manager']))
                    ->requiresConfirmation()
                    ->action(function (Expense $record) {
                        $record->update([
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Expense Approved')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->hasPermission('delete-expenses')),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExpenses::route('/'),
            'create' => Pages\CreateExpense::route('/create'),
            'edit' => Pages\EditExpense::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
