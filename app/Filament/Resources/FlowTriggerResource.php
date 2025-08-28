<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FlowTriggerResource\Pages;
use App\Models\FlowTrigger;
use App\Models\FlowVersion;
use App\Models\Provider;
use App\Models\ServiceType;
use Filament\Forms;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class FlowTriggerResource extends Resource
{
    protected static ?string $model = FlowTrigger::class;

    protected static ?string $navigationGroup = 'WhatsApp';

    protected static ?string $navigationIcon = 'heroicon-o-bolt';

    protected static ?string $navigationLabel = 'Flow Triggers';

    public static function form(Forms\Form $form): Forms\Form
    {
        // Helpers that guarantee non-null labels
        $serviceOptions = fn () => ServiceType::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->mapWithKeys(fn ($s) => [
                $s->id => ($s->name !== null && $s->name !== '') ? (string) $s->name : ('Service Type #'.$s->id),
            ])->all();

        $providerOptions = fn () => Provider::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->mapWithKeys(fn ($p) => [
                $p->id => ($p->name !== null && $p->name !== '') ? (string) $p->name : ('Provider #'.$p->id),
            ])->all();

        $flowVersionOptions = fn () => FlowVersion::query()
            ->orderByDesc('published_at')
            ->orderByDesc('version')
            ->orderByDesc('id')
            ->get(['id', 'name', 'version', 'status', 'published_at'])
            ->mapWithKeys(function ($fv) {
                $label = $fv->name ?: ('Flow #'.$fv->id);
                // Enrich label so admins can recognize it even without a name
                $meta = [];
                if ($fv->version) {
                    $meta[] = 'v'.$fv->version;
                }
                if ($fv->status) {
                    $meta[] = $fv->status;
                }
                if ($fv->published_at) {
                    $meta[] = $fv->published_at->toDateString();
                }
                if ($meta) {
                    $label .= ' ('.implode(', ', $meta).')';
                }

                return [$fv->id => (string) $label];
            })->all();

        return $form->schema([
            Forms\Components\Section::make('Trigger')
                ->schema([
                    Forms\Components\Grid::make(3)->schema([
                        Forms\Components\TextInput::make('keyword')
                            ->label('Trigger Keyword')
                            ->required()
                            ->maxLength(64)
                            ->helperText('User sends this word to start the flow, e.g. "restaurant".')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, ?string $state) {
                                $set('keyword', Str::of($state ?? '')->trim()->lower());
                            })
                            ->rule(fn ($record) => Rule::unique('flow_triggers', 'keyword')->ignore($record?->id)),
                        Forms\Components\TextInput::make('locale')
                            ->label('Default Locale')
                            ->placeholder('en')
                            ->maxLength(5)
                            ->helperText('Optional. Example: en or ar'),
                        Forms\Components\TextInput::make('priority')
                            ->numeric()
                            ->minValue(1)
                            ->default(10)
                            ->helperText('Lower number = higher priority.'),
                    ]),
                    Toggle::make('is_active')
                        ->label('Active')
                        ->default(true),
                ]),

            Forms\Components\Section::make('Target')
                ->schema([
                    Toggle::make('use_latest_published')
                        ->label('Use Latest Published for Service/Provider')
                        ->helperText('If ON, this trigger ignores the fixed Flow Version and always uses the latest published version for the selected Service/Provider.')
                        ->live(),

                    Forms\Components\Grid::make(3)->schema([
                        Forms\Components\Select::make('service_type_id')
                            ->label('Service Type')
                            ->relationship('serviceType', 'name')
                            ->getOptionLabelFromRecordUsing(
                                fn (ServiceType $r) => (string) ($r->name
                                    ?? $r->name_en
                                    ?? $r->name_ar
                                    ?? $r->slug
                                    ?? "Service Type #{$r->id}")
                            )
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('provider_id', null)),

                        Forms\Components\Select::make('provider_id')
                            ->label('Provider')
                            ->options(fn (Get $get) => Provider::query()
                                ->when($get('service_type_id'), fn ($q, $st) => $q->where('service_type_id', $st))
                                ->selectRaw("id, COALESCE(name, slug, CONCAT('Provider #', id)) AS label")
                                ->orderBy('label')
                                ->pluck('label', 'id')
                                ->toArray()
                            )
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('flow_version_id', null)),

                        Forms\Components\Select::make('flow_version_id')
                            ->label('Flow Version (fixed)')
                            ->helperText('Ignored if "Use Latest Published" is ON.')
                            ->options(function (Get $get) {
                                return FlowVersion::query()
                                    ->when($get('provider_id'), fn ($q, $pid) => $q->where('provider_id', $pid))
                                    ->orderByDesc('published_at')
                                    ->orderByDesc('version')
                                    ->orderByDesc('id')
                                    ->get(['id', 'name', 'version', 'status', 'published_at'])
                                    ->mapWithKeys(function ($fv) {
                                        $label = $fv->name ?: "Flow #{$fv->id}";
                                        $meta = [];
                                        if (! empty($fv->version)) {
                                            $meta[] = 'v'.$fv->version;
                                        }
                                        if (! empty($fv->status)) {
                                            $meta[] = $fv->status;
                                        }
                                        if (! empty($fv->published_at)) {
                                            $meta[] = \Illuminate\Support\Carbon::parse($fv->published_at)->toDateString();
                                        }
                                        if ($meta) {
                                            $label .= ' ('.implode(', ', $meta).')';
                                        }

                                        return [$fv->id => (string) $label];
                                    })
                                    ->all();
                            })
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->disabled(fn (Get $get) => (bool) $get('use_latest_published'))
                            ->required(fn (Get $get) => ! (bool) $get('use_latest_published')),
                    ]),
                ]),
        ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('keyword')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('serviceType.name')
                    ->label('Service Type')
                    ->sortable()
                    ->formatStateUsing(fn ($state, $record) => $state ?: ('Service Type #'.($record->service_type_id ?? '—'))
                    ),
                Tables\Columns\TextColumn::make('provider.name')->label('Provider')->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('flow_version_id')
                    ->label('Flow Version')
                    ->sortable()
                    ->formatStateUsing(fn ($state, $record) => $record->use_latest_published ? '— (latest)' : ($state ?: '—')),
                Tables\Columns\BadgeColumn::make('use_latest_published')
                    ->label('Latest?')
                    ->colors(['success' => true, 'gray' => false])
                    ->formatStateUsing(fn (bool $state) => $state ? 'Yes' : 'No'),
                Tables\Columns\IconColumn::make('is_active')->boolean()->label('Active'),
                Tables\Columns\TextColumn::make('priority')->sortable(),
                Tables\Columns\TextColumn::make('updated_at')->dateTime()->label('Updated')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFlowTriggers::route('/'),
            'create' => Pages\CreateFlowTrigger::route('/create'),
            'edit' => Pages\EditFlowTrigger::route('/{record}/edit'),
        ];
    }
}
