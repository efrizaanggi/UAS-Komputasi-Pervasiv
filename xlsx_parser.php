<?php
// xlsx_parser.php
// Parser sederhana untuk file .xlsx (Excel 2007+)
// Hanya membaca sheet pertama, mendukung shared strings

function parseXlsx($filePath) {
    $zip = new ZipArchive();
    if ($zip->open($filePath) !== true) {
        return false;
    }

    // Baca shared strings
    $sharedStrings = [];
    if ($zip->locateName('xl/sharedStrings.xml') !== false) {
        $xml = simplexml_load_string($zip->getFromName('xl/sharedStrings.xml'));
        foreach ($xml->si as $si) {
            $text = '';
            if (isset($si->t)) {
                $text = (string)$si->t;
            } elseif (isset($si->r)) {
                foreach ($si->r as $r) {
                    $text .= (string)$r->t;
                }
            }
            $sharedStrings[] = $text;
        }
    }

    // Baca sheet1
    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    if (!$sheetXml) {
        $zip->close();
        return false;
    }
    $xml = simplexml_load_string($sheetXml);
    $rows = $xml->sheetData->row;
    $data = [];

    foreach ($rows as $row) {
        $rowData = [];
        foreach ($row->c as $cell) {
            $value = '';
            if (isset($cell->v)) {
                $v = (string)$cell->v;
                $type = (string)$cell['t'];
                if ($type == 's') { // shared string
                    $index = (int)$v;
                    $value = $sharedStrings[$index] ?? '';
                } else {
                    $value = $v;
                }
            }
            $rowData[] = $value;
        }
        if (!empty($rowData)) {
            $data[] = $rowData;
        }
    }
    $zip->close();

    // Asumsikan baris pertama adalah header
    if (count($data) < 2) return [];
    $headers = array_map('strtolower', $data[0]);
    $expected = ['question','option_a','option_b','option_c','option_d','correct'];
    // Normalisasi header
    $headerMap = [];
    foreach ($headers as $i => $h) {
        $h = str_replace([' ', '_', '-'], '', $h);
        if (in_array($h, $expected)) {
            $headerMap[$h] = $i;
        }
    }
    // Validasi minimal header
    if (count($headerMap) < 6) return [];

    $result = [];
    for ($i = 1; $i < count($data); $i++) {
        $row = $data[$i];
        $question = $row[$headerMap['question']] ?? '';
        $option_a = $row[$headerMap['option_a']] ?? '';
        $option_b = $row[$headerMap['option_b']] ?? '';
        $option_c = $row[$headerMap['option_c']] ?? '';
        $option_d = $row[$headerMap['option_d']] ?? '';
        $correct  = strtoupper(trim($row[$headerMap['correct']] ?? ''));
        if (!in_array($correct, ['A','B','C','D'])) continue;
        if (empty($question) || empty($option_a)) continue;
        $result[] = [
            'question'    => $question,
            'option_a'    => $option_a,
            'option_b'    => $option_b,
            'option_c'    => $option_c,
            'option_d'    => $option_d,
            'correct'     => $correct
        ];
    }
    return $result;
}
function parseXlsxFlexible($filePath) {
    $zip = new ZipArchive();
    if ($zip->open($filePath) !== true) {
        return false;
    }

    // Baca shared strings
    $sharedStrings = [];
    if ($zip->locateName('xl/sharedStrings.xml') !== false) {
        $xml = simplexml_load_string($zip->getFromName('xl/sharedStrings.xml'));
        foreach ($xml->si as $si) {
            $text = '';
            if (isset($si->t)) {
                $text = (string)$si->t;
            } elseif (isset($si->r)) {
                foreach ($si->r as $r) {
                    $text .= (string)$r->t;
                }
            }
            $sharedStrings[] = $text;
        }
    }

    // Baca sheet1
    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    if (!$sheetXml) {
        $zip->close();
        return false;
    }
    $xml = simplexml_load_string($sheetXml);
    $rows = $xml->sheetData->row;
    $data = [];

    foreach ($rows as $row) {
        $rowData = [];
        foreach ($row->c as $cell) {
            $value = '';
            if (isset($cell->v)) {
                $v = (string)$cell->v;
                $type = (string)$cell['t'];
                if ($type == 's') {
                    $index = (int)$v;
                    $value = $sharedStrings[$index] ?? '';
                } else {
                    $value = $v;
                }
            }
            $rowData[] = $value;
        }
        if (!empty($rowData)) {
            $data[] = $rowData;
        }
    }
    $zip->close();

    if (count($data) < 2) return [];

    // Header (baris pertama)
    $headers = array_map('strtolower', $data[0]);
    // Cari indeks 'question' dan 'correct'
    $questionIndex = array_search('question', $headers);
    $correctIndex = array_search('correct', $headers);

    if ($questionIndex === false || $correctIndex === false) {
        return []; // header wajib ada
    }

    $result = [];
    for ($i = 1; $i < count($data); $i++) {
        $row = $data[$i];
        $question = trim($row[$questionIndex] ?? '');
        $correct  = trim($row[$correctIndex] ?? '');
        if (empty($question) || empty($correct)) continue;

        // Ambil semua kolom antara question dan correct sebagai opsi
        $options = [];
        $minIndex = min($questionIndex, $correctIndex);
        $maxIndex = max($questionIndex, $correctIndex);
        for ($j = 0; $j < count($row); $j++) {
            // Kolom di antara question dan correct (eksklusif) adalah opsi
            if ($j > $minIndex && $j < $maxIndex) {
                $opt = trim($row[$j] ?? '');
                if ($opt !== '') {
                    $options[] = $opt;
                }
            }
        }
        // Validasi: correct harus ada di dalam options
        if (!in_array($correct, $options, true)) continue;

        $result[] = [
            'question' => $question,
            'options'  => $options,
            'correct'  => $correct
        ];
    }
    return $result;
}
?>