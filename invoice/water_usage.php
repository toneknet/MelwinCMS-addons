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
                    throw new RuntimeException('Fastigheten ' . $estate['adress'] . ' behöver exakt en giltig avläsning för ' . $y . '.');
                }
            }
            $usage = (int)round((float)str_replace(',', '.', $values[$year][0]))
                - (int)round((float)str_replace(',', '.', $values[$year - 1][0]));
            if ($usage < 0) throw new RuntimeException('Mätarställningen har minskat för ' . $estate['adress'] . '. Kontrollera avläsningarna.');
            $result[$customer] += $usage;
            $found[$customer] = true;
        }
        foreach ($customers as $customer) {
            if (!isset($found[$customer])) throw new RuntimeException('Kund ' . $customer . ' saknar fastighet för vattenfakturering.');
        }
        return $result;
    }
}
