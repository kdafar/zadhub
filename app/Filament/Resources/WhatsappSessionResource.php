<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WhatsappSessionResource\Pages;
use App\Models\FlowVersion;
use App\Models\Provider;
use App\Models\Service;
use App\Models\WhatsappSession;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WhatsappSessionResource extends Resource
{
    protected static ?string $model = WhatsappSession::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationGroup = 'Messaging';

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'whatsapp-sessions';

    protected static ?string $recordTitleAttribute = 'phone';

    public static function canCreate(): bool
    {
        // sessions are created by the bot, not manually
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Session')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('phone')
                        ->label('Phone')
                        ->tel()
                        ->required()
                        ->maxLength(32),

                    Forms\Components\Select::make('provider_id')
                        ->label('Provider')
                        ->native(false)->searchable()->preload()
                        ->options(Provider::query()->orderBy('name')->pluck('name', 'id'))
                        ->required(),

                    Forms\Components\Select::make('service_id')
                        ->label('Service')
                        ->native(false)->searchable()->preload()
                        ->options(Service::query()->orderBy('name_en')->pluck('name_en', 'id')),
                ]),

            Forms\Components\Section::make('Flow')
                ->columns(3)
                ->schema([
                    Forms\Components\Select::make('flow_version_id')
                        ->label('Flow Version')
                        ->native(false)->searchable()->preload()
                        ->options(
                            FlowVersion::query()
                                ->with('template:id,name')
                                ->orderByDesc('is_stable')
                                ->orderByDesc('version')
                                ->get()
                                ->mapWithKeys(fn ($v) => [
                                    $v->id => ($v->template?->name ?? 'Template').' • v'.$v->version.($v->is_stable ? ' (stable)' : ''),
                                ])
                                ->all()
                        ),

                    Forms\Components\TextInput::make('current_screen')
                        ->maxLength(255)
                        ->helperText('Optional runtime view of current screen/state.'),

                    Forms\Components\TextInput::make('flow_token')
                        ->disabled()
                        ->dehydrated(false)
                        ->helperText('Runtime token (read-only).'),
                ]),

            Forms\Components\Section::make('Locale & Status')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('status')
                        ->required()
                        ->maxLength(64)
                        ->helperText('e.g. active, completed, paused'),

                    Forms\Components\TextInput::make('locale')
                        ->maxLength(8)
                        ->default('en')
                        ->required(),

                    Forms\Components\DateTimePicker::make('last_interacted_at')
                        ->seconds(false)
                        ->displayFormat('Y-m-d H:i')
                        ->required(),
                ]),

            Forms\Components\Section::make('Context (JSON)')
                ->schema([
                    Forms\Components\Textarea::make('context')
                        ->rows(12)
                        ->helperText('Optional JSON blob for session data.')
                        // simple JSON validation without requiring extra class import
                        ->rule('json')
                        ->dehydrateStateUsing(function ($state) {
                            if (is_array($state)) {
                                return json_encode($state);
                            }
                            if (blank($state)) {
                                return null;
                            }
                            json_decode($state);

                            return json_last_error() === JSON_ERROR_NONE ? $state : json_encode([]);
                        })
                        ->formatStateUsing(function ($state) {
                            if (is_array($state)) {
                                return json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                            }
                            $decoded = json_decode((string) $state, true);

                            return json_last_error() === JSON_ERROR_NONE
                                ? json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                                : $state;
                        }),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('phone')->label('Phone')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('provider.name')->label('Provider')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('service.name_en')->label('Service')->sortable(),
                Tables\Columns\TextColumn::make('status')->badge()->sortable(),
                Tables\Columns\TextColumn::make('locale')->sortable(),
                Tables\Columns\TextColumn::make('flowVersionLabel')
                    ->label('Flow v')
                    ->state(function (WhatsappSession $r) {
                        $v = $r->flowVersion()->with('template:id,name')->first();
                        if (! $v) {
                            return '—';
                        }

                        return ($v->template?->name ?? 'Template').' • v'.$v->version.($v->is_stable ? ' (stable)' : '');
                    }),
                Tables\Columns\TextColumn::make('last_interacted_at')->since()->label('Last seen'),
                Tables\Columns\TextColumn::make('created_at')->since()->label('Created')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')->since()->label('Updated')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('provider_id')
                    ->label('Provider')
                    ->options(\App\Models\Provider::query()->orderBy('name')->pluck('name', 'id')),

                Tables\Filters\SelectFilter::make('service_id')
                    ->label('Service')
                    ->options(\App\Models\Service::query()->orderBy('name_en')->pluck('name_en', 'id')),

                Tables\Filters\SelectFilter::make('status')
                    ->multiple()
                    ->options([
                        'active' => 'active',
                        'paused' => 'paused',
                        'completed' => 'completed',
                        'ended' => 'ended',
                    ]),

                Tables\Filters\Filter::make('recent')
                    ->label('Active in last 15m')
                    ->query(fn ($q) => $q->where('last_interacted_at', '>=', now()->subMinutes(15))),

                Tables\Filters\Filter::make('last_seen_between')
                    ->form([
                        Forms\Components\DateTimePicker::make('from'),
                        Forms\Components\DateTimePicker::make('to'),
                    ])
                    ->query(function ($q, array $data) {
                        return $q
                            ->when($data['from'] ?? null, fn ($q, $from) => $q->where('last_interacted_at', '>=', $from))
                            ->when($data['to'] ?? null, fn ($q, $to) => $q->where('last_interacted_at', '<=', $to));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('end')
                    ->label('End')
                    ->requiresConfirmation()
                    ->icon('heroicon-o-stop')
                    ->color('danger')
                    ->visible(fn ($record) => $record->status !== 'ended')
                    ->action(function (\App\Models\WhatsappSession $record) {
                        $record->end('ended_by_admin');
                        $record->save();
                    }),

                Tables\Actions\Action::make('reset')
                    ->label('Reset')
                    ->requiresConfirmation()
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->action(function (\App\Models\WhatsappSession $record) {
                        $record->resetSession();
                    }),

                Tables\Actions\Action::make('jump')
                    ->label('Jump to screen')
                    ->icon('heroicon-o-forward')
                    ->form([
                        Forms\Components\TextInput::make('screen_id')
                            ->label('Screen ID')
                            ->placeholder('e.g. SELECT_RESTAURANT')
                            ->required(),
                    ])
                    ->action(function (\App\Models\WhatsappSession $record, array $data) {
                        $record->jumpToScreen($data['screen_id'], ['by' => 'admin']);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWhatsappSessions::route('/'),
            'view' => Pages\ViewWhatsappSession::route('/{record}'),
            'edit' => Pages\EditWhatsappSession::route('/{record}/edit'),
        ];
    }
}
