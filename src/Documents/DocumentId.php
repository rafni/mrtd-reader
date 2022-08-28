<?php

namespace Rafni\MrtdReader\Documents;

use Rafni\MrtdReader\Contracts\DocumentContract;
use Rafni\MrtdReader\Policy\CommonUtilities;
use Rafni\MrtdReader\Policy\Country;
use DateTime;
use Exception;

/**
 * ICAO 9303-1 Machine Readable Travel Documents MRTDs
 * https://www.icao.int/publications/pages/publication.aspx?docnum=9303
 * 
 * Part 5: Specifications for TD1 Size Machine Readable Official Travel Documents (MROTDs)
 * https://www.icao.int/publications/Documents/9303_p5_cons_en.pdf
 * https://www.icao.int/publications/Documents/9303_p5_cons_es.pdf
 */
class DocumentId extends CommonUtilities implements DocumentContract
{
    /**
     * Patterns defined by the standard for each MRZ line of the TD1 document
     * @var array
     */
    public const PATTERNS = [
        '/^(A[^VIC]|C[A-Z<]|I[A-Z<])([A-Z<]{3})([A-Z0-9<]{9})(<|[0-9])([A-Z0-9<]{15})$/', // MRZ línea 1 - 30 caracteres
        '/^([0-9<]{6})([0-9])(F|M|<)([0-9]{6})([0-9])([A-Z<]{3})([A-Z0-9<]{11})([0-9])$/', // MRZ línea 2 - 30 caracteres
        '/^([A-Z<]{30})$/', // MRZ línea 3 - 30 caracteres
    ];

    /**
     * MRZ lines for analysis and extraction of information
     * @var array
     */
    protected $mrzLines;

    /**
     * Line 1 - Positions 1 to 2
     * @var string
     */
    protected $documentType;

    /**
     * Line 1 - Positions 3 to 5
     * @var string
     */
    protected $issueCode;

    /**
     * Line 1 - Positions 6 to 14
     * @var string
     */
    protected $documentNumber;

    /**
     * Line 1 - Position 15
     * Control digit for positions 6-14 or 6-14, 16-28 (if the position 15 contents <) last number is the new control digit
     * @var string
     */
    protected $documentNumberVerificationDigit;

    /**
     * Line 1 - Positions 16 to 30
     * @var string
     */
    protected $optionalData1;

    /**
     * Line 2 - Positions 1 to 6
     * @var string
     */
    protected $birthdayDate;

    /**
     * Line 2 - Position 7
     * @var string
     */
    protected $birthdayDateVerificationDigit;

    /**
     * Line 2 - Position 8
     * @var string
     */
    protected $sex;

    /**
     * Line 2 - Positions 9 to 14
     * @var string
     */
    protected $expirationDate;

    /**
     * Line 2 - Position 15
     * @var string
     */
    protected $expirationDateVerificationDigit;

    /**
     * Line 2 - Positions 16 to 18
     * @var string
     */
    protected $nationality;

    /**
     * Line 2 - Positions 19 to 29
     * @var string
     */
    protected $optionalData2;

    /**
     * Line 2 - Position 30
     * @var string
     */
    protected $compoundVerificationDigit;

    /**
     * Line 3 - Positions 1 to 30
     * @var string
     */
    protected $name;

    /**
     * Line 3 - Positions 1 to 30
     * @var string
     */
    protected $surname;

    /**
     * Create and validate according to the ICAO 9303-1 standard the TD1 document using the MRZ codes
     * @param array $mrzLine
     * @throws Exception
     */
	public function __construct(array $mrzLine)
    {
        if (count($mrzLine) != 3) {
            throw new Exception('MRZ codes do not comply with the ICAO 9303-1 standard for TD1 documents');
        }
        $this->mrzLines = array_values($mrzLine);
        foreach ($this->mrzLines as $lineNumber => $lineValue) {
            $hasValidLine = isset(self::PATTERNS[$lineNumber]) ? preg_match(self::PATTERNS[$lineNumber], $lineValue) : null;
            if ($hasValidLine == false) {
                throw new Exception(sprintf('The value %s of the line %s of the MRZ code does not comply with the ICAO 9303-1 standard for TD1 documents', $lineValue, strlen($lineValue), $lineNumber));
            } elseif ($lineNumber == 0) {
                $this->dumpMrzLine1($lineValue);
            } elseif ($lineNumber == 1) {
                $this->dumpMrzLine2($lineValue);
            } elseif ($lineNumber == 2) {
                $this->dumpMrzLine3($lineValue);
            }
        }
    }

    /**
     * TD1 document integrity verificator digit verifier
     * @return object
     */
    public function verify()
    {
        return (object) [
            'documentNumber' => $this->documentNumberVerificator(),
            'birthdayDate' => $this->birthdayDateVerificator(),
            'expirationDate' => $this->expirationDateVerificator(),
            'compound' => $this->compoundVerification()
        ];
    }

    /**
     * Get the processed information available from the TD1 document
     * @return object
     */
    public function data()
    {
        return (object) array_map(function($value) {
            if (is_string($value)) {
                return $this->toPrintData($value);
            }
            return $value;
        }, get_object_vars($this));
    }

    /**
     * Obtain the document type extracted from line 1 of the MRZ code of the TD1 document
     * @return string
     */
    public function documentType()
    {
        return $this->toPrintData($this->documentType);
    }

    /**
     * Obtain the code of the issuer of the document extracted from line 1 of the MRZ code of the TD1 document
     * @return string
     */
    public function issueCode()
    {
        $issue = $this->toPrintData($this->issueCode);
        return ! is_null(Country::search($issue)) ? Country::search($issue) : $issue;
    }

    /**
     * Obtain the number of the document extracted from line 1 of the MRZ code of the TD1 document
     * @return string
     */
    public function documentNumber()
    {
        if ($this->documentNumberVerificationDigit == '<') {
            $secondPartDocument = $this->toPrintData($this->optionalData1);
            return $this->documentNumber . substr($secondPartDocument, 0, strlen($secondPartDocument) - 1);
        }
        return $this->toPrintData($this->documentNumber);
    }

    /**
     * Obtain the person ID extracted from line 1 of the MRZ code of the TD1 document
     * @return string
     */
    public function personId()
    {
        return $this->optionalData1();
    }

    /**
     * Obtain the first optional information extracted from line 1 of the MRZ code of the TD1 document
     * @return string
     */
    public function optionalData1()
    {
        return $this->toPrintData($this->optionalData1);
    }

    /**
     * Obtain the birthday date extracted from line 2 of the MRZ code of the TD1 document
     * @return DateTime
     */
    public function birthdayDate()
    {
        return DateTime::createFromFormat('ymd', $this->birthdayDate);
    }

    /**
     * Obtain the sex extracted from line 2 of the MRZ code of the TD1 document
     * @return string
     */
    public function sex()
    {
        return $this->sex == '<' ? null : $this->sex;
    }

    /**
     * Obtain the expiration date of the document extracted from line 2 of the MRZ code of the TD1 document
     * @return DateTime
     */
    public function expirationDate()
    {
        return DateTime::createFromFormat('ymd', $this->expirationDate);
    }

    /**
     * Obtain the person nationality of the document extracted from line 2 of the MRZ code of the TD1 document
     * @return string
     */
    public function nationality()
    {
        $nationality = $this->toPrintData($this->nationality);
        return ! is_null(Country::search($nationality)) ? Country::search($nationality) : $nationality;
    }

    /**
     * Obtain the second optional information extracted from line 2 of the MRZ code of the TD1 document
     * @return string
     */
    public function optionalData2()
    {
        return $this->toPrintData($this->optionalData2);
    }

    /**
     * Obtain the name of the person extracted from line 3 of the MRZ code of the TD1 document
     * @return string
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * Obtain the surname of the person extracted from line 3 of the MRZ code of the TD1 document
     * @return string
     */
    public function surname()
    {
        return $this->surname;
    }

    /**
     * Extract and dump the data from line 1 MRZ of the TD1 document
     * @param string
     */
    protected function dumpMrzLine1(string $mrzLine1)
    {
        preg_match(self::PATTERNS[0], $mrzLine1, $mrzLineParts);

        $this->documentType = isset($mrzLineParts[1]) ? $mrzLineParts[1] : null;
        $this->issueCode = isset($mrzLineParts[2]) ? $mrzLineParts[2] : null;
        $this->documentNumber = isset($mrzLineParts[3]) ? $mrzLineParts[3] : null;
        $this->documentNumberVerificationDigit = isset($mrzLineParts[4]) ? $mrzLineParts[4] : null;
        $this->optionalData1 = isset($mrzLineParts[5]) ? $mrzLineParts[5] : null;
    }

    /**
     * Extract and dump the data from line 2 MRZ of the TD1 document
     * @param string
     */
    protected function dumpMrzLine2(string $mrzLine2)
    {
        preg_match(self::PATTERNS[1], $mrzLine2, $mrzLineParts);

        $this->birthdayDate = isset($mrzLineParts[1]) ? $mrzLineParts[1] : null;
        $this->birthdayDateVerificationDigit = isset($mrzLineParts[2]) ? $mrzLineParts[2] : null;
        $this->sex = isset($mrzLineParts[3]) ? $mrzLineParts[3] : null;
        $this->expirationDate = isset($mrzLineParts[4]) ? $mrzLineParts[4] : null;
        $this->expirationDateVerificationDigit = isset($mrzLineParts[5]) ? $mrzLineParts[5] : null;
        $this->nationality = isset($mrzLineParts[6]) ? $mrzLineParts[6] : null;
        $this->optionalData2 = isset($mrzLineParts[7]) ? $mrzLineParts[7] : null;
        $this->compoundVerificationDigit = isset($mrzLineParts[8]) ? $mrzLineParts[8] : null;
    }

    /**
     * Extract and dump the data from line 3 MRZ of the TD1 document
     * @param string
     */
    protected function dumpMrzLine3(string $mrzLine3)
    {
        $splittedParts = explode('<', $mrzLine3);
        $toReturn = [];

        $inSurnamePosition = true;
        foreach($splittedParts as $key => $value) {
            if (! empty($value)) {
                $toReturn[$inSurnamePosition === true ? 'surname' : 'name'][] = $value;
            } else {
                $inSurnamePosition = false;
            }
        };

        $this->name = isset($toReturn['name']) ? implode(' ', $toReturn['name']) : null;
        $this->surname = isset($toReturn['surname']) ? implode(' ', $toReturn['surname']) : null;
    }

    /**
     * Integrity verificator of the document number of the MRZ line 1 of the TD1 document
     * @return object
     */
    protected function documentNumberVerificator()
    {
        $documentNumber = $this->documentNumber();
        $verificationDigit = $this->documentNumberVerificationDigit;
        if ($verificationDigit == '<') {
            $documentNumber .= substr($this->toPrintData($this->optionalData1), 0, strlen($this->toPrintData($this->optionalData1)) - 1);
            $verificationDigit = substr($this->toPrintData($this->optionalData1), -1, 1);
        }

        return (object) [
            'value' => $documentNumber,
            'verified' => $verificationDigit == $this->calcVerificationDigit($documentNumber)
        ];
    }

    /**
     * Integrity verificator of the birthday date of the MRZ line 2 of the TD1 document
     * @return object
     */
    protected function birthdayDateVerificator()
    {
        return (object) [
            'value' => $this->birthdayDate,
            'verified' => $this->birthdayDateVerificationDigit == $this->calcVerificationDigit($this->birthdayDate)
        ];
    }

    /**
     * Integrity verificator of the expiration date of the document of the MRZ line 2 of the TD1 document
     * @return object
     */
    protected function expirationDateVerificator()
    {
        return (object) [
            'value' => $this->expirationDate,
            'verified' => $this->expirationDateVerificationDigit == $this->calcVerificationDigit($this->expirationDate)
        ];
    }

    /**
     * Integrity verificator composed of MRZ lines 1 and 2 of the TD1 document
     * @return object
     */
    protected function compoundVerification()
    {
        preg_match('/^.{5}(.{25})$/', $this->mrzLines[0], $mrzLine1Parts);
        preg_match('/^(.{7}).{1}(.{7}).{3}(.{11}).{1}$/', $this->mrzLines[1], $mrzLine2Parts);
        unset($mrzLine1Parts[0], $mrzLine2Parts[0]);
        $valuesToCheck = implode('', array_merge($mrzLine1Parts, $mrzLine2Parts));

        return (object) [
            'value' => $valuesToCheck,
            'verified' => $this->compoundVerificationDigit == $this->calcVerificationDigit($valuesToCheck)
        ];
    }

    /**
     * Accessor for the attributes that you want to publish only for reading from the outside
     * @param string $attribute
     * @throws Exception
     * @return string
     */
    public function __get($attribute)
    {
        switch ($attribute) {
            case 'documentType': return $this->documentType();
            case 'issueCode': return $this->issueCode();
            case 'documentNumber': return $this->documentNumber();
            case 'personId': return $this->personId();
            case 'optionalData1': return $this->optionalData1();
            case 'birthdayDate': return $this->birthdayDate();
            case 'sex': return $this->sex();
            case 'expirationDate': return $this->expirationDate();
            case 'nationality': return $this->nationality();
            case 'optionalData2': return $this->optionalData2();
            case 'name': return $this->name();
            case 'surname': return $this->surname();
            default: throw new Exception(sprintf('The attribute %s you are trying to access does not exist', $attribute));
        }
    }
}