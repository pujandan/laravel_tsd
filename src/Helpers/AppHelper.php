<?php

namespace DaniarDev\LaravelCore\Helpers;

use App\Enums\AccountHeaderCategory;
use App\Enums\AccountHeaderType;
use App\Enums\LogType;
use App\Enums\PackageRefundType;
use App\Enums\PackageStatus;
use App\Enums\PackageType;
use App\Enums\PointTypeEnum;
use App\Exceptions\AppException;
use App\Http\Resources\Api\Finance\Report\ReportAccountHeaderCollection;
use App\Models\AdditionalCost;
use App\Models\Airline;
use App\Models\Airport;
use App\Models\BankAccount;
use App\Models\BankCode;
use App\Models\CodeAccount;
use App\Models\Currency;
use App\Models\Employment;
use App\Models\Hotel;
use App\Models\Identity;
use App\Models\Journal;
use App\Models\Log;
use App\Models\Marketing;
use App\Models\MarketingReward;
use App\Models\MarketingTag;
use App\Models\Office;
use App\Models\Package;
use App\Models\Passport;
use App\Models\Payment;
use App\Models\Point;
use App\Models\Reference;
use App\Models\ReportAccountHeader;
use App\Models\Schedule;
use App\Models\ScheduleCategory;
use App\Models\ScheduleFlight;
use App\Models\ScheduleVacation;
use App\Models\Season;
use App\Models\Vacation;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;
use LogicException;
use RuntimeException;

class AppHelper {

    public static function provincesTable() : string
    {
        return config('laravolt.indonesia.table_prefix').'provinces';
    }

    public static function citiesTable() : string
    {
        return config('laravolt.indonesia.table_prefix').'cities';
    }

    public static  function districtsTable() : string
    {
        return config('laravolt.indonesia.table_prefix').'districts';
    }

    public static function villagesTable() : string
    {
        return config('laravolt.indonesia.table_prefix').'villages';
    }

    public static function replaceSpace(string $data) : string
    {
        return preg_replace('/\s/', '', $data);
    }

    public static function toAz09(string $value) : string
    {
        return preg_replace('/[^A-Za-z0-9]/', '', $value);
    }

    public static function formatIdentityForm(array $data) : array
    {
        $data['phone_number'] = self::replaceSpace($data['phone_number']);
        $data['name'] = Str::title($data['name']);
        $data['father_name'] = Str::title($data['father_name']);
        $data['birth_place'] = Str::title($data['birth_place']);
        $data['address'] = Str::upper($data['address']);
        Arr::forget($data, 'is_passport');
        return $data;
    }


    /**
     * @param $nominal
     * @param string $prefix
     * @param int $decimal
     * @param string $separator
     * @param string $thousand
     * @param bool $isParentheses
     * @return string
     */
    public static function formatCurrency(
        $nominal,
        string $prefix = '',
        int $decimal = 0,
        string $separator = ',',
        string $thousand = '.',
        bool $isParentheses = false // with ()
    ) : string {
        // Hilangkan semua karakter kecuali angka, koma, titik, dan minus
        $nominal = preg_replace('/[^\d,.-]/', '', $nominal);

        // Ganti koma terakhir jadi titik supaya bisa dikonversi ke float
        if (str_contains($nominal, ',')) {
            $nominal = preg_replace('/,(\d{1,2})$/', '.$1', $nominal);
        }

        $nominal = (float)$nominal;

        // Format angka
        $formatted = number_format(abs($nominal), $decimal, $separator, $thousand);

        // Gabungkan prefix kalau ada
        $result = trim("{$prefix} {$formatted}");

        if ($nominal < 0) {
            return $isParentheses ? "({$result})" : "-{$result}";
        } else {
            return $result;
        }
    }


    public static function formatDate($date, $format = 'd F Y') : string
    {
        Carbon::setLocale('id');
        return Carbon::parse($date)->translatedFormat($format);
    }

    public static function counted($amount, $lang = 'id') : string
    {
        $amount = (int) $amount;

        if ($amount == 0) {
            return __('label.zero', [], $lang) . ' ' . __('label.rupiah', [], $lang);
        }

        $result = self::countedRecursive($amount, $lang);
        return ucfirst(trim($result)) . ' ' . __('label.rupiah', [], $lang);
    }

    private static function countedRecursive($amount, $lang) : string
    {
        if ($amount == 0) {
            return '';
        }

        $result = '';

        // Trillions
        if ($amount >= 1000000000000) {
            $trillion = floor($amount / 1000000000000);
            $result .= ($lang == 'id' ? ($trillion == 1 ? 'se' : '') : '') . self::countedRecursive($trillion, $lang) . ' ' . __('label.trillion', [], $lang) . ' ';
            $amount %= 1000000000000;
        }

        // Billions
        if ($amount >= 1000000000) {
            $billion = floor($amount / 1000000000);
            $result .= ($lang == 'id' ? ($billion == 1 ? 'se' : '') : '') . self::countedRecursive($billion, $lang) . ' ' . __('label.billion', [], $lang) . ' ';
            $amount %= 1000000000;
        }

        // Millions
        if ($amount >= 1000000) {
            $million = floor($amount / 1000000);
            $result .= ($lang == 'id' ? ($million == 1 ? 'se' : '') : '') . self::countedRecursive($million, $lang) . ' ' . __('label.million', [], $lang) . ' ';
            $amount %= 1000000;
        }

        // Thousands
        if ($amount >= 1000) {
            $thousand = floor($amount / 1000);
            $result .= ($lang == 'id' ? ($thousand == 1 ? 'se' : '') : '') . self::countedRecursive($thousand, $lang) . ' ' . __('label.thousand', [], $lang) . ' ';
            $amount %= 1000;
        }

        // Hundreds
        if ($amount >= 100) {
            $hundred = floor($amount / 100);
            $result .= ($lang == 'id' ? ($hundred == 1 ? 'se' : '') : '') . self::countedRecursive($hundred, $lang) . ' ' . __('label.hundred', [], $lang) . ' ';
            $amount %= 100;
        }

        // 1-99
        if ($amount > 0) {
            if ($lang == 'id') {
                $result .= self::terbilangIndonesian($amount);
            } else {
                $result .= self::terbilangEnglish($amount);
            }
        }

        return $result;
    }

    private static function terbilangIndonesian($amount) : string
    {
        $result = '';

        if ($amount >= 1 && $amount <= 11) {
            $words = [
                1 => 'satu', 2 => 'dua', 3 => 'tiga', 4 => 'empat', 5 => 'lima',
                6 => 'enam', 7 => 'tujuh', 8 => 'delapan', 9 => 'sembilan',
                10 => 'sepuluh', 11 => 'sebelas'
            ];
            $result = $words[$amount] . ' ';
        } elseif ($amount >= 12 && $amount <= 19) {
            $units = $amount - 10;
            $unitWords = [
                2 => 'dua', 3 => 'tiga', 4 => 'empat', 5 => 'lima',
                6 => 'enam', 7 => 'tujuh', 8 => 'delapan', 9 => 'sembilan'
            ];
            $result = $unitWords[$units] . ' belas ';
        } elseif ($amount >= 20 && $amount <= 99) {
            $tens = floor($amount / 10);
            $unit = $amount % 10;

            $tensWords = [
                2 => 'dua', 3 => 'tiga', 4 => 'empat', 5 => 'lima',
                6 => 'enam', 7 => 'tujuh', 8 => 'delapan', 9 => 'sembilan'
            ];

            $result = $tensWords[$tens] . ' puluh ';

            if ($unit > 0) {
                $unitWords = [
                    1 => 'satu', 2 => 'dua', 3 => 'tiga', 4 => 'empat', 5 => 'lima',
                    6 => 'enam', 7 => 'tujuh', 8 => 'delapan', 9 => 'sembilan'
                ];
                $result .= $unitWords[$unit] . ' ';
            }
        }

        return $result;
    }

    private static function terbilangEnglish($amount) : string
    {
        $result = '';

        if ($amount >= 1 && $amount <= 19) {
            $words = [
                1 => 'one', 2 => 'two', 3 => 'three', 4 => 'four', 5 => 'five',
                6 => 'six', 7 => 'seven', 8 => 'eight', 9 => 'nine',
                10 => 'ten', 11 => 'eleven', 12 => 'twelve', 13 => 'thirteen',
                14 => 'fourteen', 15 => 'fifteen', 16 => 'sixteen', 17 => 'seventeen',
                18 => 'eighteen', 19 => 'nineteen'
            ];
            $result = $words[$amount] . ' ';
        } elseif ($amount >= 20 && $amount <= 99) {
            $tens = floor($amount / 10);
            $unit = $amount % 10;

            $tensWords = [
                2 => 'twenty', 3 => 'thirty', 4 => 'forty', 5 => 'fifty',
                6 => 'sixty', 7 => 'seventy', 8 => 'eighty', 9 => 'ninety'
            ];

            $result = $tensWords[$tens] . ' ';

            if ($unit > 0) {
                $unitWords = [
                    1 => 'one', 2 => 'two', 3 => 'three', 4 => 'four', 5 => 'five',
                    6 => 'six', 7 => 'seven', 8 => 'eight', 9 => 'nine'
                ];
                $result .= $unitWords[$unit] . ' ';
            }
        }

        return $result;
    }

    public static function getInitialName(?string $name) : string
    {
        // convert name to array by space
        $words = explode(' ', $name ?? '');
        // if the name consists of only one word, return the first letter of the word
        if (count($words) === 1) {
            return strtoupper(substr($name, 0, 2));
        }
        // initialize string to store initials
        $initials = '';
        // loop through each word
        foreach ($words as $word) {
            // take the first character of each word
            $initials .= strtoupper(substr($word, 0, 1));
            // if you have reached 2 letters, stop the iteration
            if (strlen($initials) >= 2) {
                break;
            }
        }
        // return the resulting initials
        return $initials;
    }

    // api
    public static function toSnakeCase(array $data): array
    {
        return collect($data)->mapWithKeys(function ($value, $key) {
            $snakeKey = Str::snake($key);
            if (is_array($value)) {
                return [$snakeKey => self::toSnakeCase($value)];
            } else {
                return [$snakeKey => $value];
            }
        })->toArray();
    }


    public static function toCamelCase(array $data): array
    {
        return collect($data)->mapWithKeys(function ($value, $key) {
            $camelKey = Str::camel($key);
            if (is_array($value)) {
                return [$camelKey => self::toCamelCase($value)];
            } else {
                return [$camelKey => $value];
            }
        })->toArray();
    }


    public static function enumToArray(string $enum) : array
    {
        try {
            $reflectionClass = new \ReflectionClass($enum);
            $officeTypes = $reflectionClass->getConstants();
            return array_values($officeTypes);
        } catch (\ReflectionException $e) {
            return [];
        }
    }

    public static function enumToImplode(string $enum, string $separator = ',') : string
    {
        return implode($separator, self::enumToArray($enum));
    }

    public static function arrayMerge(...$arrays) : array
    {
        $mergedArray = [];
        foreach ($arrays as $array) {
            $mergedArray = array_merge($mergedArray, $array);
        }
        return $mergedArray;
    }

    public static function isCamel(?string $value) : bool
    {
        if (Str::camel($value) === $value) {
            return true;
        } else {
            return false;
        }
    }

    public static function toBoolean($value) : bool
    {
        if($value == 1 || $value == "1"){
            return true;
        }else{
            return false;
        }
    }

    public static function assetStorage(?string $image) : ?string
    {
        return $image ? asset("storage/{$image}") : null;
    }

    public static function ifNull($data, $replace = null)
    {
        return $data != null ? $data : $replace ?? null;
    }

    public static function getClass(object $object) : string
    {
        return get_class($object);
    }
    public static function getClassName(object $object) : string
    {
        return class_basename($object);
    }

    /**
     * @param string|null $className
     * @return string
     */
    public static function classToName(?string $className): string
    {
        $parts = explode('\\', $className);
        $shortName = end($parts);
        return strtolower($shortName);
    }

    public static function withMicro(Carbon|string|null $date = null): string
    {
        $now = Carbon::now();
        if (is_null($date)) {
            return $now->format('Y-m-d H:i:s.u');
        }
        $baseDate = $date instanceof Carbon ? $date : Carbon::parse($date);
        $newDate = Carbon::createFromFormat(
            'Y-m-d H:i:s.u',
            $baseDate->format('Y-m-d') . ' ' . $now->format('H:i:s.u')
        );

        return $newDate->format('Y-m-d H:i:s.u');
    }

    /**
     * Convert backed enum cases to imploded string.
     *
     * Usage: AppHelper::enumCasesToString(PackageType::class)
     * Result: "direct,closing,free_owner,free_marketing,leader_owner,leader_marketing"
     *
     * @param string $enumClass Fully qualified enum class name
     * @param string $separator Separator for implode (default: ',')
     * @return string
     */
    public static function enumCasesToString(string $enumClass, string $separator = ','): string
    {
        return implode($separator, array_map(fn($case) => $case->value, $enumClass::cases()));
    }

    /**
     * Convert image file to base64 encoded data URI
     *
     * @param string $path Relative path from public directory
     * @return string Base64 encoded image data URI
     */
    public static function base64Image(string $path): string
    {
        $fullPath = public_path($path);

        if (!file_exists($fullPath)) {
            // Return empty 1x1 transparent gif if image not found
            return 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
        }

        $imageData = file_get_contents($fullPath);
        $mimeType = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $fullPath);

        return 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
    }

    /**
     * Generate QR Code from digital signature
     *
     * @param string $signature Digital signature to encode
     * @return string Base64 encoded QR Code image
     */
    public static function generateQrCode(string $signature): string
    {
        if (empty($signature)) {
            return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
        }

        // Use QR Server API (more reliable)
        $qrData = urlencode(substr($signature, 0, 100)); // Limit to 100 chars for QR
        $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={$qrData}";

        try {
            $qrImage = file_get_contents($qrUrl);
            if ($qrImage !== false) {
                return 'data:image/png;base64,' . base64_encode($qrImage);
            }
        } catch (\Exception $e) {
            // Silently fail
        }

        // Return placeholder if failed
        return 'data:image/svg+xml;base64,' . base64_encode('<?xml version="1.0" encoding="UTF-8"?><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><rect x="10" y="10" width="20" height="20" fill="#000"/><rect x="70" y="10" width="20" height="20" fill="#000"/><rect x="10" y="70" width="20" height="20" fill="#000"/><rect x="40" y="40" width="20" height="20" fill="#000"/><rect x="15" y="15" width="10" height="10" fill="#fff"/><rect x="75" y="15" width="10" height="10" fill="#fff"/><rect x="15" y="75" width="10" height="10" fill="#fff"/><rect x="45" y="45" width="10" height="10" fill="#fff"/></svg>');
    }

}




