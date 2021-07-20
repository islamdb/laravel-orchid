<?php


namespace App\Support\Traits;


use Illuminate\Support\Str;

trait ResourceAllowedSortsAndFilters
{
    /**
     * Name of columns to which http sorting can be applied
     *
     * @var array
     */
    protected $allowedSorts = [];

    /**
     * Name of columns to which http filtering can be applied
     *
     * @var array
     */
    protected $allowedFilters = [];

    protected function setAllowedSortsAndFilters($columnAdditions = ['created_at', 'updated_at'])
    {
        $this->allowedSorts = array_merge(
            $this->fillable,
            $columnAdditions
        );

        $this->allowedFilters = array_merge(
            $this->fillable,
            $columnAdditions
        );
    }

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setAllowedSortsAndFilters();
    }
}
