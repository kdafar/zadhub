<?php

namespace App\Filament\Resources\FlowResource\Pages;

use App\Filament\Resources\FlowResource;
use App\Models\FlowVersion;
use Filament\Forms;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class BuildFlow extends EditRecord
{
    protected static string $resource = FlowResource::class;

    protected static ?string $title = 'Flow Builder';

    protected static string $view = 'filament.resources.flow-resource.pages.build-flow';

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
        $liveVersion = $this->record->liveVersion()->first();
        $this->form->fill($liveVersion?->definition ?? []);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $liveVersion = $record->liveVersion()->first();

        if ($liveVersion) {
            $liveVersion->update(['definition' => $data]);
        } else {
            // Create a new version if none exists
            $liveVersion = $record->versions()->create([
                'definition' => $data,
                'version' => 1,
                'status' => 'published',
                'published_at' => now(),
                'name' => 'v1',
                'service_type_id' => $record->provider->service_type_id,
                'provider_id' => $record->provider_id,
            ]);
        }

        Notification::make()->title('Flow saved successfully')->success()->send();

        return $record;
    }
}
