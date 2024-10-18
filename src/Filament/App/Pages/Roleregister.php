<?php

namespace Stats4sd\FilamentTeamManagement\Filament\App\Pages;

use Filament\Forms;
use App\Models\User;
use Livewire\Attributes\Url;
use App\Models\UserInvitation;
use Filament\Facades\Filament;
use Spatie\Permission\Models\Role;
use Filament\Forms\Components\Select;
use Illuminate\Auth\Events\Registered;
use App\Http\Responses\RegisterResponse;
use Filament\Forms\Components\Component;
use Filament\Notifications\Notification;
use App\Models\TeamManagement\RoleInvite;
use Filament\Pages\Auth\Register as BaseRegister;
use Filament\Http\Responses\Auth\Contracts\RegistrationResponse;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;

class Roleregister extends BaseRegister
{
    #[Url]
    public $token = '';

    public ?RoleInvite $invite = null;

    public ?array $data = [];

    public function mount(): void
    {
        $this->invite = RoleInvite::where('token', $this->token)->firstOrFail();

        $this->form->fill([
            'email' => $this->invite->email,
        ]);
    }

    public function register(): ?RegistrationResponse
    {
        try {
            $this->rateLimit(2);
        } catch (TooManyRequestsException $exception) {
            Notification::make()
                ->title(__('filament-panels::pages/auth/register.notifications.throttled.title', [
                    'seconds' => $exception->secondsUntilAvailable,
                    'minutes' => ceil($exception->secondsUntilAvailable / 60),
                ]))
                ->body(array_key_exists('body', __('filament-panels::pages/auth/register.notifications.throttled') ?: []) ? __('filament-panels::pages/auth/register.notifications.throttled.body', [
                    'seconds' => $exception->secondsUntilAvailable,
                    'minutes' => ceil($exception->secondsUntilAvailable / 60),
                ]) : null)
                ->danger()
                ->send();

            return null;
        }

        $data = $this->form->getState();
        $user = $this->getUserModel()::create($data);

        // do not delete rote_invites record, keep them as reference.
        // System will not allow the invited user to register again with the same email address.
        // $this->invite->delete();

        $this->invite->is_confirmed = 1;
        $this->invite->save();

        // add role to user
        $role = Role::find($this->invite->role_id);
        $user->assignRole($role);

        app()->bind(
            \Illuminate\Auth\Listeners\SendEmailVerificationNotification::class,
        );

        event(new Registered($user));

        Filament::auth()->login($user);

        session()->regenerate();

        // redirect new user to app panel
        return app(RegisterResponse::class);
    }

    protected function getEmailFormComponent(): Component
    {
        return Forms\Components\TextInput::make('email')
            ->label(__('filament-panels::pages/auth/register.form.email.label'))
            ->email()
            ->required()
            ->maxLength(255)
            ->unique($this->getUserModel())
            ->readOnly();
    }
}
