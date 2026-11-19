<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    protected $fillable = ['name', 'email', 'phone', 'organisation'];

    public function emailUrl(): ?string
    {
        return filled($this->email) ? 'mailto:'.str_replace('%40', '@', rawurlencode($this->email)) : null;
    }

    public function phoneUrl(): ?string
    {
        $number = $this->diallingNumber();

        return $number === null ? null : 'tel:'.$number;
    }

    public function whatsAppUrl(): ?string
    {
        $number = $this->diallingNumber();

        return $number !== null && preg_match('/\A\+[1-9][0-9]{6,14}\z/', $number)
            ? 'https://wa.me/'.substr($number, 1)
            : null;
    }

    private function diallingNumber(): ?string
    {
        $phone = trim($this->phone ?? '');
        if (str_starts_with($phone, '+') || str_starts_with($phone, '00')) {
            $phone = preg_replace('/\(\s*0\s*\)/', '', $phone);
        }

        $number = preg_replace('/[\s().\p{Pd}]+/u', '', $phone);
        if ($number === null) {
            return null;
        }

        if (str_starts_with($number, '00')) {
            $number = '+'.substr($number, 2);
        }

        return preg_match('/\A(?:\+[1-9][0-9]{2,14}|[0-9]{3,15})\z/', $number) ? $number : null;
    }
}
