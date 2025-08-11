<?php

namespace App\Filament\Resources\FlowResource\Pages;

use App\Filament\Resources\FlowResource;
use Filament\Forms;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BuildFlow extends EditRecord
{
    protected static string $resource = FlowResource::class;

    protected static ?string $title = 'Flow Builder';

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
                    ->live()
                    ->schema([
                        TextInput::make('id')
                            ->label('Screen ID (Unique)')
                            ->required()
                            ->default(fn () => 'SCR_'.strtoupper(Str::random(6))),
                        TextInput::make('title')
                            ->label('Screen Title')
                            ->required(),

                        Repeater::make('children')
                            ->label('UI Components')
                            ->schema([
                                Select::make('type')
                                    ->label('Component Type')
                                    ->options($componentOptions)
                                    ->live()
                                    ->required(),

                                // This group dynamically loads the schema for the selected component
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

                        TextInput::make('footer_label')
                            ->label('Footer Button Label')
                            ->required(),
                    ])
                    ->reorderableWithDragAndDrop()
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => $state['title'] ?? null),
            ])
            ->model($this->record)
            ->statePath('data');
    }

    // --- No changes needed below this line ---
    public function mount(int|string $record): void
    {
        parent::mount($record);
        $latestVersion = $this->record->versions()->first();
        $this->form->fill($latestVersion?->steps_data ?? []);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $latestVersion = $record->versions()->first();
        $newVersionNumber = ($latestVersion?->version_number ?? 0) + 1;
        $version = $record->versions()->create([
            'version_number' => $newVersionNumber,
            'steps_data' => $data,
            'changelog' => "Version {$newVersionNumber} created.",
        ]);
        $record->update(['live_version_id' => $version->id]);
        Notification::make()->title('Flow saved successfully')->success()->send();

        return $record;
    }
}
