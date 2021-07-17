<?php

namespace App\Sessions\Traits;

trait EnglishAlphabet
{
    /**
     * english alphabet
     *
     * @var string
     */
    protected $alphabet = 'abcdegfhiklmnopqrstwxyz';

    /**
     * get english chars
     *
     * @return array|false
     */
    public function getEnglishChars()
    {
        return str_split($this->alphabet);
    }
}
