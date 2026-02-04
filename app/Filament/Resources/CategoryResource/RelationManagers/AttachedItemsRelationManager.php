<?php

namespace App\Filament\Resources\CategoryResource\RelationManagers;

use App\Domain\Categories\Models\Categorizable;
use App\Domain\ContentScopes\ContentScope;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class AttachedItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'categorizablePivots';

    protected static ?string $title = 'Attached Items';

    public function form(Form $form): Form
    {
        $category = $this->getOwnerRecord();
        $scope = $category->scope;

        if (!$scope || !$scope->model_class || !class_exists($scope->model_class)) {
            return $form->schema([
                Forms\Components\Placeholder::make('info')
                    ->label('')
                    ->content('No model configured for this scope. Please configure the model class in the Content Scope settings.')
                    ->columnSpanFull(),
            ]);
        }

        return $form->schema([
            Select::make('categorizable_id')
                ->label('Item')
                ->options(function () use ($scope) {
                    try {
                        $model = app($scope->model_class);
                        $items = $model->query()->limit(500)->get();
                        
                        // Get already attached IDs to exclude them
                        $attachedIds = Categorizable::where('category_id', $this->getOwnerRecord()->id)
                            ->where('categorizable_type', $scope->model_class)
                            ->pluck('categorizable_id')
                            ->toArray();
                        
                        return $items->reject(function ($item) use ($attachedIds) {
                            return in_array($item->id, $attachedIds);
                        })->mapWithKeys(function ($item) {
                            $label = $item->title 
                                ?? $item->name 
                                ?? $item->label 
                                ?? (method_exists($item, 'getName') ? $item->getName() : null)
                                ?? "Item #{$item->id}";
                            
                            return [$item->id => $label];
                        });
                    } catch (\Exception $e) {
                        return [];
                    }
                })
                ->searchable()
                ->required()
                ->helperText("Select an item from {$scope->label} to attach to this category."),
        ]);
    }

    public function table(Table $table): Table
    {
        $category = $this->getOwnerRecord();
        $scope = $category->scope;

        if (!$scope || !$scope->model_class || !class_exists($scope->model_class)) {
            return $table
                ->columns([])
                ->emptyStateHeading('No Model Configured')
                ->emptyStateDescription('Please configure the model class for this scope in Content Scopes settings.')
                ->emptyStateIcon('heroicon-o-exclamation-triangle');
        }

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('categorizable_id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('item_label')
                    ->label('Item')
                    ->getStateUsing(function ($record) use ($scope) {
                        try {
                            $model = app($scope->model_class);
                            $item = $model->find($record->categorizable_id);
                            
                            if (!$item) {
                                return "Item #{$record->categorizable_id} (Not Found)";
                            }
                            
                            return $item->title 
                                ?? $item->name 
                                ?? $item->label 
                                ?? (method_exists($item, 'getName') ? $item->getName() : null)
                                ?? "Item #{$item->id}";
                        } catch (\Exception $e) {
                            return "Item #{$record->categorizable_id}";
                        }
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Attached')
                    ->dateTime('M j, Y')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $category = $this->getOwnerRecord();
                        $scope = $category->scope;
                        
                        return [
                            'category_id' => $category->id,
                            'categorizable_type' => $scope->model_class,
                            'categorizable_id' => $data['categorizable_id'],
                        ];
                    }),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No items attached')
            ->emptyStateDescription("Attach items from {$scope->label} to this category.")
            ->emptyStateIcon('heroicon-o-tag');
    }

    protected function canCreate(): bool
    {
        $category = $this->getOwnerRecord();
        $scope = $category->scope;
        
        return $scope && $scope->model_class && class_exists($scope->model_class);
    }

    /**
     * Modify the query to filter by scope.
     */
    protected function getTableQuery(): Builder
    {
        $category = $this->getOwnerRecord();
        $scope = $category->scope;

        if (!$scope || !$scope->model_class) {
            return Categorizable::query()->whereRaw('1 = 0');
        }

        return Categorizable::query()
            ->where('category_id', $category->id)
            ->where('categorizable_type', $scope->model_class)
            ->orderBy('created_at', 'desc');
    }
}
