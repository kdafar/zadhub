<?php

namespace App\Filament\Resources\FlowTemplateResource\Pages;

use App\Filament\Resources\FlowTemplateResource;
use Filament\Forms;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class BuildTemplate extends EditRecord
{
    protected static string $resource = FlowTemplateResource::class;

    protected static ?string $title = 'Flow Template Builder';

    public function form(Form $form): Form
    {
        // Register all available component classes here
        $componentClasses = [
            \App\FlowComponents\TextBody::class,
            \App\FlowComponents\Dropdown::class,
            \App\FlowComponents\TextInput::class,
            \App\FlowComponents\Image::class,
            \App\FlowComponents\DatePicker::class,
        ];
        $componentOptions = collect($componentClasses)
            ->mapWithKeys(fn ($class) => [$class::getKey() => $class::getName()])
            ->toArray();

        return $form
            ->schema([
                Repeater::make('screens')
                    ->label('Screens')
                    ->schema([
                        TextInput::make('id')->label('Screen ID (Unique)')->required(),
                        TextInput::make('title')->label('Screen Title')->required(),
                        Repeater::make('children')
                            ->label('UI Components')
                            ->schema([
                                Select::make('type')
                                    ->label('Component Type')
                                    ->options($componentOptions)
                                    ->live()
                                    ->required(),
                                Forms\Components\Group::make()
                                    ->schema(function (Get $get) use ($componentClasses) {
                                        $type = $get('type');
                                        if (! $type) {
                                            return [];
                                        }
                                        $selectedClass = collect($componentClasses)->first(fn ($class) => $class::getKey() === $type);

                                        return $selectedClass ? $selectedClass::getSchema() : [];
                                    }),
                            ])
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $componentOptions[$state['type']] ?? ''),
                        TextInput::make('footer_label')->label('Footer Button Label')->required(),
                        Select::make('next_screen_id')
                            ->label('Next Screen (on Footer click)')
                            ->options(function (Get $get) {
                                $screens = $get('../../screens') ?? [];

                                return collect($screens)
                                    ->pluck('id', 'id')
                                    ->toArray();
                            })
                            ->helperText('Choose which screen to go to when the footer is pressed.'),
                    ])
                    ->reorderableWithDragAndDrop()
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => $state['title'] ?? null),
            ])
            ->model($this->record)
            ->statePath('data');
    }

    public function mount(int|string $record): void
    {
        parent::mount($record);
        $latestVersion = $this->record->versions()->first();
        $this->form->fill($latestVersion?->builder_data ?? []);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $latestVersion = $record->versions()->first();
        $newVersionNumber = ($latestVersion?->version_number ?? 0) + 1;
        $version = $record->versions()->create([
            'version_number' => $newVersionNumber,
            'builder_data' => $data,
            'changelog' => "Version {$newVersionNumber} created.",
        ]);
        $record->update(['live_version_id' => $version->id]);
        Notification::make()->title('Template saved successfully')->success()->send();

        return $record;
    }
}
