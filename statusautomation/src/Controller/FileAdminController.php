<?php

/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

namespace PrestaShop\Module\Statusautomation\Controller;

if (!defined('_PS_VERSION_')) {
    exit;
}

use PhpOffice\PhpSpreadsheet\IOFactory;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use PrestaShopBundle\Exception\FileUploadException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileAdminController extends FrameworkBundleAdminController
{
    private $FILE_SEPARATOR = ';';
    /**
     * @var int|null
     */
    private $shopId;
    private const FILE_NAME = 'blacklist_numbers';
    private const FILE_EXTENSION = 'csv';

    // private $runtime_count = 0;
    public function __construct($shopId)
    {
        $this->shopId = $shopId;
    }

    private static function getPath($file = '')
    {
        return _PS_MODULE_DIR_ . 'statusautomation' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'file' . DIRECTORY_SEPARATOR . $file;
    }

    protected function getBatchLimit()
    {
        $limit = (int) \Configuration::get('STATUSAUTOMATION_PHASE_1_BATCH_SIZE', 100);
        if ($limit <= 0) {
            $limit = 100;
        }

        return (int) $limit;
    }

    public function getCSVName()
    {
        return self::FILE_NAME . '.' . self::FILE_EXTENSION;
    }

    public function uploadImportAction(Request $request)
    {
        $limit = $this->getBatchLimit();
        $offset = (int) $request->request->get('offset');
        $total_count = (int) $request->request->get('total_count');

        $this->FILE_SEPARATOR = $this->detectCsvDelimiter($this->getPath($this->getCSVName()));

        $this->processData($offset, $limit);

        $offset = (int) $offset + $limit;

        $response = [
            'status' => true,
            // 'runtime_count' => $this->runtime_count,
            // 'total_count' => $total_count,
            'offset' => $offset,
            'done' => $total_count <= $offset,
            'message' => $this->trans('Successfully Uploaded', 'Modules.Statusautomation.FileAdminController.php'),
        ];

        return $this->json($response);
    }

    protected function detectCsvDelimiter($file, $delimiters = [';', ','])
    {
        // Open the file for reading
        $handle = fopen($file, 'r');

        // Read the first line from the file
        $firstLine = fgets($handle);
        fclose($handle);

        // Store the detected delimiter
        $detectedDelimiter = null;

        // Count occurrences of each delimiter
        $delimiterCount = [];
        foreach ($delimiters as $delimiter) {
            $delimiterCount[$delimiter] = substr_count($firstLine, $delimiter);
        }

        // Find the delimiter with the most occurrences
        $detectedDelimiter = array_search(max($delimiterCount), $delimiterCount);

        return $detectedDelimiter;
    }

    protected function getTotalCount()
    {
        $recordCount = 0;
        $file = $this->getPath($this->getCSVName());
        if (file_exists($file)) {
            if (($handle = fopen($file, 'r')) !== false) {
                while (fgetcsv($handle, null, $this->FILE_SEPARATOR) !== false) {
                    ++$recordCount;
                }
                fclose($handle);
            }
        }

        return $recordCount;
    }

    public function uploadAction(Request $request)
    {
        \Statusautomation::initUploadProcess($this->getPath($this->getCSVName()), $this->getPath(self::FILE_NAME . '.xlsx'));

        $uploadedFile = $request->files->get('file');

        try {
            if ($uploadedFile instanceof UploadedFile) {
                // $file = $this->excelToCsvFile($uploadedFile);
                $fileType = $uploadedFile->getMimeType();
                $fileExtension = $uploadedFile->getClientOriginalExtension();

                $allowedMimeTypes = ['text/plain', 'text/csv', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
                $allowedExtensions = ['csv', 'xlsx'];

                // Validate MIME type and extension
                if (!in_array($fileType, $allowedMimeTypes) || !in_array($fileExtension, $allowedExtensions)) {
                    $response = [
                        'status' => false,
                        'message' => $this->trans('Invalid file type. Please upload a CSV/XLSX file.', 'Modules.Statusautomation.FileAdminController.php'),
                    ];
                } else {
                    $uploadedFileObj = new UploadedFile($uploadedFile->getRealPath(), self::FILE_NAME . '.' . $fileExtension, $uploadedFile->getMimeType());

                    $target = $uploadedFileObj->move($this->getPath(), self::FILE_NAME . '.' . $fileExtension);

                    if ($fileExtension === 'xlsx') {
                        $csvFilePath = $this->excelToCsvFile(self::FILE_NAME . '.' . $fileExtension);
                    }

                    $response = [
                        'status' => true,
                        'total_count' => $this->getTotalCount(),
                        'message' => $this->trans('Successfully Uploaded File. <br /> Further Processing.', 'Modules.Statusautomation.FileAdminController.php'),
                    ];
                }
            } else {
                $response = [
                    'status' => false,
                    'message' => $this->trans('No file was uploaded.', 'Modules.Statusautomation.FileAdminController.php'),
                ];
            }
        } catch (FileUploadException $e) {
            $response = [
                'status' => false,
                'error' => $e->getMessage(),
            ];
        }

        return $this->json($response);
    }

    public function downloadAction()
    {
        // Step 1: Fetch the data (id_product and proportion)
        $orders = $this->getBlackListedNumbers();
        // Create a StreamedResponse to stream the CSV file
        $response = new StreamedResponse(function () use ($orders) {
            // Open output stream
            $handle = fopen('php://output', 'w');

            // Add CSV header
            // fputcsv($handle, ['Phone Numbers'], $this->FILE_SEPARATOR);

            // Loop through the data and write to the CSV file
            foreach ($orders as $row) {
                fputcsv($handle, [$row['phone_number']], $this->FILE_SEPARATOR);
            }

            // Close the output stream
            fclose($handle);
        });

        // Set headers to trigger file download
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $this->getCSVName() . '"');
        $response->headers->set('Cache-Control', 'max-age=0, no-cache, no-store, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');

        // Return the response
        return $response;
    }

    // Helper function to get the products and proportion data
    private function getBlackListedNumbers()
    {
        // $sql = '
        // SELECT DISTINCT sb.phone_number
        // FROM ' . _DB_PREFIX_ . 'ts_whatsapp tw
        // LEFT JOIN ' . _DB_PREFIX_ . 'customer pac ON tw.id_customer = pac.id_customer
        // LEFT JOIN ' . _DB_PREFIX_ . 'statusautomation_blacklist sb ON sb.phone_number = tw.whatsapp_number
        // WHERE sb.is_blacklisted = "YES"';

        $sql = '
        SELECT DISTINCT sb.phone_number
        FROM ' . _DB_PREFIX_ . 'statusautomation_blacklist sb
        WHERE sb.is_blacklisted = "YES"';

        return \Db::getInstance()->executeS($sql);
    }

    public function processData($offset, $new_limit)
    {
        $file = $this->getPath($this->getCSVName());

        if (file_exists($file)) {
            // Read the CSV file
            if (($handle = fopen($file, 'r')) !== false) {
                // skip
                for ($i = 0; $i < $offset; ++$i) {
                    if (fgetcsv($handle, null, $this->FILE_SEPARATOR) === false) {
                        fclose($handle);

                        return;
                    }
                }

                // Process each row of the CSV file
                while (($data = fgetcsv($handle, null, $this->FILE_SEPARATOR)) !== false) {
                    $phone_number = (string) $data[0];

                    if ($this->isPhoneNumberValid($phone_number)) {
                        \StatusautomationBlacklist::saveBlacklist($phone_number);
                    }

                    if (!$new_limit--) {
                        break;
                    }
                    // $this->runtime_count++;
                }

                // Close the file
                fclose($handle);
            }
        }
    }

    private function isPhoneNumberValid($phone_number)
    {
        return ctype_digit($phone_number);
    }

    private function excelToCsvFile($filename)
    {
        $dest_file = '';
        if (preg_match('#(.*?)\.(csv)#is', $filename)) {
            $dest_file = $this->getPath((string) preg_replace('/\.{2,}/', '.', $filename));
        } else {
            $csv_folder = $this->getPath();
            $excel_folder = $csv_folder;
            $info = pathinfo($filename);
            $csv_name = basename($filename, '.' . $info['extension']) . '.csv';
            $dest_file = $excel_folder . $csv_name;

            if (!is_dir($excel_folder)) {
                mkdir($excel_folder);
            }

            if (!is_file($dest_file)) {
                $reader_excel = IOFactory::createReaderForFile($csv_folder . $filename);
                $reader_excel->setReadDataOnly(true);
                $excel_file = $reader_excel->load($csv_folder . $filename);

                $csv_writer = IOFactory::createWriter($excel_file, 'Csv');

                $csv_writer->setSheetIndex(0);
                $csv_writer->setDelimiter(';');
                $csv_writer->save($dest_file);
            }
        }

        return $dest_file;
    }
}
