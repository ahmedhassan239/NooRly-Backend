<?php

namespace App\Filament\Resources\JourneyWeekResource\RelationManagers;

use App\Domain\Journey\JourneyWeekLesson;
use App\Domain\Lessons\Lesson;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

class WeekLessonsRelationManager extends RelationManager
{
    protected static string $relationship = 'journeyWeekLessons';

    protected static ?string $title = 'Week Lessons';

    public function form(Form $form): Form
    {
        $week = $this->getOwnerRecord();
        $existingLessonIds = $week->journeyWeekLessons()->pluck('lesson_id')->toArray();

        return $form
            ->schema([
                Forms\Components\Select::make('lesson_id')
                    ->label('Lesson')
                    ->options(
                        Lesson::query()
                            ->whereNotIn('id', $existingLessonIds)
                            ->get()
                            ->mapWithKeys(fn (Lesson $l) => [
                                $l->id => $l->translations()->where('language_code', 'en')->first()?->title ?? "Lesson #{$l->id}",
                            ])
                    )
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make('day_number')
                    ->label('Day (1–7)')
                    ->options(collect([1, 2, 3, 4, 5, 6, 7])->mapWithKeys(fn ($n) => [$n => "Day {$n}"]))
                    ->required()
                    ->default(1)
                    ->live()
                    ->rules(['integer', 'min:1', 'max:7']),
                Forms\Components\TextInput::make('position')
                    ->label('Position within day (1–50)')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(50)
                    ->default(1)
                    ->helperText('Leave 1 to append at end of day.')
                    ->rules([
                        'integer',
                        'min:1',
                        'max:50',
                        function () use ($week) {
                            return function (string $attr, $value, \Closure $fail) use ($week) {
                                $day = request()->input('day_number', 1);
                                $exists = JourneyWeekLesson::where('journey_week_id', $week->id)
                                    ->where('day_number', $day)
                                    ->where('position', (int) $value)
                                    ->exists();
                                if ($exists) {
                                    $fail('This position is already used for day ' . $day . '.');
                                }
                            };
                        },
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        $week = $this->getOwnerRecord();

        return $table
            ->recordTitleAttribute('id')
            ->description(fn () => 'Add lessons per day (Day 1–7). Multiple lessons per day allowed. Reorder via drag or edit position.')
            ->columns([
                Tables\Columns\TextColumn::make('day_number')
                    ->label('Day')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('position')
                    ->label('Pos')
                    ->sortable(),
                Tables\Columns\TextColumn::make('lesson_title')
                    ->label('Lesson')
                    ->getStateUsing(fn (JourneyWeekLesson $record) => $record->lesson
                        ? ($record->lesson->translations()->where('language_code', 'en')->first()?->title ?? "Lesson #{$record->lesson_id}")
                        : "Lesson #{$record->lesson_id}"),
                Tables\Columns\TextColumn::make('lesson.duration_minutes')
                    ->label('Duration')
                    ->suffix(' min')
                    ->sortable(),
                Tables\Columns\TextColumn::make('lesson.type')
                    ->label('Type')
                    ->badge(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->filters([
                Tables\Filters\SelectFilter::make('day_number')
                    ->label('Day')
                    ->options(collect([1, 2, 3, 4, 5, 6, 7])->mapWithKeys(fn ($n) => [$n => "Day {$n}"])),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add lesson')
                    ->mutateFormDataUsing(function (array $data) use ($week): array {
                        $data['journey_week_id'] = $week->id;
                        $day = (int) ($data['day_number'] ?? 1);
                        $maxPos = (int) $week->journeyWeekLessons()->where('day_number', $day)->max('position');
                        $pos = (int) ($data['position'] ?? ($maxPos + 1));
                        if ($pos < 1 || $week->journeyWeekLessons()->where('day_number', $day)->where('position', $pos)->exists()) {
                            $pos = $maxPos + 1;
                        }
                        $data['position'] = $pos;
                        $data['sort_order'] = $day * 100 + $pos;
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->form([
                        Forms\Components\Select::make('day_number')
                            ->label('Day (1–7)')
                            ->options(collect([1, 2, 3, 4, 5, 6, 7])->mapWithKeys(fn ($n) => [$n => "Day {$n}"]))
                            ->required()
                            ->rules(['integer', 'min:1', 'max:7']),
                        Forms\Components\TextInput::make('position')
                            ->label('Position (1–50)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(50)
                            ->required()
                            ->rules([
                                function (JourneyWeekLesson $record) {
                                    $week = $this->getOwnerRecord();
                                    return Rule::unique('journey_week_lessons', 'position')
                                        ->where('journey_week_id', $week->id)
                                        ->where('day_number', $record->day_number)
                                        ->ignore($record->id);
                                },
                                'integer',
                                'min:1',
                                'max:50',
                            ]),
                    ])
                    ->mutateFormDataUsing(function (JourneyWeekLesson $record, array $data): array {
                        $data['sort_order'] = ((int) ($data['day_number'] ?? $record->day_number)) * 100 + (int) ($data['position'] ?? $record->position);
                        return $data;
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No lessons in this week')
            ->emptyStateDescription('Add lessons and assign day (1–7) and position. Multiple lessons per day are allowed.')
            ->emptyStateIcon('heroicon-o-academic-cap');
    }
}
