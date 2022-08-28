<?php

namespace Rafni\MrtdReader\Policy;

/**
 * ICAO 9303-1 Machine Readable Travel Documents
 * https://www.icao.int/publications/pages/publication.aspx?docnum=9303
 * 
 * Part 3: Specifications Common to all MRTDs 
 * https://www.icao.int/publications/Documents/9303_p3_cons_en.pdf
 * https://www.icao.int/publications/Documents/9303_p3_cons_es.pdf
 */
abstract class CommonUtilities
{
    /**
     * Numeric values to take as substitution in integrity verificator for fields with alphabetic characters
     * @var array
     */
    public const LETTER_VALUES = [
        'A' => 10, 'B' => 11, 'C' => 12, 'D' => 13, 'E' => 14, 'F' => 15, 'G' => 16, 'H' => 17, 'I' => 18, 
        'J' => 19, 'K' => 20, 'L' => 21, 'M' => 22, 'N' => 23, 'O' => 24, 'P' => 25, 'Q' => 26, 'R' => 27,
        'S' => 28, 'T' => 29, 'U' => 30, 'V' => 31, 'W' => 32, 'X' => 33, 'Y' => 34, 'Z' => 35, '<' => 0
    ];

    /**
     * Calculate string integrity verification digit
     * @param string $string
     * @return int|null
     */
    protected function calcVerificationDigit($string)
    {
        $weightingFactor = [7, 3, 1];
        $productLastNumbers = [];
        $compoundBlocks = array_chunk(str_split($string), 3);
        foreach ($compoundBlocks as $blockKey => $block) {
            foreach ($block as $componentKey => $componentValue) {
                $product = strtr($componentValue, self::LETTER_VALUES) * $weightingFactor[$componentKey];
                $productLastNumbers[] = substr($product, -1, 1);
            }
        }
        return empty($productLastNumbers) ? null : substr(array_sum($productLastNumbers), -1, 1);
    }

    /**
     * Returns the data without padding characters (<)
     * @param string $string
     * @return string
     */
    protected function toPrintData($string)
    {
        return trim(str_replace('<', '', $string));
    }
}