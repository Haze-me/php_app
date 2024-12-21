<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use League\Csv\Reader;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception;
use Illuminate\Support\Facades\Log;

class FileService
{
   /**
    * Validate the number of rows in a CSV file.
    *
    * @param UploadedFile $file
    * @return bool
    */
   public function validateCsvRows(UploadedFile $file): bool
   {
      try {
      $csv = Reader::createFromPath($file->getPathname(), 'r');
      $csv->setHeaderOffset(0);
      $rowsCount = iterator_count($csv->getRecords());

      return $rowsCount > 5;
      } catch (Exception $e) {
         Log::error('Unable to read csv file: '.$e->getMessage());
         return false;
      }
   }

   /**
    * Validate the number of rows in an XLSX file.
    *
    * @param UploadedFile $file
    * @return bool
    */
   public function validateXlsxRows(UploadedFile $file): bool
   {
      try {
         $spreadsheet = IOFactory::load($file->getPathname());
         $sheet = $spreadsheet->getActiveSheet();
         $rowsCount = $sheet->getHighestRow() - 1; // Exclude header row

         return $rowsCount > 5;
      } catch (Exception $e) {
         Log::error('Unable to read xlsx file: '.$e->getMessage());
         return false;
      }
   }

   /**
    * Validate file extension and number of rows.
    *
    * @param UploadedFile $file
    * @return bool|string
    */
   public function validateFileRows(UploadedFile $file)
   {
      $extension = $file->getClientOriginalExtension();

      if ($extension == 'csv') {
         if (!$this->validateCsvRows($file)) {
            return 'The CSV file must contain more than 5 rows.';
         }
      } elseif ($extension == 'xlsx') {
         if (!$this->validateXlsxRows($file)) {
            return 'The XLSX file must contain more than 5 rows.';
         }
      } else {
         return 'The uploaded file must be a CSV or XLSX file.';
      }

      return true;
   }
}
