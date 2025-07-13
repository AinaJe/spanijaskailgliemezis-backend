<?php

// app/Helpers/FileOperationsHelper.php

use CodeIgniter\Files\File;

/**
 * Izveido drošu un unikālu faila nosaukumu.
 * Nomaina latviešu burtus un speciālos simbolus ar angļu atbilstošajiem.
 *
 * @param string $title  Faila pamata nosaukums (piem., kartītes nosaukums).
 * @param string $extension Failu paplašinājums (piem., 'jpg', 'mp4').
 * @return string Normalizēts faila nosaukums.
 */
function normalize_filename(string $title, string $extension): string
{
    $title = strtolower($title); // Viss uz mazajiem burtiem

    // Nomainām latviešu specifiskos simbolus
    $replacements = [
        'ā' => 'a', 'č' => 'c', 'ē' => 'e', 'ģ' => 'g', 'ī' => 'i',
        'ķ' => 'k', 'ļ' => 'l', 'ņ' => 'n', 'š' => 's', 'ū' => 'u',
        'ž' => 'z',
        ' ' => '_', // Atstarpes uz pasvītrām
        '/' => '_', '\\' => '_', // Slīpsvītras uz pasvītrām
        ':' => '_', '*' => '_', '?' => '_', '"' => '_', '<' => '_',
        '>' => '_', '|' => '_', // Neatļautie failu nosaukumu simboli
    ];
    $filename = strtr($title, $replacements);

    // Noņemam visus simbolus, kas nav alfabētiska, cipari vai pasvītras
    $filename = preg_replace('/[^a-z0-9_.-]/', '', $filename);

    // Noņemam vairākas pasvītras pēc kārtas
    $filename = preg_replace('/__+/', '_', $filename);

    // Noņemam pasvītras no sākuma un beigām
    $filename = trim($filename, '_');

    // Ja nosaukums paliek tukšs pēc normalizācijas (piem., tikai simboli), ģenerējam unikālu ID
    if (empty($filename)) {
        $filename = uniqid('file_', true);
    }

    return $filename . '.' . $extension;
}

/**
 * Augšupielādē failu, izveidojot norādīto mapju struktūru.
 *
 * @param \CodeIgniter\HTTP\Files\UploadedFile $uploadedFile  Augšupielādētais faila objekts.
 * @param string                               $baseDir       Bāzes mape (piem., 'images' vai 'videos').
 * @param int                                  $themeId       Tēmas ID apakšmapei.
 * @param int                                  $cardId        Kartītes ID apakšmapei.
 * @param string                               $recordTitle   Ieraksta virsraksts faila nosaukumam.
 * @return string|false Failu URL vai `false` kļūdas gadījumā.
 */
function upload_file_with_structure(
    \CodeIgniter\HTTP\Files\UploadedFile $uploadedFile,
    string $baseDir,
    int $themeId,
    int $cardId,
    string $recordTitle
): string|false
{
    if (!$uploadedFile->isValid() || $uploadedFile->hasMoved()) {
        return false; // Fails nav derīgs vai jau pārvietots
    }

    // Izveidojam faila paplašinājumu
    $extension = $uploadedFile->getExtension();

    // Normalizējam faila nosaukumu
    $normalizedTitle = normalize_filename($recordTitle, $extension);

    // Izveidojam mērķa mapi: UPLOAD_PATH / baseDir / themeId / cardId /
    $targetPath = UPLOAD_PATH . $baseDir . DIRECTORY_SEPARATOR . $themeId . DIRECTORY_SEPARATOR . $cardId . DIRECTORY_SEPARATOR;

    // Pārbaudām un izveidojam mapi, ja tā neeksistē
    if (!is_dir($targetPath)) {
        mkdir($targetPath, 0777, true); // 0777 tiesības, rekursīvi
    }

    // Pārbaudām, vai fails ar šādu nosaukumu jau eksistē, un pievienojam numuru, ja nepieciešams
    $finalFilename = $normalizedTitle;
    $counter = 1;
    while (file_exists($targetPath . $finalFilename)) {
        $baseFilename = pathinfo($normalizedTitle, PATHINFO_FILENAME);
        $finalFilename = $baseFilename . '_' . str_pad((string)$counter, 2, '0', STR_PAD_LEFT) . '.' . $extension;
        $counter++;
    }

    // Pārvietojam failu
    if ($uploadedFile->move($targetPath, $finalFilename)) {
        // Atgriežam publiski pieejamo URL
        // base_url() funkcija ir pieejama, ja ir ielādēts URL palīgs
        return base_url("uploads/{$baseDir}/{$themeId}/{$cardId}/{$finalFilename}");
    }

    return false; // Augšupielāde neizdevās
}

/**
 * Dzēš failu, balstoties uz tā URL.
 * Mēģina arī dzēst tukšas direktorijas pēc faila dzēšanas.
 *
 * @param string $fileUrl Failu URL, kas ir jādzēš.
 * @return bool True, ja fails dzēsts vai neeksistē, false kļūdas gadījumā.
 */
function delete_file_by_url(string $fileUrl): bool
{
    // Pārveidojam URL uz lokālo ceļu
    $baseUrl = base_url();
    if (strpos($fileUrl, $baseUrl) === 0) {
        $relativePath = substr($fileUrl, strlen($baseUrl)); // Noņem bāzes URL
    } else {
        // Ja URL nav no base_url, mēģinām atrast pēc zināmiem uploads ceļiem
        if (strpos($fileUrl, 'uploads/') === 0) {
            $relativePath = $fileUrl;
        } else {
            return false; // Nav atpazīstams URL
        }
    }

    $localFilePath = WRITEPATH . $relativePath; // Pilns lokālais ceļš

    if (file_exists($localFilePath)) {
        if (unlink($localFilePath)) { // Dzēšam failu
            // Mēģinām dzēst tukšas apakšdirektorijas (rekursīvi)
            $dir = dirname($localFilePath);
            while ($dir !== UPLOAD_PATH && is_dir($dir) && count(scandir($dir)) == 2) { // 2 ir "." un ".."
                if (!rmdir($dir)) {
                    break; // Neizdevās dzēst (varbūt nav tukšs vai nav tiesību)
                }
                $dir = dirname($dir);
            }
            return true;
        }
        return false; // Neizdevās dzēst failu
    }

    return true; // Fails neeksistē (uzskatām par veiksmīgu, jo mērķis sasniegts)
}

/**
 * Dzēš mapi un visu tās saturu rekursīvi.
 *
 * @param string $dirPath Ceļš uz mapi.
 * @return bool True, ja mape un saturs dzēsts, false kļūdas gadījumā.
 */
function delete_directory_recursive(string $dirPath): bool
{
    if (!is_dir($dirPath)) {
        return false;
    }

    $files = array_diff(scandir($dirPath), ['.', '..']);
    foreach ($files as $file) {
        (is_dir("$dirPath/$file")) ? delete_directory_recursive("$dirPath/$file") : unlink("$dirPath/$file");
    }
    return rmdir($dirPath);
}