<?php

class watermeter_import
{
    public static function rows($stream): array
    {
        $contents = stream_get_contents($stream);
        if ($contents === false) throw new RuntimeException('Kunde inte läsa CSV-filen.');
        if (!preg_match('//u', $contents)) {
            $contents = iconv('Windows-1252', 'UTF-8', $contents);
            if ($contents === false) throw new RuntimeException('Kunde inte läsa filens teckenkodning.');
        }
        $normalized = fopen('php://temp', 'r+');
        fwrite($normalized, $contents);
        rewind($normalized);
        try { return self::parse($normalized); }
        finally { fclose($normalized); }
    }

    private static function parse($stream): array
    {
        $header = fgetcsv($stream, 0, ';', '"', '');
        if (!$header) throw new RuntimeException('CSV-filen är tom.');
        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
        $header = array_map('trim', $header);
        $required = ['Adress', 'Mätarens serienummer', 'Datum', 'Mätarställning'];
        foreach ($required as $name) {
            if (count(array_keys($header, $name, true)) !== 1) {
                throw new RuntimeException('CSV-filen måste ha kolumnerna: ' . implode(';', $required));
            }
        }
        $rows = [];
        $line = 1;
        while (($values = fgetcsv($stream, 0, ';', '"', '')) !== false) {
            $line++;
            if (count($values) === 1 && trim((string)$values[0]) === '') continue;
            if (count($values) !== count($header)) throw new RuntimeException("Rad $line har fel antal kolumner. Ingen import har startats.");
            $row = array_combine($header, array_map('trim', $values));
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $row['Datum']);
            $reading = str_replace(',', '.', $row['Mätarställning']);
            if ($row['Mätarens serienummer'] === '' || !$date || $date->format('Y-m-d') !== $row['Datum']
                || !preg_match('/^\d+(?:\.\d+)?$/D', $reading) || strlen($reading) > 20) {
                throw new RuntimeException("Rad $line har ogiltigt serienummer, datum eller mätarställning. Ingen import har startats.");
            }
            $reading = number_format(round((float)$reading), 0, '.', '');
            $rows[] = ['line' => $line, 'meter' => $row['Mätarens serienummer'], 'address' => $row['Adress'],
                'datum' => $row['Datum'] . ' 00:00:00', 'reading' => $reading];
        }
        return $rows;
    }

    public static function save($db, array $rows): array
    {
        // Serialize imports because the existing schema has no unique key on estate/date.
        $lock = '0x' . bin2hex('watermeter:' . substr(hash('sha256', $db->o_prefix('watermeter')), 0, 40));
        $acquired = $db->o_get_row('', "SELECT GET_LOCK($lock,5) AS acquired");
        if ((int)($acquired['acquired'] ?? 0) !== 1) throw new RuntimeException('En import pågår redan. Försök igen om en stund.');
        try {
            $estates = $db->o_get_all('estate', 'SELECT id,meter FROM [table]');
            $readings = $db->o_get_all('watermeter', 'SELECT pid,datum,reading FROM [table]');
            if (!is_array($estates) || !is_array($readings)) throw new RuntimeException('Kunde inte läsa fastigheter och avläsningar.');
            $meters = $existing = [];
            foreach ($estates as $estate) {
                $meter = trim((string)$estate['meter']);
                if ($meter !== '') $meters[$meter][] = (int)$estate['id'];
            }
            foreach ($readings as $reading) {
                $existing[$reading['pid'] . ':' . substr($reading['datum'], 0, 10)][] = $reading['reading'];
            }
            $result = ['inserted' => 0, 'skipped' => 0, 'errors' => []];
            foreach ($rows as $row) {
                $ids = $meters[$row['meter']] ?? [];
                $label = 'Rad ' . $row['line'] . ' (' . $row['address'] . ', mätare ' . $row['meter'] . '): ';
                if (count($ids) !== 1) {
                    $result['errors'][] = $label . (count($ids) ? 'serienumret finns på flera fastigheter.' : 'serienumret saknas i fastighetsregistret.');
                    continue;
                }
                $key = $ids[0] . ':' . substr($row['datum'], 0, 10);
                if (isset($existing[$key])) {
                    if (count($existing[$key]) === 1 && is_numeric(str_replace(',', '.', $existing[$key][0]))
                        && (float)str_replace(',', '.', $existing[$key][0]) === (float)$row['reading']) {
                        $result['skipped']++;
                    } else {
                        $result['errors'][] = $label . 'det finns redan en annan avläsning för samma datum. Inget skrevs över.';
                    }
                    continue;
                }
                if (!$db->o_insert('watermeter', ['pid' => $ids[0], 'datum' => $row['datum'], 'reading' => $row['reading']])) {
                    $result['errors'][] = $label . 'kunde inte sparas. Importen stoppades; tidigare sparade rader finns kvar.';
                    break;
                }
                $existing[$key] = [$row['reading']];
                $result['inserted']++;
            }
            return $result;
        } finally {
            $db->o_get_row('', "SELECT RELEASE_LOCK($lock) AS released");
        }
    }
}
