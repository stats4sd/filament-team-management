<?php

namespace Stats4sd\FilamentTeamManagement\Filament\App\Resources\XlsformResource\Pages;

use App\Filament\App\Resources\XlsformResource;
use App\Http\Controllers\SurveyMonitoringController;
use Filament\Actions\Action;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Stats4sd\FilamentOdkLink\Services\OdkLinkService;

class MonitorXlsform extends Page
{
    use InteractsWithRecord;

    protected static string $resource = XlsformResource::class;

    protected static string $view = 'filament.app.resources.xlsform-resource.pages.monitor-xlsform';

    protected static ?string $title = 'Survey Monitoring';

    public function getHeading(): string
    {
        return $this->record->title . ': Survey Monitoring';
    }

    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    // Question: This function is being called twice now...
    // Is it possible to define a public variable to store the summary array?
    public function getSummary(): array
    {
        $surveyMonitoringController = new SurveyMonitoringController;
        $summary = $surveyMonitoringController->getSubmissionSummary($this->record->owner, false);

        return $summary;
    }

    // TODO: Update this to link to the formatted Excel version
    public function exportAsExcelFile()
    {
        $odkLinkService = app()->make(OdkLinkService::class);

        return $odkLinkService->exportAsExcelFile($this->record);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download raw survey data')
                ->label('Download Raw Survey Data')
                ->action(fn () => $this->exportAsExcelFile()),
        ];
    }
}
