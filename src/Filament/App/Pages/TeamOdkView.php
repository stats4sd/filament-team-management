<?php

namespace Stats4sd\FilamentTeamManagement\Filament\App\Pages;

use App\Filament\App\Resources\XlsformResource;
use App\Models\Team;
use App\Services\HelperService;
use Awcodes\Shout\Components\Shout;
use Filament\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\HtmlString;
use Stats4sd\FilamentOdkLink\Models\OdkLink\Xlsform;
use Stats4sd\FilamentOdkLink\Services\OdkLinkService;

class TeamOdkView extends Page implements HasInfolists, HasTable
{
    use InteractsWithForms;
    use InteractsWithInfolists;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.app.pages.team-odk-view';

    protected ?string $heading = 'ODK Form Management';

    protected static ?string $navigationLabel = 'ODK Form Management';

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getRecord(): Team
    {
        return HelperService::getSelectedTeam();
    }

    public function infolist(Infolist $infolist): Infolist
    {
        $team = HelperService::getSelectedTeam()->load('odkProject.appUsers');

        return $infolist
            ->state($team->toArray())
            ->schema([
                ViewEntry::make('odk_qr_code')
                    ->view('filament-odk-link::filament.infolists.components.team-qr-code'),

            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->relationship(fn (): MorphMany => HelperService::getSelectedTeam()->xlsforms())
            ->inverseRelationship('owner')
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('title')
                    ->grow(false),
                TextColumn::make('status')
                    ->color(fn ($state) => match ($state) {
                        'UPDATES AVAILABLE' => 'danger',
                        'LIVE' => 'success',
                        'DRAFT' => 'info',
                        default => 'light',
                    })
                    ->iconColor(fn ($state) => match ($state) {
                        'UPDATES AVAILABLE' => 'danger',
                        'LIVE' => 'success',
                        'DRAFT' => 'info',
                        default => 'light',
                    })
                    ->icon(fn ($state) => match ($state) {
                        'UPDATES AVAILABLE' => 'heroicon-o-exclamation-circle',
                        'LIVE' => 'heroicon-o-check',
                        'DRAFT' => 'heroicon-o-pencil',
                        default => 'heroicon-o-information-circle',
                    })
                    ->label('Status'),

                TextColumn::make('live_submissions_count')
                    ->label('No. of Submissions'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                \Filament\Tables\Actions\Action::make('create-temp')
                    ->color('gray')
                    ->label('Add a new ODK form')
                    ->modalFooterActions([
                        Action::make('ok')
                            ->label('Return')
                            ->close(),
                    ])
                    ->form([
                        Shout::make('note')
                            ->type('danger')
                            ->content('TBC: Note - at present this feature is not available. All team forms are centrally managed by the system administrators in collaboration with the team leaders. In the future, this will allow teams to have multiple HOLPA survey forms active at the same time, for example a standard HOLPA and a "HOLPA National" survey, or 2 surveys to allow conducting Steps 1 and 2 at different times.'),
                    ]),
            ])
            ->actions([

                TableAction::make('show submissions')
                    ->url(fn (Xlsform $record) => XlsformResource::getUrl('view', ['record' => $record]))
                    ->label('Show Submissions'),

                // add Publish button
                TableAction::make('publish')
                    ->label('Publish')
                    ->icon('heroicon-m-arrow-up-tray')
                    ->visible(fn (Xlsform $record) => ! $record->is_active)
                    ->requiresConfirmation()
                    ->action(function (Xlsform $record) {

                        $odkLinkService = app()->make(OdkLinkService::class);

                        // create draft if there is no draft yet
                        if (! $record->has_draft) {
                            $odkLinkService->createDraftForm($record);
                        }

                        // call API to publish form in ODK central
                        $odkLinkService->publishForm($record);
                    }),

                TableAction::make('update_published_version')
                    ->visible(fn (Xlsform $record) => ! $record->has_latest_template)
                    ->label('Deploy Updates')
                    ->requiresConfirmation()
                    ->modalDescription('This will update the form to the latest version of the xlsform template uploaded to the platform. Are you sure you want to proceed? (NOTE: if this form is live, you may need to tell your enumerators to re-download the form to get the latest version)')
                    ->action(function (Xlsform $record) {

                        $record->syncWithTemplate();
                        $record->publishForm(app()->make(OdkLinkService::class));
                        $record->refresh();

                        Notification::make('update_success')
                            ->title('Success!')
                            ->body("The form {$record->title} is now using the latest xlsform uploaded to this platform")
                            ->color('success')
                            ->send();
                    }),

                TableAction::make('update_team_data')
                    ->label('Publish Latest Lookup Data')
                    ->requiresConfirmation()
                    ->modalDescription(new HtmlString('This will publish the latest team data from the platform to be used in the form. Are you sure you want to proceed? <br/><br/><b>NOTE</b>: if this form is live, you may need to tell your enumerators to re-download the form to get the latest version'))
                    ->visible(fn (Xlsform $record) => ! $record->has_latest_media)
                    ->action(function (Xlsform $record) {
                        $record->publishForm(app()->make(OdkLinkService::class));

                        $record->refresh();

                        Notification::make('update_success')
                            ->title('Success!')
                            ->body("The form {$record->title} now has the latest lookup data entered into the platform by your team.")
                            ->color('success')
                            ->send();
                    }),
                //
                //                // add Pull Submissions button
                //                TableAction::make('export')
                //                    ->label('Export')
                //                    ->icon('heroicon-m-document-arrow-down')
                //                    ->action(function (Xlsform $record) {
                //                        $odkLinkService = app()->make(OdkLinkService::class);
                //
                //                        // call API to export data as excel file
                //                        // P.S. use return to trigger file download in browser
                //                        return $odkLinkService->exportAsExcelFile($record);
                //                    }),
                //
                //                ViewAction::make(),
                //                EditAction::make(),
                //                DeleteAction::make(),
            ])
            ->bulkActions([]);
    }
}
