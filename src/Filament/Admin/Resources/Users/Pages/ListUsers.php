<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Users\Pages;

use Awcodes\Shout\Components\Shout;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ListRecords;
use Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Users\UserResource;
use Stats4sd\FilamentTeamManagement\Models\User;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('invite users')
                ->schema([
                    Shout::make('info')
                        ->type('info')
                        ->content('Add the email address(es) of the user(s) you would like to invite with a role. An invitation will be sent to each address.')
                        ->columnSpanFull(),
                    Forms\Components\Repeater::make('users')
                        ->label('Email Addresses to Invite')
                        ->schema([
                            Forms\Components\TextInput::make('email')
                                ->email()
                                ->required(),

                            Forms\Components\Select::make('role')
                                ->relationship('roles', 'name')
                                ->required(),
                        ])
                        ->reorderable(false)
                        ->addActionLabel('Add Another Email Address'),
                ])
                ->action(fn (array $data, ListRecords $livewire) => $this->handleInvitation($data)),
        ];
    }

    public function handleInvitation(array $data): void
    {

        $user = auth()->user();

        if (! $user instanceof User) {
            abort(500, 'The user model does not extend the model provided by this package.');
        }

        $user->sendInvites($data['users']);
    }
}
