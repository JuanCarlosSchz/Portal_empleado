<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeResource\Pages;
use App\Filament\Resources\EmployeeResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Columns\Column;

class EmployeeResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    
    protected static ?string $navigationLabel = 'Trabajadores';
    
    protected static ?string $modelLabel = 'Trabajador';
    
    protected static ?string $pluralModelLabel = 'Trabajadores';
    
    protected static ?int $navigationSort = 4;
    
    public static function canViewAny(): bool
    {
        return auth()->user()->isAdmin();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('role', 'trabajador');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('dni')
                    ->label('DNI')
                    ->maxLength(20)
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('password')
                    ->label('Contraseña')
                    ->password()
                    ->required(fn (string $context): bool => $context === 'create')
                    ->dehydrated(fn ($state) => filled($state))
                    ->maxLength(255),
                Forms\Components\Hidden::make('role')
                    ->default('trabajador'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('dni')
                    ->label('DNI')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('monthly_hours')
                    ->label('Horas del Mes')
                    ->getStateUsing(function ($record) {
                        $startOfMonth = now()->startOfMonth();
                        $endOfMonth = now()->endOfMonth();
                        
                        $entries = $record->timeEntries()
                            ->whereBetween('datetime', [$startOfMonth, $endOfMonth])
                            ->orderBy('datetime')
                            ->get();
                        
                        $totalMinutes = 0;
                        $lastEntry = null;
                        $pauseStart = null;
                        
                        foreach ($entries as $entry) {
                            if ($entry->type === 'entrada') {
                                $lastEntry = $entry->datetime;
                            } elseif ($entry->type === 'salida' && $lastEntry) {
                                $minutes = $lastEntry->diffInMinutes($entry->datetime);
                                $totalMinutes += $minutes;
                                $lastEntry = null;
                            } elseif ($entry->type === 'pausa_inicio') {
                                $pauseStart = $entry->datetime;
                            } elseif ($entry->type === 'pausa_fin' && $pauseStart) {
                                $pauseMinutes = $pauseStart->diffInMinutes($entry->datetime);
                                $totalMinutes -= $pauseMinutes;
                                $pauseStart = null;
                            }
                        }
                        
                        $hours = floor($totalMinutes / 60);
                        $minutes = $totalMinutes % 60;
                        
                        return sprintf('%02d:%02d', $hours, $minutes);
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query; // No ordenable por ser calculado
                    }),
                Tables\Columns\TextColumn::make('timeEntries_count')
                    ->label('Fichajes del Mes')
                    ->counts([
                        'timeEntries' => fn (Builder $query) => $query
                            ->whereBetween('datetime', [now()->startOfMonth(), now()->endOfMonth()])
                    ])
                    ->sortable(),
                Tables\Columns\IconColumn::make('active')
                    ->label('Estado')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registrado')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('active')
                    ->label('Estado')
                    ->placeholder('Todos')
                    ->trueLabel('Activos')
                    ->falseLabel('Inactivos'),
                Tables\Filters\Filter::make('con_fichajes')
                    ->label('Con fichajes este mes')
                    ->query(fn (Builder $query): Builder => $query->whereHas('timeEntries', function ($q) {
                        $q->whereBetween('datetime', [now()->startOfMonth(), now()->endOfMonth()]);
                    })),
                Tables\Filters\Filter::make('sin_fichajes')
                    ->label('Sin fichajes este mes')
                    ->query(fn (Builder $query): Builder => $query->whereDoesntHave('timeEntries', function ($q) {
                        $q->whereBetween('datetime', [now()->startOfMonth(), now()->endOfMonth()]);
                    })),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('toggle_active')
                    ->label(fn ($record) => $record->active ? 'Desactivar' : 'Activar')
                    ->icon(fn ($record) => $record->active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn ($record) => $record->active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->modalHeading(fn ($record) => $record->active ? 'Desactivar Trabajador' : 'Activar Trabajador')
                    ->modalDescription(fn ($record) => $record->active 
                        ? '¿Está seguro de que desea desactivar este trabajador? No podrá acceder al sistema.'
                        : '¿Está seguro de que desea activar este trabajador?')
                    ->action(function ($record) {
                        $record->active = !$record->active;
                        $record->save();
                    })
                    ->successNotificationTitle(fn ($record) => $record->active ? 'Trabajador activado correctamente' : 'Trabajador desactivado correctamente'),
                Tables\Actions\Action::make('ver_fichajes')
                    ->label('Ver Fichajes')
                    ->icon('heroicon-o-clock')
                    ->url(fn ($record) => route('filament.admin.resources.time-entries.index', [
                        'tableFilters' => [
                            'user' => ['value' => $record->id],
                        ],
                    ])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->label('Exportar a Excel')
                        ->exports([
                            ExcelExport::make()
                                ->withFilename('trabajadores-' . date('Y-m-d'))
                                ->withColumns([
                                    Column::make('name')->heading('Nombre'),
                                    Column::make('email')->heading('Email'),
                                    Column::make('dni')->heading('DNI'),
                                    Column::make('monthly_hours')
                                        ->heading('Horas del Mes')
                                        ->formatStateUsing(function ($record) {
                                            $startOfMonth = now()->startOfMonth();
                                            $endOfMonth = now()->endOfMonth();
                                            
                                            $entries = $record->timeEntries()
                                                ->whereBetween('datetime', [$startOfMonth, $endOfMonth])
                                                ->orderBy('datetime')
                                                ->get();
                                            
                                            $totalMinutes = 0;
                                            $lastEntry = null;
                                            $pauseStart = null;
                                            
                                            foreach ($entries as $entry) {
                                                if ($entry->type === 'entrada') {
                                                    $lastEntry = $entry->datetime;
                                                } elseif ($entry->type === 'salida' && $lastEntry) {
                                                    $minutes = $lastEntry->diffInMinutes($entry->datetime);
                                                    $totalMinutes += $minutes;
                                                    $lastEntry = null;
                                                } elseif ($entry->type === 'pausa_inicio') {
                                                    $pauseStart = $entry->datetime;
                                                } elseif ($entry->type === 'pausa_fin' && $pauseStart) {
                                                    $pauseMinutes = $pauseStart->diffInMinutes($entry->datetime);
                                                    $totalMinutes -= $pauseMinutes;
                                                    $pauseStart = null;
                                                }
                                            }
                                            
                                            $hours = floor($totalMinutes / 60);
                                            $minutes = $totalMinutes % 60;
                                            
                                            return sprintf('%02d:%02d', $hours, $minutes);
                                        }),
                                ]),
                        ]),
                ]),
            ])
            ->defaultSort('name');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployees::route('/'),
        ];
    }
    
    public static function canCreate(): bool
    {
        return false;
    }
}
