<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceTemplateResource\Pages;
use App\Models\ServiceTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ServiceTemplateResource extends Resource
{
    protected static ?string $model = ServiceTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Service Operations';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    public static function canAccess(): bool
    {
        return in_array(auth()->user()?->role, ['owner', 'manager', 'advisor']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->rows(2)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('estimated_duration')
                            ->numeric()
                            ->suffix('minutes'),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true),
                    ])->columns(2),

                Forms\Components\Section::make('Template Items')
                    ->schema([
                        Forms\Components\Repeater::make('serviceTemplateItems')
                            ->relationship()
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->relationship('product', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                Forms\Components\TextInput::make('quantity')
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->minValue(1),
                                Forms\Components\TextInput::make('unit_price')
                                    ->numeric()
                                    ->prefix('$'),
                            ])
                            ->columns(3)
                            ->defaultItems(0),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->limit(50),
                Tables\Columns\TextColumn::make('estimated_duration')
                    ->suffix(' min'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('service_template_items_count')
                    ->counts('serviceTemplateItems')
                    ->label('Items'),
            ])
            ->defaultSort('name')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServiceTemplates::route('/'),
            'create' => Pages\CreateServiceTemplate::route('/create'),
            'edit' => Pages\EditServiceTemplate::route('/{record}/edit'),
        ];
    }
}
