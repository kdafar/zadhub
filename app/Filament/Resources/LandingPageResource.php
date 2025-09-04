<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LandingPageResource\Pages;
use App\Models\LandingPage;
use App\Services\RevalidateService;
use Filament\Forms;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LandingPageResource extends Resource
{
    protected static ?string $model = LandingPage::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $navigationGroup = 'CMS';

    protected static ?string $navigationLabel = 'Landing Pages';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Grid::make(2)->schema([
                TextInput::make('slug')->required()->helperText('e.g. whatsapp-bot')->rule('regex:/^[a-z0-9-]+$/'),
                TextInput::make('locale')->required()->datalist(['en', 'ar'])->maxLength(5),
                TextInput::make('title')->required()->columnSpanFull(),
                TextInput::make('meta_title')->maxLength(60),
                Textarea::make('meta_description')->rows(2)->maxLength(160),
            ]),
            Builder::make('sections')
                ->label('Page Sections')
                ->blocks([
                    Builder\Block::make('hero')->schema([
                        TextInput::make('eyebrow')->maxLength(40),
                        TextInput::make('heading')->required()->maxLength(90),
                        Textarea::make('subheading')->rows(2)->maxLength(160),
                        Group::make()->schema([
                            TextInput::make('label')->label('Primary label')->maxLength(40),
                            TextInput::make('href')->label('Primary href')->default('#lead'),
                        ])->statePath('primary')->columns(2),
                        Group::make()->schema([
                            TextInput::make('label')->label('Secondary label')->maxLength(40),
                            TextInput::make('href')->label('Secondary href'),
                        ])->statePath('secondary')->columns(2),
                        FileUpload::make('image')->image()->directory('landing/hero')->imageEditor(),
                        Toggle::make('dark')->default(true)->label('Dark theme'),
                    ]),
                    Builder\Block::make('features_grid')->schema([
                        Forms\Components\Repeater::make('features')
                            ->schema([
                                TextInput::make('title')->required()->maxLength(40),
                                Textarea::make('body')->rows(2)->maxLength(160),
                                TextInput::make('icon')->placeholder('e.g. ⚡ or a short text'),
                            ])
                            ->minItems(3)->columns(3),
                    ]),
                    // LOGOS
                    Builder\Block::make('logos')->schema([
                        Forms\Components\Repeater::make('items')
                            ->schema([
                                FileUpload::make('logo')->image()->directory('landing/logos')->imageEditor()->required(),
                                TextInput::make('alt')->maxLength(80),
                            ])
                            ->minItems(3)->maxItems(10)->columns(2),
                    ]),

                    // WHY US
                    Builder\Block::make('why_us')->schema([
                        Forms\Components\Repeater::make('items')
                            ->schema([
                                TextInput::make('title')->required()->maxLength(40),
                                Textarea::make('body')->rows(2)->maxLength(160),
                                TextInput::make('icon')->placeholder('e.g. ⚡'),
                            ])
                            ->minItems(3)->maxItems(6)->columns(3),
                    ]),

                    // INDUSTRY SLICES
                    Builder\Block::make('industry_slices')->schema([
                        Forms\Components\Repeater::make('slices')
                            ->schema([
                                TextInput::make('kicker')->maxLength(24),
                                TextInput::make('headline')->maxLength(80)->required(),
                                Textarea::make('copy')->rows(3)->maxLength(240),
                                FileUpload::make('image')->image()->directory('landing/slices')->imageEditor(),
                                Toggle::make('reverse')->label('Reverse layout')->default(false),
                            ])
                            ->minItems(1)->maxItems(6)->columns(2),
                    ])->label('Industry Slices'),

                    // PRICING
                    Builder\Block::make('pricing')->schema([
                        Forms\Components\Repeater::make('plans')
                            ->schema([
                                TextInput::make('name')->required(),
                                TextInput::make('price_text')->placeholder('29 KWD/mo'),
                                Textarea::make('summary')->rows(2)->maxLength(140),
                                Forms\Components\Repeater::make('bullets')
                                    ->schema([TextInput::make('text')->maxLength(80)->required()])
                                    ->minItems(2)->maxItems(8)->columns(1),
                                Forms\Components\Group::make()->schema([
                                    TextInput::make('label')->label('CTA label')->maxLength(40),
                                    TextInput::make('href')->label('CTA href')->default('#lead'),
                                ])->statePath('cta')->columns(2),
                                Toggle::make('featured')->default(false),
                            ])
                            ->minItems(1)->maxItems(4)->columns(2),
                        TextInput::make('note')->maxLength(120),
                    ]),

                    // FAQ
                    Builder\Block::make('faq')->schema([
                        Forms\Components\Repeater::make('items')
                            ->schema([
                                TextInput::make('q')->required()->maxLength(100),
                                Textarea::make('a')->rows(3)->required()->maxLength(300),
                            ])
                            ->minItems(4)->maxItems(10)->columns(2),
                    ]),

                    // CTA (final)
                    Builder\Block::make('cta')->schema([
                        TextInput::make('heading')->maxLength(80)->required(),
                        Textarea::make('subheading')->rows(2)->maxLength(160),
                        Forms\Components\Group::make()->schema([
                            TextInput::make('label')->label('CTA label')->maxLength(40),
                            TextInput::make('href')->label('CTA href')->default('#lead'),
                        ])->statePath('cta')->columns(2),
                    ])->label('Final CTA'),
                    Builder\Block::make('testimonials')->schema([
                        Forms\Components\Repeater::make('items')
                            ->schema([
                                Forms\Components\TextInput::make('quote')->label('Quote')->required()->maxLength(180),
                                Forms\Components\TextInput::make('author')->required()->maxLength(60),
                                Forms\Components\TextInput::make('role')->maxLength(60),
                                Forms\Components\FileUpload::make('avatar')->image()->directory('landing/testimonials')->imageEditor(),
                                Forms\Components\Select::make('rating')->options([
                                    5 => '★★★★★', 4 => '★★★★☆', 3 => '★★★☆☆', 2 => '★★☆☆☆', 1 => '★☆☆☆☆',
                                ])->default(5),
                            ])
                            ->minItems(2)->maxItems(6)->columns(2),
                    ])->label('Testimonials'),
                ])
                ->collapsible()
                ->columnSpanFull(),
            Toggle::make('is_published')->label('Published')->reactive(),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('slug')->searchable(),
                Tables\Columns\BadgeColumn::make('locale'),
                Tables\Columns\IconColumn::make('is_published')->boolean(),
                Tables\Columns\TextColumn::make('updated_at')->since(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('locale')->options(['en' => 'en', 'ar' => 'ar']),
                Tables\Filters\TernaryFilter::make('is_published')->label('Published'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('publish')
                    ->visible(fn (LandingPage $r) => ! $r->is_published)
                    ->label('Publish')->color('success')
                    ->action(function (LandingPage $record) {
                        $record->update([
                            'is_published' => true,
                            'published_at' => now(),
                            'version' => $record->version + 1,
                        ]);
                        app(RevalidateService::class)->trigger([
                            "/{$record->locale}/{$record->slug}",
                        ]);
                    }),
                Tables\Actions\Action::make('unpublish')
                    ->visible(fn (LandingPage $r) => $r->is_published)
                    ->label('Unpublish')->color('warning')
                    ->action(function (LandingPage $record) {
                        $record->update(['is_published' => false]);
                        app(RevalidateService::class)->trigger([
                            "/{$record->locale}/{$record->slug}",
                        ]);
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageLandingPages::route('/'),
            'edit' => Pages\EditLandingPage::route('/{record}/edit'), // ⬅️ Ensure this page exists
        ];
    }
}
