<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DocumentResource\Pages;
use App\Filament\Resources\DocumentResource\RelationManagers;
use App\Models\Document;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    
    protected static ?string $navigationLabel = 'Documentos';
    
    protected static ?string $modelLabel = 'Documento';
    
    protected static ?string $pluralModelLabel = 'Documentos';
    
    protected static ?int $navigationSort = 3;
    
    public static function canCreate(): bool
    {
        // Solo administradores pueden crear documentos
        return auth()->user()->isAdmin();
    }
    
    public static function canEdit($record): bool
    {
        // Solo administradores pueden editar documentos
        return auth()->user()->isAdmin();
    }
    
    public static function canDelete($record): bool
    {
        // Solo administradores pueden eliminar documentos
        return auth()->user()->isAdmin();
    }
    
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();
        
        // Si es trabajador, solo mostrar documentos visibles para todos o asignados a él
        if (auth()->user()->isTrabajador()) {
            $query->where(function($q) {
                $q->where('visible_to_all', true)
                  ->orWhereHas('users', function($subQ) {
                      $subQ->where('users.id', auth()->id());
                  });
            });
        }
        
        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->label('Título')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('description')
                    ->label('Descripción')
                    ->maxLength(65535)
                    ->columnSpanFull(),
                Forms\Components\FileUpload::make('file_path')
                    ->label('Archivo')
                    ->directory('documents')
                    ->disk('public')
                    ->acceptedFileTypes(['application/pdf', 'image/*', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/plain'])
                    ->maxSize(10240)
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('visible_to_all')
                    ->label('Visible para todos los trabajadores')
                    ->default(false)
                    ->helperText('Si está desactivado, deberás asignar el documento a usuarios específicos')
                    ->columnSpanFull(),
                Forms\Components\Select::make('users')
                    ->label('Asignar a usuarios específicos')
                    ->relationship('users', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->visible(fn (Forms\Get $get) => !$get('visible_to_all'))
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('file_name')
                    ->label('Archivo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('file_size')
                    ->label('Tamaño')
                    ->formatStateUsing(fn ($state) => number_format($state / 1024, 2) . ' KB')
                    ->sortable(),
                Tables\Columns\TextColumn::make('uploadedBy.name')
                    ->label('Subido por')
                    ->sortable(),
                Tables\Columns\IconColumn::make('visible_to_all')
                    ->label('Visible a todos')
                    ->boolean(),
                Tables\Columns\TextColumn::make('users_count')
                    ->label('Usuarios asignados')
                    ->counts('users')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Subido el')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('visible_to_all')
                    ->label('Visibilidad')
                    ->placeholder('Todos')
                    ->trueLabel('Visible para todos')
                    ->falseLabel('Asignados específicamente'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListDocuments::route('/'),
            'create' => Pages\CreateDocument::route('/create'),
            'edit' => Pages\EditDocument::route('/{record}/edit'),
        ];
    }
}
