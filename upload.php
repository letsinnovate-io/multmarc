<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file']) && $_FILES['file']['error'] == UPLOAD_ERR_OK) {
    $fileTmpPath = $_FILES['file']['tmp_name'];
    $fileName = $_FILES['file']['name'];
    $fileSize = $_FILES['file']['size'];
    $fileType = $_FILES['file']['type'];
    $fileNameCmps = explode(".", $fileName);
    $fileExtension = strtolower(end($fileNameCmps));

    $allowedfileExtensions = array('csv');

    if (in_array($fileExtension, $allowedfileExtensions)) {
        $csvData = array_map('str_getcsv', file($fileTmpPath));
        $csvHeader = array_shift($csvData);
        $csvArray = [];

        foreach ($csvData as $row) {
            $rowAssoc = array_combine($csvHeader, $row);

            if (array_key_exists($rowAssoc['BARCODE'], $csvArray)) {

                if ($rowAssoc['MARC TAG'] == '245' && $rowAssoc['MARC SUBFIELD'] == 'p') {
                    $csvArray[$rowAssoc['BARCODE']]['245p'] = $rowAssoc['MARC SUBFIELD DATA'];
                } elseif ($rowAssoc['MARC TAG'] == '710' && $rowAssoc['MARC SUBFIELD'] == 'a') {
                    $csvArray[$rowAssoc['BARCODE']]['710a'] = $rowAssoc['MARC SUBFIELD DATA'];
                }

            } else {
                $csvArray[$rowAssoc['BARCODE']] = $rowAssoc;
                $csvArray[$rowAssoc['BARCODE']]['245a'] = $rowAssoc['MARC SUBFIELD DATA'];
                $csvArray[$rowAssoc['BARCODE']]['245p'] = "";
                $csvArray[$rowAssoc['BARCODE']]['710a'] = "";

            }
        }

        foreach ($csvArray as $barcode => $row) {
            unset($csvArray[$barcode]['MARC TAG'], $csvArray[$barcode]['MARC SUBFIELD'], $csvArray[$barcode]['MARC SUBFIELD DATA']);
        }

        if (!empty($csvArray)) {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="multmarc-prepped.csv"');

            $output = fopen('php://output', 'w');

            // Get column names from the first row of the array
            $headerRow = array_keys(reset($csvArray));
            fputcsv($output, $headerRow);

            foreach ($csvArray as $row) {
                fputcsv($output, $row);
            }

            fclose($output);
        } else {
            echo 'The CSV array is empty.';
        }

//        echo '<pre>';
//        print_r($csvArray);
//        echo '</pre>';

    } else {
        echo 'Upload failed. Only CSV files are allowed.';
    }
} else {
    echo 'There was some error with the file upload.';
}
?>