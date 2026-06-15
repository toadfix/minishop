<?php

namespace Minishop\Filament\Resources;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Minishop\Enums\ReviewStatus;
use Minishop\Filament\Resources\ReviewResource\Pages;
use Minishop\Models\Review;

class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static ?int $navigationSort = 4;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-star';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Catalog';
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = static::getModel()::query()->where('status', ReviewStatus::Pending)->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['product', 'user']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->limit(30),

                TextColumn::make('user.name')
                    ->label('Reviewer')
                    ->searchable(),

                TextColumn::make('rating')
                    ->formatStateUsing(fn (int $state) => str_repeat('★', $state).str_repeat('☆', 5 - $state))
                    ->sortable(),

                TextColumn::make('body')
                    ->limit(60)
                    ->wrap()
                    ->toggleable(),

                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (ReviewStatus $state) => $state->label())
                    ->color(fn (ReviewStatus $state) => match ($state) {
                        ReviewStatus::Pending => 'warning',
                        ReviewStatus::Approved => 'success',
                        ReviewStatus::Rejected => 'danger',
                    }),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(ReviewStatus::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])),
            ])
            ->actions([
                Action::make('approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Review $record) => $record->status !== ReviewStatus::Approved && auth()->user()?->can('update', $record))
                    ->action(fn (Review $record) => $record->update(['status' => ReviewStatus::Approved])),

                Action::make('reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Review $record) => $record->status !== ReviewStatus::Rejected && auth()->user()?->can('update', $record))
                    ->action(fn (Review $record) => $record->update(['status' => ReviewStatus::Rejected])),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReviews::route('/'),
        ];
    }
}
