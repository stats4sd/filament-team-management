<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Resources\UserResource;
use Awcodes\Shout\Components\Shout;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ListRecords;
use Spatie\Permission\Models\Role;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('invite users')
                ->form([
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

                            // Only show "Super Admin" in role selection box.
                            // This is to avoid admin invite program admin via role-invite.
                            // Super admin should create a program then invite program admin for that program
                            Forms\Components\Select::make('role')
                                ->options(Role::where('name', 'Super Admin')->pluck('name', 'id'))
                                ->required(),
                        ])
                        ->reorderable(false)
                        ->columns(2)
                        ->addActionLabel('Add Another Email Address'),
                ])
                ->action(fn (array $data, ListRecords $livewire) => $this->handleInvitation($data)),
            Actions\CreateAction::make(),
        ];
    }

    public function handleInvitation(array $data): void
    {
        auth()->user()->sendInvites($data['users']);
    }
}
