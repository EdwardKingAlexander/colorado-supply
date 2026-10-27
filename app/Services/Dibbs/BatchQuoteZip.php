<?php

namespace App\Services\Dibbs;

use ZipArchive;

/**
 * Reads the bq*.txt and as*.txt entries out of a DIBBS bqYYMMDD.zip.
 */
class BatchQuoteZip
{
    /**
     * @return array{bq: ?string, as: ?string}
     *
     * @throws DibbsLayoutException when the content is not a readable zip
     */
    public function read(string $zipContents): array
    {
        $tmp = tempnam(sys_get_temp_dir(), 'dibbs-bq-');
        file_put_contents($tmp, $zipContents);

        try {
            $zip = new ZipArchive;

            if ($zip->open($tmp) !== true) {
                throw new DibbsLayoutException('Batch quote file is not a readable zip archive.');
            }

            $found = ['bq' => null, 'as' => null];

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = strtolower(basename((string) $zip->getNameIndex($i)));

                foreach (['bq', 'as'] as $prefix) {
                    if ($found[$prefix] === null && preg_match('/^'.$prefix.'\d{6}\.txt$/', $name)) {
                        $found[$prefix] = (string) $zip->getFromIndex($i);
                    }
                }
            }

            $zip->close();

            if ($found['bq'] === null) {
                throw new DibbsLayoutException('Batch quote zip contains no bqYYMMDD.txt entry.');
            }

            return $found;
        } finally {
            @unlink($tmp);
        }
    }
}
