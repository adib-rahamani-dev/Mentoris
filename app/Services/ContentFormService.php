<?php
declare(strict_types=1);
namespace App\Services;
use DateTimeImmutable;
use DateTimeZone;
final class ContentFormService
{
    public static function integer(string $input): ?int
    {
        $input=strtr($input,['۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9','٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9']);
        $input=preg_replace('/[\s,٬]+/u','',$input) ?? '';
        return preg_match('/^\d{1,10}$/',$input) && (int)$input<=1000000000 ? (int)$input : null;
    }
    public static function localDate(string $input): ?string
    {
        if($input==='') return '';
        if(!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(?::\d{2}(?:[+-]\d{2}:\d{2}|Z)?)?$/',$input)) return null;
        try { $date=new DateTimeImmutable($input,new DateTimeZone('Asia/Tehran')); $errors=DateTimeImmutable::getLastErrors(); }
        catch(\Throwable) { return null; }
        return $errors!==false && ($errors['warning_count'] || $errors['error_count']) ? null : $date->format(DATE_ATOM);
    }
    public static function collection(mixed $rows,string $first,string $second): ?array
    {
        if(!is_array($rows) || count($rows)>40) return null;
        $result=[];
        foreach($rows as $row) {
            if(!is_array($row) || !is_string($row[$first] ?? '') || !is_string($row[$second] ?? '')) return null;
            $a=trim($row[$first] ?? ''); $b=trim($row[$second] ?? '');
            if($a==='' && $b==='') continue;
            if($a==='' || mb_strlen($a)>300 || mb_strlen($b)>5000) return null;
            $item=[$first=>$a,$second=>$b];
            if($first==='title') { if(!is_string($row['duration'] ?? '') || mb_strlen($row['duration'] ?? '')>120) return null; $item['duration']=$row['duration'] ?? ''; }
            $result[]=$item;
        }
        return $result;
    }
}
