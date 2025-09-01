<?php

namespace App\Filament\Resources\FlowResource\Pages;

use App\Filament\Resources\FlowResource;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
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

    public int $activeScreenIndex = 0;

    public function form(Form $form): Form
    {
        // Register all available component classes here
        $componentClasses = [
            \App\FlowComponents\TextBody::class,
            \App\FlowComponents\Image::class,
            \App\FlowComponents\Video::class,
            \App\FlowComponents\Audio::class,
            \App\FlowComponents\Document::class,
            \App\FlowComponents\Dropdown::class,
            \App\FlowComponents\TextInput::class,
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

                        Repeater::make('actions')
                            ->label('Actions (on screen entry)')
                            ->schema([
                                Select::make('type')
                                    ->label('Action Type')
                                    ->options([
                                        'api_call' => 'API Call',
                                        'send_message_template' => 'Send Message Template',
                                    ])
                                    ->live()
                                    ->required(),
                                Forms\Components\Group::make()
                                    ->schema(function (Get $get) {
                                        if ($get('type') === 'api_call') {
                                            return [
                                                Forms\Components\TextInput::make('config.url')->label('URL')->required(),
                                                Forms\Components\Select::make('config.method')->options(['GET', 'POST', 'PUT', 'DELETE'])->default('POST'),
                                                Forms\Components\KeyValue::make('config.headers')->label('Headers'),
                                                Forms\Components\KeyValue::make('config.body')->label('Body/Payload'),
                                                Forms\Components\TextInput::make('config.save_to')->label('Save Response To')->default('api_response'),
                                                Forms\Components\TextInput::make('config.on_success')->label('Next Screen on Success'),
                                                Forms\Components\TextInput::make('config.on_failure')->label('Next Screen on Failure'),
                                            ];
                                        } elseif ($get('type') === 'send_message_template') {
                                            return [
                                                Forms\Components\TextInput::make('config.template_name')->label('Template Name')->required(),
                                                Forms\Components\TextInput::make('config.language_code')->label('Language Code')->default('en_US')->required(),
                                                Forms\Components\KeyValue::make('config.variables')
                                                    ->label('Template Variables')
                                                    ->helperText('Map session data to template placeholders. Key = placeholder index, Value = session data key. e.g., 1 => user.name'),
                                            ];
                                        }

                                        return [];
                                    }),
                            ])
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['type'] ?? ''),

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
                    ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                    ->addAction(fn (Action $action, Get $get, $state) => $action
                        ->label('Preview')
                        ->icon('heroicon-o-eye')
                        ->color(fn () => $this->activeScreenIndex == array_search($get('.'), $state) ? 'primary' : 'gray')
                        ->action(function () use ($get, $state) {
                            $this->activeScreenIndex = array_search($get('.'), $state);
                        })
                    ),
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
