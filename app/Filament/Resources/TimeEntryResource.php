<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TimeEntryResource\Pages;
use App\Filament\Resources\TimeEntryResource\RelationManagers;
use App\Models\TimeEntry;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TimeEntryResource extends Resource
{
    protected static ?string $model = TimeEntry::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';
    
    protected static ?string $navigationLabel = 'Registros de Jornada';
    
    protected static ?string $modelLabel = 'Registro de Jornada';
    
    protected static ?string $pluralModelLabel = 'Registros de Jornada';
    
    protected static ?int $navigationSort = 5;
    
    public static function canViewAny(): bool
    {
        return auth()->user()->isAdmin() || auth()->user()->isTrabajador();
    }
    
    public static function canCreate(): bool
    {
        return auth()->user()->isAdmin();
    }
    
    public static function canEdit($record): bool
    {
        return auth()->user()->isAdmin();
    }
    
    public static function canDelete($record): bool
    {
        return auth()->user()->isAdmin();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('Trabajador')
                    ->relationship('user', 'name', fn ($query) => $query->where('role', 'trabajador'))
                    ->searchable()
                    ->preload()
                    ->required()
                    ->columnSpanFull(),
                
                Forms\Components\DatePicker::make('date')
                    ->label('Fecha de la Jornada')
                    ->native(false)
                    ->default(today())
                    ->required()
                    ->columnSpanFull(),
                
                Forms\Components\Section::make('🟢 Entrada')
                    ->description('Hora de inicio de la jornada')
                    ->schema([
                        Forms\Components\TimePicker::make('entrada_time')
                            ->label('Hora de Entrada')
                            ->seconds(false)
                            ->native(false)
                            ->required()
                            ->columnSpan(1),
                    ])
                    ->columns(1)
                    ->columnSpanFull(),
                
                Forms\Components\Section::make('⏸️ Pausas')
                    ->description('Registra las pausas durante la jornada (opcional)')
                    ->schema([
                        Forms\Components\Repeater::make('pausas')
                            ->schema([
                                Forms\Components\TimePicker::make('inicio')
                                    ->label('Inicio de Pausa')
                                    ->seconds(false)
                                    ->native(false)
                                    ->required(),
                                Forms\Components\TimePicker::make('fin')
                                    ->label('Fin de Pausa')
                                    ->seconds(false)
                                    ->native(false)
                                    ->required(),
                            ])
                            ->columns(2)
                            ->defaultItems(0)
                            ->addActionLabel('Añadir Pausa')
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => 
                                isset($state['inicio']) && isset($state['fin']) 
                                    ? "Pausa: {$state['inicio']} - {$state['fin']}"
                                    : 'Nueva pausa'
                            ),
                    ])
                    ->columnSpanFull()
                    ->collapsible(),
                
                Forms\Components\Section::make('🔴 Salida')
                    ->description('Hora de fin de la jornada')
                    ->schema([
                        Forms\Components\TimePicker::make('salida_time')
                            ->label('Hora de Salida')
                            ->seconds(false)
                            ->native(false)
                            ->required()
                            ->columnSpan(1),
                    ])
                    ->columns(1)
                    ->columnSpanFull(),
                
                Forms\Components\Section::make('Información Adicional')
                    ->schema([
                        Forms\Components\TextInput::make('location')
                            ->label('Ubicación')
                            ->placeholder('Ej: Oficina, Remoto, Cliente...')
                            ->maxLength(255)
                            ->columnSpan(1),
                        Forms\Components\Textarea::make('notes')
                            ->label('Notas')
                            ->placeholder('Información adicional sobre esta jornada...')
                            ->maxLength(65535)
                            ->rows(3)
                            ->columnSpan(1),
                    ])
                    ->columns(2)
                    ->columnSpanFull()
                    ->collapsible(),
                
                // Campos ocultos para mantener compatibilidad con el modelo
                Forms\Components\Hidden::make('type')->default('entrada'),
                Forms\Components\Hidden::make('datetime')->default(now()),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                if (auth()->user()->isTrabajador()) {
                    $query->where('user_id', auth()->id());
                }
            })
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuario')
                    ->searchable()
                    ->sortable()
                    ->visible(fn () => auth()->user()->isAdmin()),
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Tipo')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'entrada' => 'Entrada',
                        'salida' => 'Salida',
                        'pausa_inicio' => 'Inicio Pausa',
                        'pausa_fin' => 'Fin Pausa',
                        default => $state,
                    })
                    ->colors([
                        'success' => 'entrada',
                        'danger' => 'salida',
                        'warning' => 'pausa_inicio',
                        'info' => 'pausa_fin',
                    ]),
                Tables\Columns\TextColumn::make('datetime')
                    ->label('Fecha y Hora')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('location')
                    ->label('Ubicación')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registrado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user')
                    ->label('Usuario')
                    ->relationship('user', 'name')
                    ->visible(fn () => auth()->user()->isAdmin()),
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        'entrada' => 'Entrada',
                        'salida' => 'Salida',
                        'pausa_inicio' => 'Inicio Pausa',
                        'pausa_fin' => 'Fin Pausa',
                    ]),
                Tables\Filters\Filter::make('datetime')
                    ->form([
                        Forms\Components\DatePicker::make('desde')
                            ->label('Desde'),
                        Forms\Components\DatePicker::make('hasta')
                            ->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['desde'],
                                fn (Builder $query, $date): Builder => $query->whereDate('datetime', '>=', $date),
                            )
                            ->when(
                                $data['hasta'],
                                fn (Builder $query, $date): Builder => $query->whereDate('datetime', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn () => auth()->user()->isAdmin()),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => auth()->user()->isAdmin()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ])
                    ->visible(fn () => auth()->user()->isAdmin()),
            ])
            ->defaultSort('datetime', 'desc');
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
            'index' => Pages\ManageTimeEntries::route('/'),
            'create' => Pages\CreateTimeEntry::route('/create'),
            'edit' => Pages\EditTimeEntry::route('/{record}/edit'),
        ];
    }
}
