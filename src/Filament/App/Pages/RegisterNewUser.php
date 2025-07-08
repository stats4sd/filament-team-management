<?php

namespace Stats4sd\FilamentTeamManagement\Filament\App\Pages;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\Component;
use Filament\Http\Responses\Auth\Contracts\RegistrationResponse;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\Register as BaseRegister;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Url;
use Spatie\Permission\Models\Role;
use Stats4sd\FilamentTeamManagement\Events\RegisteredWithData;
use Stats4sd\FilamentTeamManagement\Http\Responses\RegisterResponse;
use Stats4sd\FilamentTeamManagement\Models\Invite;

class RegisterNewUser extends BaseRegister
{
    #[Url]
    public string $token = '';

    public ?Invite $invite = null;

    public ?array $data = [];

    public function mount(): void
    {
        $this->invite = Invite::where('token', $this->token)->firstOrFail();

        if (Filament::auth()->check()) {
            redirect()->intended(Filament::getUrl());
        }

        $this->callHook('beforeFill');

        $this->form->fill([
            'email' => $this->invite->email,
        ]);

        $this->callHook('afterFill');
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

        $data['original_password'] = $data['password'];
        $data['password'] = Hash::make($data['password']);

        $user = $this->getUserModel()::create(
            collect($data)
                ->except('original_password')
                ->toArray()
        );

        if ($this->invite instanceof Invite) {

            // Question: If we do not delete team_invites record, can it be used for registration again?
            // $this->invite->delete();
            $this->invite->is_confirmed = true;
            $this->invite->save();

            // If the invite was linked to a role, team or program, link the user to those entries:
            if ($this->invite->role) {
                $role = config('filament-team-management.models.role')::find($this->invite->role_id);
                $user->assignRole($role);
            }

            if ($this->invite->team) {
                $this->invite->team->members()->attach($user);
            }

            if ($this->invite->program) {
                $this->invite->program->users()->attach($user);
            }

        }

        app()->bind(
            SendEmailVerificationNotification::class,
        );

        // pass in the registered form data to the event for extensibility
        event(new Registered($user));
        event(new RegisteredWithData($user, $data));

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

    protected function getPasswordFormComponent(): Component
    {
        /** @var Forms\Components\Field $field */
        $field = parent::getPasswordFormComponent();

        return $field
            ->dehydrateStateUsing(fn ($state) => $state) // override default hashing so we have the option of passing the plain password to register on ODK Central
            ->helperText('Your password must be at least 10 characters long')
            ->rule('min:10', 'Password must be at least 10 characters long.');
    }
}
