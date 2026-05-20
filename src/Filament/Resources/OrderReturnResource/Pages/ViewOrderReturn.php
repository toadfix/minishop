<?php

namespace Minishop\Filament\Resources\OrderReturnResource\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Minishop\Enums\ReturnStatus;
use Minishop\Filament\Resources\OrderReturnResource;
use Minishop\Models\OrderReturn;

class ViewOrderReturn extends ViewRecord
{
    protected static string $resource = OrderReturnResource::class;

    protected function getHeaderActions(): array
    {
        /** @var OrderReturn $record */
        $record = $this->getRecord();

        $actions = [];

        if (in_array(ReturnStatus::Approved->value, $record->status->allowedTransitions())) {
            $actions[] = Action::make('approve')
                ->label('Approve')
                ->color('success')
                ->icon('heroicon-o-check')
                ->requiresConfirmation()
                ->action(function () {
                    /** @var OrderReturn $record */
                    $record = $this->getRecord();
                    $record->update(['status' => ReturnStatus::Approved]);
                    Notification::make()->title('Return approved.')->success()->send();
                    $this->refreshFormData(['status']);
                });
        }

        if (in_array(ReturnStatus::Rejected->value, $record->status->allowedTransitions())) {
            $actions[] = Action::make('reject')
                ->label('Reject')
                ->color('danger')
                ->icon('heroicon-o-x-mark')
                ->requiresConfirmation()
                ->action(function () {
                    /** @var OrderReturn $record */
                    $record = $this->getRecord();
                    $record->update(['status' => ReturnStatus::Rejected]);
                    Notification::make()->title('Return rejected.')->warning()->send();
                    $this->refreshFormData(['status']);
                });
        }

        if (in_array(ReturnStatus::Received->value, $record->status->allowedTransitions())) {
            $actions[] = Action::make('mark_received')
                ->label('Mark Received')
                ->color('info')
                ->icon('heroicon-o-inbox-arrow-down')
                ->requiresConfirmation()
                ->action(function () {
                    /** @var OrderReturn $record */
                    $record = $this->getRecord();
                    $record->update(['status' => ReturnStatus::Received]);
                    Notification::make()->title('Return marked as received.')->success()->send();
                    $this->refreshFormData(['status']);
                });
        }

        if (in_array(ReturnStatus::Refunded->value, $record->status->allowedTransitions())) {
            $actions[] = Action::make('refund')
                ->label('Process Refund')
                ->color('primary')
                ->icon('heroicon-o-currency-dollar')
                ->requiresConfirmation()
                ->action(function () {
                    /** @var OrderReturn $record */
                    $record = $this->getRecord();
                    $record->update([
                        'status' => ReturnStatus::Refunded,
                        'refunded_at' => now(),
                    ]);
                    Notification::make()->title('Refund processed.')->success()->send();
                    $this->refreshFormData(['status']);
                });
        }

        return $actions;
    }
}
