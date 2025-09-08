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
    private const FILE_NAME = 'products.csv';

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
        $limit = (int) \Configuration::get('STATUSAUTOMATION_BATCH_SIZE');
        if ($limit < 0) {
            $limit = 100;
        }

        return $limit;
    }

    public function uploadImportAction(Request $request)
    {
        $limit = $this->getBatchLimit();
        $offset = (int) $request->request->get('offset');
        $total_count = (int) $request->request->get('total_count');

        $this->FILE_SEPARATOR = $this->detectCsvDelimiter($this->getPath(self::FILE_NAME));

        $this->processData($offset, $limit);

        $offset = (int) $offset + $limit;

        $response = [
            'status' => true,
            // 'runtime_count' => $this->runtime_count,
            // 'total_count' => $total_count,
            'offset' => $offset,
            'done' => $total_count <= $offset,
            'message' => $this->trans('Successfully Uploaded', 'Modules.Statusautomation.Controller.php'),
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
        $file = $this->getPath(self::FILE_NAME);
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
        $uploadedFile = $request->files->get('file');
        try {
            if ($uploadedFile instanceof UploadedFile) {
                $fileType = $uploadedFile->getMimeType();
                $fileExtension = $uploadedFile->getClientOriginalExtension();

                $allowedMimeTypes = ['text/plain', 'text/csv', 'application/vnd.ms-excel'];
                $allowedExtensions = ['csv'];

                // Validate MIME type and extension
                if (!in_array($fileType, $allowedMimeTypes) || !in_array($fileExtension, $allowedExtensions)) {
                    $response = [
                        'status' => false,
                        'message' => $this->trans('Invalid file type. Please upload a CSV file.', 'Modules.Statusautomation.Controller.php'),
                    ];
                } else {
                    $uploadedFileObj = new UploadedFile($uploadedFile->getRealPath(), self::FILE_NAME, $uploadedFile->getMimeType());

                    $target = $uploadedFileObj->move($this->getPath(), self::FILE_NAME);

                    $response = [
                        'status' => true,
                        'total_count' => $this->getTotalCount(),
                        'message' => $this->trans('Successfully Uploaded File. <br /> Further Processing.', 'Modules.Statusautomation.Controller.php'),
                    ];
                }
            } else {
                $response = [
                    'status' => false,
                    'message' => $this->trans('No file was uploaded.', 'Modules.Statusautomation.Controller.php'),
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
        $rows = $this->getBlackListedNumbers();
        // Create a StreamedResponse to stream the CSV file
        $response = new StreamedResponse(function () use ($rows) {
            // Open output stream
            $handle = fopen('php://output', 'w');

            // Add CSV header
            fputcsv($handle, ['Phone Numbers'], $this->FILE_SEPARATOR);

            // Loop through the data and write to the CSV file
            foreach ($rows as $row) {
                // $id_product_attribute = \Product::getDefaultAttribute($product['id_product']);
                // $x = $this->getAttributeGroupValueFromProductAttribute($id_product_attribute);
                fputcsv($handle, [$row['phone_number']], $this->FILE_SEPARATOR);
            }

            // Close the output stream
            fclose($handle);
        });

        // Set headers to trigger file download
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="products.csv"');
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
        $file = $this->getPath(self::FILE_NAME);

        if (file_exists($file)) {
            // Read the CSV file
            if (($handle = fopen($file, 'r')) !== false) {
                // Read the header row
                fgetcsv($handle, null, $this->FILE_SEPARATOR);

                // skip
                for ($i = 0; $i < $offset; ++$i) {
                    if (fgetcsv($handle, null, $this->FILE_SEPARATOR) === false) {
                        fclose($handle);

                        return;
                    }
                }

                // Process each row of the CSV file
                while (($data = fgetcsv($handle, null, $this->FILE_SEPARATOR)) !== false) {
                    // $id_product = (int) $data[0];
                    // $proportionArr = explode('x', $data[2]);
                    // $proportionValue = 0;
                    // if (!empty($proportionArr)) {
                    //     $counted = count($proportionArr);
                    //     if ($counted == 2) {
                    //         $default_attribute_value = $proportionArr[0];
                    //         $default_attribute_divisor = $proportionArr[1];
                    //         $proportionValue = number_format(round((float) $default_attribute_value / (float) $default_attribute_divisor, \Pproductsize::ROUND_PLACES), \Pproductsize::ROUND_PLACES);
                    //     } elseif ($counted == 1) {
                    //         $proportionValue = (float) $proportionArr[0];
                    //     }
                    // }

                    // if ($id_product) {
                    //     \Pproductsize::saveProportion($id_product, $proportionValue);
                    // }

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
}
