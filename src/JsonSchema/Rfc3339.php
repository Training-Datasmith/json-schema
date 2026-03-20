<?php

declare (strict_types=1);
namespace Json_Schema;

class Rfc3339
{
    private const REGEX = '/^(\d{4}-\d{2}-\d{2}[T ](0[0-9]|1[0-9]|2[0-3]):([0-5][0-9]):((?:[0-5][0-9]|60)))(\.\d+)?(Z|([+-](0[0-9]|1[0-9]|2[0-3]))(:)?([0-5][0-9]))$/';
    /**
     * Try creating a DateTime instance
     *
     * @param string $input
     */
    public static function create_from_string($input): ?\DateTime
    {
        if (!preg_match(self::REGEX, strtoupper($input), $matches)) {
            return null;
        }
        $input = strtoupper($input);
        // Cleanup for lowercase t and z
        $input_has_t_separator = strpos($input, 'T');
        $date_and_time = $matches[1];
        $microseconds = $matches[5] ?: '.000000';
        $time_zone = 'Z' !== $matches[6] ? $matches[6] : '+00:00';
        $date_format = $input_has_t_separator === false ? 'Y-m-d H:i:s.uP' : 'Y-m-d\TH:i:s.uP';
        $date_time = \DateTimeImmutable::create_from_format($date_format, $date_and_time . $microseconds . $time_zone, new \DateTimeZone('UTC'));
        if ($date_time === false) {
            return null;
        }
        $utc_date_time = $date_time->set_timezone(new \DateTimeZone('+00:00'));
        $one_second = new \DateInterval('PT1S');
        // handle leap seconds
        if ($matches[4] === '60' && $utc_date_time->sub($one_second)->format('H:i:s') === '23:59:59') {
            $date_time = $date_time->sub($one_second);
            $matches[1] = str_replace(':60', ':59', $matches[1]);
        }
        // Ensure we still have the same year, month, day, hour, minutes and seconds to ensure no rollover took place.
        if ($date_time->format($input_has_t_separator ? 'Y-m-d\TH:i:s' : 'Y-m-d H:i:s') !== $matches[1]) {
            return null;
        }
        $mutable = \DateTime::create_from_format('U.u', $date_time->format('U.u'));
        if ($mutable === false) {
            throw new \RuntimeException('Unable to create DateTime from DateTimeImmutable');
        }
        $mutable->set_timezone($date_time->get_timezone());
        return $mutable;
    }
}