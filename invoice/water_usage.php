<?php

class invoice_water_usage
{
    public static function quantities(array $estates, array $readings, array $customers, int $year): array
    {
        $byEstate = [];
        foreach ($readings as $row) {
            $y = (int)substr($row['datum'], 0, 4);
            if ($y !== $year && $y !== $year - 1) continue;
            $byEstate[(int)$row['pid']][$y][] = $row['reading'];
        }
        $result = array_fill_keys($customers, 0);
        $found = [];
        foreach ($estates as $estate) {
            $customer = (int)$estate['pid'];
            if (!array_key_exists($customer, $result)) continue;
            $values = $byEstate[(int)$estate['id']] ?? [];
            foreach ([$year - 1, $year] as $y) {
                if (count($values[$y] ?? []) !== 1 || !is_numeric(str_replace(',', '.', (string)$values[$y][0]))) {
                    throw new RuntimeException(($GLOBALS['LANG']['MODULE']['invoice']['ui']['the_property']) . $estate['adress'] . ($GLOBALS['LANG']['MODULE']['invoice']['ui']['needs_exactly_one_valid_meter_reading_for']) . $y . '.');
                }
            }
            $usage = (int)round((float)str_replace(',', '.', $values[$year][0]))
                - (int)round((float)str_replace(',', '.', $values[$year - 1][0]));
            if ($usage < 0) throw new RuntimeException(($GLOBALS['LANG']['MODULE']['invoice']['ui']['the_meter_reading_has_decreased_for']) . $estate['adress'] . ($GLOBALS['LANG']['MODULE']['invoice']['ui']['check_the_readings']));
            $result[$customer] += $usage;
            $found[$customer] = true;
        }
        foreach ($customers as $customer) {
            if (!isset($found[$customer])) throw new RuntimeException(($GLOBALS['LANG']['MODULE']['invoice']['ui']['customer_1d8e4']) . $customer . ($GLOBALS['LANG']['MODULE']['invoice']['ui']['has_no_property_for_water_billing']));
        }
        return $result;
    }
}
