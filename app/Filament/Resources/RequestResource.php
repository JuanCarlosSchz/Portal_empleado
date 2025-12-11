<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RequestResource\Pages;
use App\Filament\Resources\RequestResource\RelationManagers;
use App\Models\Request;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Model;

class RequestResource extends Resource
{
    protected static ?string $model = Request::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';
    
    protected static ?string $navigationLabel = 'Solicitudes';
    
    protected static ?string $modelLabel = 'Solicitud';
    
    protected static ?string $pluralModelLabel = 'Solicitudes';
    
    protected static ?int $navigationSort = 6;
    
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        
        // Si es trabajador, solo mostrar sus propias solicitudes
        if (auth()->user()->isTrabajador()) {
            $query->where('user_id', auth()->id());
        }
        
        return $query;
    }
    
    public static function canEdit($record): bool
    {
        // Los trabajadores solo pueden editar sus solicitudes pendientes
        if (auth()->user()->isTrabajador()) {
            return $record->user_id === auth()->id() && $record->status === 'pendiente';
        }
        
        return true;
    }
    
    public static function canDelete($record): bool
    {
        // Los trabajadores solo pueden eliminar sus solicitudes pendientes
        if (auth()->user()->isTrabajador()) {
            return $record->user_id === auth()->id() && $record->status === 'pendiente';
        }
        
        return true;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información de la Solicitud')
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
                        Forms\Components\Select::make('type')
                            ->label('Tipo de Solicitud')
                            ->options([
                                'vacaciones' => 'Vacaciones',
                                'teletrabajo' => 'Teletrabajo',
                                'cita_medica' => 'Cita Médica',
                                'otro' => 'Otro',
                            ])
                            ->required()
                            ->columnSpan(1),
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Fecha de Inicio')
                            ->required()
                            ->columnSpan(1),
                        Forms\Components\DatePicker::make('end_date')
                            ->label('Fecha de Fin')
                            ->columnSpan(1),
                        Forms\Components\Textarea::make('description')
                            ->label('Descripción')
                            ->required()
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Revisión')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options([
                                'pendiente' => 'Pendiente',
                                'aprobada' => 'Aprobada',
                                'rechazada' => 'Rechazada',
                            ])
                            ->default('pendiente')
                            ->required()
                            ->disabled(fn () => auth()->user()->isTrabajador())
                            ->columnSpan(1),
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Notas del Administrador')
                            ->maxLength(65535)
                            ->disabled(fn () => auth()->user()->isTrabajador())
                            ->columnSpanFull(),
                    ])
                    ->hidden(fn (string $context): bool => $context === 'create' || (auth()->user()->isTrabajador() && $context !== 'view'))
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
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Tipo')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'vacaciones' => 'Vacaciones',
                        'teletrabajo' => 'Teletrabajo',
                        'cita_medica' => 'Cita Médica',
                        'otro' => 'Otro',
                        default => $state,
                    })
                    ->colors([
                        'info' => 'vacaciones',
                        'success' => 'teletrabajo',
                        'warning' => 'cita_medica',
                        'gray' => 'otro',
                    ]),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Desde')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('Hasta')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Estado')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pendiente' => 'Pendiente',
                        'aprobada' => 'Aprobada',
                        'rechazada' => 'Rechazada',
                        default => $state,
                    })
                    ->colors([
                        'warning' => 'pendiente',
                        'success' => 'aprobada',
                        'danger' => 'rechazada',
                    ]),
                Tables\Columns\TextColumn::make('reviewedBy.name')
                    ->label('Revisado por')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Solicitado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'pendiente' => 'Pendiente',
                        'aprobada' => 'Aprobada',
                        'rechazada' => 'Rechazada',
                    ]),
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        'vacaciones' => 'Vacaciones',
                        'teletrabajo' => 'Teletrabajo',
                        'cita_medica' => 'Cita Médica',
                        'otro' => 'Otro',
                    ]),
                Tables\Filters\SelectFilter::make('user')
                    ->label('Usuario')
                    ->relationship('user', 'name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('aprobar')
                    ->label('Aprobar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (Request $record) {
                        $record->update([
                            'status' => 'aprobada',
                            'reviewed_by' => auth()->id(),
                            'reviewed_at' => now(),
                        ]);
                    })
                    ->visible(fn (Request $record): bool => 
                        auth()->user()->isAdmin() && $record->status === 'pendiente'
                    ),
                Tables\Actions\Action::make('rechazar')
                    ->label('Rechazar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Motivo del rechazo')
                            ->required(),
                    ])
                    ->action(function (Request $record, array $data) {
                        $record->update([
                            'status' => 'rechazada',
                            'admin_notes' => $data['admin_notes'],
                            'reviewed_by' => auth()->id(),
                            'reviewed_at' => now(),
                        ]);
                    })
                    ->visible(fn (Request $record): bool => 
                        auth()->user()->isAdmin() && $record->status === 'pendiente'
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
            'index' => Pages\ListRequests::route('/'),
            'create' => Pages\CreateRequest::route('/create'),
            'edit' => Pages\EditRequest::route('/{record}/edit'),
        ];
    }
}
