<?php

namespace App\Sessions\Traits;

trait ArabicAlphabet
{
    protected $arabicAlphabet =
        'ش	غ	ظ	ذ	خ	ث	ت	س	ر	ق	ض	ف	ع	ص	ن	م	ل	ك	ي	ط	ح	ز	و	ه	د	ج	ب	أ';

    /**
     * get arabic chars in form of array
     *
     * @return false|string[]
     */
    public function getArabicChars()
    {
        return explode("\t", $this->arabicAlphabet);
    }
}
