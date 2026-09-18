<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Auth;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Auth\Http\Responses\Contracts\RegistrationResponse;
use Filament\Auth\Pages\Register as BaseRegister;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;
use Livewire\Attributes\Url;
use Stats4sd\FilamentTeamManagement\Actions\AcceptMembershipInvitation;
use Stats4sd\FilamentTeamManagement\Http\Responses\RegisterResponse;
use Stats4sd\FilamentTeamManagement\Models\Invite;

class Register extends BaseRegister
{
    #[Url]
    public string $token = '';

    public ?Invite $invite = null;

    public ?array $data = [];

    public function mount(): void
    {
        if (Filament::auth()->check()) {
            $this->redirect(Filament::getUrl());

            return;
        }

        $this->invite = Invite::where('token', $this->token)->first();

        if (! $this->invite || ! $this->invite->isPending()) {
            Notification::make()->warning()->title('Invitation unavailable')->body('This invitation is invalid, expired or already accepted. Contact the person who invited you.')->send();
            $this->redirect(Filament::getLoginUrl());

            return;
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

        $user = app(AcceptMembershipInvitation::class)->handle($this->token, $data);

        $user->getConnection()->afterCommit(function () use ($user): void {
            Filament::auth()->login($user);
            session()->regenerate();
        });

        // redirect new user to app panel
        return app(RegisterResponse::class);
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label(__('filament-panels::auth/pages/register.form.email.label'))
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
            ->rule('min:10')
            ->validationMessages(['min' => 'Password must be at least 10 characters long.']);
    }
}
