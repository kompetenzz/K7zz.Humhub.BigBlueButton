<?php

namespace k7zz\humhub\bbb\helpers;

use Spatie\PdfToImage\Enums\OutputFormat;
use Spatie\PdfToImage\Pdf;
use Yii;


class Tools
{
    /**
     * Converts the first page of a PDF file to a PNG image.
     *
     * @param string $pdfPath Path to the input PDF file.
     * @param string $outPath Path to save the output file. File extension determines format.
     * @param int $width Desired width of the output image. Height is scaled proportionally.
     */
    public static function pdfFirstPageToPng(string $pdfPath, string $outPath, int $width = 300): bool
    {
        try {
            $pdf = new Pdf($pdfPath);
            $realPaths = $pdf
                ->resolution(150)
                ->quality(90)
                ->size($width)
                ->format(OutputFormat::Png)
                ->selectPage(1)
                ->save($outPath);
            return count($realPaths) && $realPaths[0] == $outPath;

        } catch (\Exception $e) {
            Yii::error("Error converting PDF to PNG: " . $e->getMessage(), 'bbb');
        }
        return false;
    }
}