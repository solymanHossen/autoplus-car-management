<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\AuditLog;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class AuditLogViewer extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-magnifying-glass';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 11;

    protected static ?string $title = 'Audit Logs';

    protected static string $view = 'filament.pages.audit-log-viewer';

    public static function canAccess(): bool
    {
        return in_array(auth()->user()?->role, ['owner', 'manager']);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(AuditLog::query()->with(['user']))
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Timestamp')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable(),
                Tables\Columns\TextColumn::make('action')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        'viewed' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('model_type')
                    ->label('Model')
                    ->formatStateUsing(fn ($state) => class_basename($state ?? '')),
                Tables\Columns\TextColumn::make('model_id')
                    ->label('Record ID'),
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('action')
                    ->options([
                        'created' => 'Created',
                        'updated' => 'Updated',
                        'deleted' => 'Deleted',
                        'viewed' => 'Viewed',
                    ]),
                Tables\Filters\SelectFilter::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->label('User'),
            ]);
    }
}
