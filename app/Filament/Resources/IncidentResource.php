<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IncidentResource\Pages;
use App\Filament\Resources\IncidentResource\RelationManagers;
use App\Models\Incident;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Model;

class IncidentResource extends Resource
{
    protected static ?string $model = Incident::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';
    
    protected static ?string $navigationLabel = 'Incidencias';
    
    protected static ?string $modelLabel = 'Incidencia';
    
    protected static ?string $pluralModelLabel = 'Incidencias';
    
    protected static ?int $navigationSort = 7;
    
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        
        // Si es trabajador, solo mostrar sus propias incidencias
        if (auth()->user()->isTrabajador()) {
            $query->where('user_id', auth()->id());
        }
        
        return $query;
    }
    
    public static function canEdit($record): bool
    {
        // Los trabajadores solo pueden editar sus incidencias pendientes o en revisión
        if (auth()->user()->isTrabajador()) {
            return $record->user_id === auth()->id() && in_array($record->status, ['pendiente', 'en_revision']);
        }
        
        return true;
    }
    
    public static function canDelete($record): bool
    {
        // Los trabajadores solo pueden eliminar sus incidencias pendientes
        if (auth()->user()->isTrabajador()) {
            return $record->user_id === auth()->id() && $record->status === 'pendiente';
        }
        
        return true;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información de la Incidencia')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Usuario')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->default(fn () => auth()->user()->isTrabajador() ? auth()->id() : null)
                            ->disabled(fn () => auth()->user()->isTrabajador())
                            ->dehydrated()
                            ->columnSpan(1),
                        Forms\Components\DatePicker::make('date')
                            ->label('Fecha de la Incidencia')
                            ->default(now())
                            ->required()
                            ->columnSpan(1),
                        Forms\Components\Select::make('time_entry_id')
                            ->label('Fichaje relacionado')
                            ->relationship('timeEntry', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->type . ' - ' . $record->datetime->format('d/m/Y H:i'))
                            ->searchable()
                            ->preload()
                            ->columnSpan(2),
                        Forms\Components\Textarea::make('description')
                            ->label('Descripción del Problema')
                            ->required()
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Resolución')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options([
                                'pendiente' => 'Pendiente',
                                'en_revision' => 'En Revisión',
                                'resuelta' => 'Resuelta',
                                'rechazada' => 'Rechazada',
                            ])
                            ->default('pendiente')
                            ->required()
                            ->disabled(fn () => auth()->user()->isTrabajador())
                            ->columnSpan(1),
                        Forms\Components\Textarea::make('resolution')
                            ->label('Resolución')
                            ->maxLength(65535)
                            ->disabled(fn () => auth()->user()->isTrabajador())
                            ->columnSpanFull(),
                    ])
                    ->hidden(fn (string $context, ?Model $record): bool => 
                        $context === 'create' || 
                        (auth()->user()->isTrabajador() && $context === 'edit')
                    )
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuario')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('date')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Descripción')
                    ->limit(50)
                    ->wrap(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Estado')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pendiente' => 'Pendiente',
                        'en_revision' => 'En Revisión',
                        'resuelta' => 'Resuelta',
                        'rechazada' => 'Rechazada',
                        default => $state,
                    })
                    ->colors([
                        'warning' => 'pendiente',
                        'info' => 'en_revision',
                        'success' => 'resuelta',
                        'danger' => 'rechazada',
                    ]),
                Tables\Columns\TextColumn::make('resolvedBy.name')
                    ->label('Resuelto por')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('resolved_at')
                    ->label('Fecha resolución')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Reportado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'pendiente' => 'Pendiente',
                        'en_revision' => 'En Revisión',
                        'resuelta' => 'Resuelta',
                        'rechazada' => 'Rechazada',
                    ]),
                Tables\Filters\SelectFilter::make('user')
                    ->label('Usuario')
                    ->relationship('user', 'name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('resolver')
                    ->label('Resolver')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->modalWidth('2xl')
                    ->form([
                        Forms\Components\Section::make('Ajuste de Fichaje')
                            ->description('Seleccione el día y el ajuste de tiempo necesario')
                            ->schema([
                                Forms\Components\DatePicker::make('adjustment_date')
                                    ->label('Fecha del Fichaje a Ajustar')
                                    ->default(fn (Incident $record) => $record->date)
                                    ->native(false)
                                    ->required()
                                    ->columnSpan(2),
                                Forms\Components\Select::make('adjustment_type')
                                    ->label('Tipo de Ajuste')
                                    ->options([
                                        'add' => 'Sumar tiempo',
                                        'subtract' => 'Restar tiempo',
                                    ])
                                    ->default('add')
                                    ->required()
                                    ->reactive()
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('adjustment_hours')
                                    ->label('Horas')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(23)
                                    ->default(0)
                                    ->suffix('h')
                                    ->columnSpan(1),
                                Forms\Components\TextInput::make('adjustment_minutes')
                                    ->label('Minutos')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(59)
                                    ->default(0)
                                    ->suffix('min')
                                    ->columnSpan(1),
                            ])
                            ->columns(2),
                        Forms\Components\Textarea::make('resolution')
                            ->label('Descripción de la Resolución')
                            ->required()
                            ->columnSpanFull()
                            ->rows(3),
                    ])
                    ->action(function (Incident $record, array $data) {
                        // Calcular el total de minutos a ajustar
                        $totalMinutes = ($data['adjustment_hours'] ?? 0) * 60 + ($data['adjustment_minutes'] ?? 0);
                        
                        if ($totalMinutes > 0) {
                            // Obtener fichajes del día especificado
                            $timeEntries = \App\Models\TimeEntry::where('user_id', $record->user_id)
                                ->whereDate('datetime', $data['adjustment_date'])
                                ->orderBy('datetime')
                                ->get();
                            
                            if ($timeEntries->isNotEmpty()) {
                                // Aplicar el ajuste al último fichaje del día
                                $lastEntry = $timeEntries->last();
                                
                                if ($data['adjustment_type'] === 'add') {
                                    $lastEntry->datetime = $lastEntry->datetime->addMinutes($totalMinutes);
                                } else {
                                    $lastEntry->datetime = $lastEntry->datetime->subMinutes($totalMinutes);
                                }
                                
                                $lastEntry->notes = ($lastEntry->notes ? $lastEntry->notes . "\n" : '') 
                                    . "Ajustado por incidencia #{$record->id}: " 
                                    . ($data['adjustment_type'] === 'add' ? '+' : '-') 
                                    . "{$data['adjustment_hours']}h {$data['adjustment_minutes']}min";
                                $lastEntry->save();
                            }
                        }
                        
                        // Actualizar la incidencia
                        $record->update([
                            'status' => 'resuelta',
                            'resolution' => $data['resolution'] . "\n\n" 
                                . "Ajuste aplicado: " 
                                . ($data['adjustment_type'] === 'add' ? '+' : '-') 
                                . "{$data['adjustment_hours']}h {$data['adjustment_minutes']}min "
                                . "en la fecha " . \Carbon\Carbon::parse($data['adjustment_date'])->format('d/m/Y'),
                            'resolved_by' => auth()->id(),
                            'resolved_at' => now(),
                        ]);
                    })
                    ->successNotificationTitle('Incidencia resuelta y fichaje ajustado correctamente')
                    ->visible(fn (Incident $record): bool => 
                        auth()->user()->isAdmin() && in_array($record->status, ['pendiente', 'en_revision'])
                    ),
                Tables\Actions\Action::make('rechazar')
                    ->label('Rechazar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        Forms\Components\Textarea::make('resolution')
                            ->label('Motivo del rechazo')
                            ->required(),
                    ])
                    ->action(function (Incident $record, array $data) {
                        $record->update([
                            'status' => 'rechazada',
                            'resolution' => $data['resolution'],
                            'resolved_by' => auth()->id(),
                            'resolved_at' => now(),
                        ]);
                    })
                    ->visible(fn (Incident $record): bool => 
                        auth()->user()->isAdmin() && in_array($record->status, ['pendiente', 'en_revision'])
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListIncidents::route('/'),
            'create' => Pages\CreateIncident::route('/create'),
            'edit' => Pages\EditIncident::route('/{record}/edit'),
        ];
    }
}
