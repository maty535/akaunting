<?php

namespace App\Models\Income;

use App\Models\Model;
use App\Traits\Currencies;
use Bkwld\Cloner\Cloneable;

class InvoiceItem extends Model
{

    use Currencies, Cloneable;

    protected $table = 'invoice_items';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['company_id', 'invoice_id', 'item_id', 'name', 'sku', 'quantity', 'unit', 'price', 'total', 'tax', 'tax_id'];
    
    public $cloneable_relations = ['taxes'];

    public function invoice()
    {
        return $this->belongsTo('App\Models\Income\Invoice');
    }

    public function item()
    {
        return $this->belongsTo('App\Models\Common\Item');
    }

    public function taxes()
    {
        return $this->hasMany('App\Models\Income\InvoiceItemTax', 'invoice_item_id', 'id');
    }
    
    public function first_tax() {
        return $this->taxes()->first();
    }
    
    public function getTotalTaxRateAttribute() {
        $totalTax = 0.0;
        foreach( $this->taxes as $t){
            $totalTax += $t->tax->rate;
        }
        
        return $totalTax;        
    }
    
    public function getTaxUsedAsStringAttribute() {
        $usedTaxes = collect();
        foreach( $this->taxes as $t){
            $usedTaxes->push($t->tax->rate);
        }
        
        return $usedTaxes->implode('+');        
    }
  
    
    
    public function getTotalTaxAmountAttribute() {
        $totalAmount = 0.0;
        foreach( $this->taxes as $t){
            $totalAmount += $t->amount;
        }
        
        return $totalAmount;        
    }
  
  
/*
    public function tax()
    {
        return $this->belongsTo('App\Models\Setting\Tax');
    }

    public function taxType()
    {
        return $this->belongsTo('App\Models\Setting\Tax', 'tax_id');
    }
*/    
    /**
     * Convert tax to double.
     *
     * @param  string  $value
     * @return void
     */
    public function getTaxIdAttribute($value)
    {
        $tax_ids = [];
        if (!empty($value)) {
            $tax_ids[] = $value;
            return $tax_ids[0];
        }
        return $this->taxes->pluck('tax_id');
    }
    

    /**
     * Convert price to double.
     *
     * @param  string  $value
     * @return void
     */
    public function setPriceAttribute($value)
    {
        $this->attributes['price'] = (double) $value;
    }

    /**
     * Convert total to double.
     *
     * @param  string  $value
     * @return void
     */
    public function setTotalAttribute($value)
    {
        $this->attributes['total'] = (double) $value;
    }

    /**
     * Convert tax to double.
     *
     * @param  string  $value
     * @return void
     */
    public function setTaxAttribute($value)
    {
        $this->attributes['tax'] = (double) $value;
    }

    public function onCloning($src, $child = null)
    {
        // Získame aktuálny rok a mesiac vo formáte YYYY/MM
        $replacement = date('Y/m');

        /**
         * Regex vysvetlenie:
         * \d{4}    - hľadá presne 4 číslice (rok)
         * [\/]     - hľadá lomenú zátvorku
         * \d{2}    - hľadá presne 2 číslice (mesiac)
         */
        $pattern = '/\d{4}\/\d{2}/';

        // Vykonáme nahradenie v názve položky
        if (!empty($this->name)) {
            $this->name = preg_replace($pattern, $replacement, $this->name);
        }

        // Voliteľne: ak máš tento formát aj v popise, odkomentuj riadok nižšie
        // if (!empty($this->description)) {
        //    $this->description = preg_replace($pattern, $replacement, $this->description);
        // }
    }
}
