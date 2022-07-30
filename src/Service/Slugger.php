<?php

namespace App\Service;

class Slugger
{
    public function slugify(string $str): string
    {
        return transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $str);
    }
}