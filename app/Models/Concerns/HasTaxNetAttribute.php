<?php

namespace App\Models\Concerns;

/**
 * Maps TaxNet (legacy PascalCase) to DB column tax_net (snake_case).
 * Use in models whose table has a tax_net column so that both
 * $model->tax_net and $model->TaxNet work, and create(['TaxNet' => x]) is accepted.
 */
trait HasTaxNetAttribute
{
    public function getTaxNetAttribute()
    {
        return $this->attributes['tax_net'] ?? 0;
    }

    public function setTaxNetAttribute($value)
    {
        $this->attributes['tax_net'] = $value;
    }

    public function setAttribute($key, $value)
    {
        if ($key === 'TaxNet') {
            return parent::setAttribute('tax_net', $value);
        }

        return parent::setAttribute($key, $value);
    }
}
