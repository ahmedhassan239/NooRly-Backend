<?php

namespace App\Domain\Concerns\Pivots;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Custom Pivot class for hadith_itemables table.
 * 
 * This pivot table exists in the main database, not in the hadith database.
 */
class HadithItemable extends Pivot
{
    /**
     * The connection name for the model.
     * 
     * This ensures the pivot table is accessed from the main database,
     * even though the related model (HadithItem) uses a different connection.
     *
     * @var string
     */
    protected $connection = 'mysql'; // Explicitly use main database connection

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'hadith_itemables';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * Get the database connection for the model.
     * 
     * Override to always return the main database connection,
     * regardless of the related model's connection.
     *
     * @return \Illuminate\Database\Connection
     */
    public function getConnection()
    {
        return static::resolveConnection('mysql');
    }

    /**
     * Get the database connection name.
     * 
     * Override to always return 'mysql' (main database).
     *
     * @return string|null
     */
    public function getConnectionName()
    {
        return 'mysql';
    }
}
