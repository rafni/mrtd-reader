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
 * Part 4: Specifications for Machine Readable Passports (MRPs) and other TD3 Size MRTDs
 * https://www.icao.int/publications/Documents/9303_p4_cons_en.pdf
 * https://www.icao.int/publications/Documents/9303_p4_cons_es.pdf
 */
class Passport extends CommonUtilities implements DocumentContract
{
	/**
     * Patterns defined by the standard for each MRZ line of the TD3 document
     * @var array
     */
    public const PATTERNS = [
        '/^(P[A-Za-z<]{1})([A-Za-z<]{3})([A-Za-z<]{39})$/', // MRZ línea 1 - 44 caracteres
        '/^([A-Za-z0-9<]{9})([0-9]{1})([A-Za-z<]{3})([0-9]{6})([0-9]{1})(F|M|<)([0-9]{6})([0-9]{1})([A-Za-z0-9<]{14})([0-9]{1})([0-9]{1})$/', // MRZ línea 2 - 44 caracteres
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
     * Line 1 - Positions 6 to 44
     * @var string
     */
    protected $name;

    /**
     * Line 1 - Positions 6 to 44
     * @var string
     */
    protected $surname;

    /**
     * Line 2 - Positions 1 to 9
     * @var string
     */
    protected $documentNumber;

    /**
     * Line 2 - Position 10
     * @var string
     */
    protected $documentNumberVerificationDigit;

    /**
     * Line 2 - Positions 11 to 13
     * @var string
     */
    protected $nationality;

    /**
     * Line 2 - Positions 14 to 19
     * @var string
     */
    protected $birthdayDate;

    /**
     * Line 2 - Position 20
     * @var string
     */
    protected $birthdayDateVerificationDigit;

    /**
     * Line 2 - Position 21
     * @var string
     */
    protected $sex;

    /**
     * Line 2 - Positions 22 to 27
     * @var string
     */
    protected $expirationDate;

    /**
     * Line 2 - Position 28
     * @var string
     */
    protected $expirationDateVerificationDigit;

    /**
     * Line 2 - Positions 29 to 42
     * @var string
     */
    protected $personalNumber;

    /**
     * Line 2 - Position 43
     * @var string
     */
    protected $personalNumberVerificationDigit;

    /**
     * Line 2 - Position 44
     * @var string
     */
    protected $compoundVerificationDigit;

    /**
     * Create and validate according to the ICAO 9303-1 standard the TD3 document using the MRZ codes
     * @param array $mrzLine
     * @throws Exception
     */
	public function __construct(array $mrzLine)
    {
        if (count($mrzLine) != 2) {
            throw new Exception('MRZ codes do not comply with the ICAO 9303-1 standard for TD3 documents');
        }
        $this->mrzLines = array_values($mrzLine);
        foreach ($this->mrzLines as $lineNumber => $lineValue) {
            $hasValidLine = isset(self::PATTERNS[$lineNumber]) ? preg_match(self::PATTERNS[$lineNumber], $lineValue) : null;
            if ($hasValidLine == false) {
                throw new Exception(sprintf('The value %s of the line %s of the MRZ code does not comply with the ICAO 9303-1 standard for TD3 documents', $lineValue, strlen($lineValue), $lineNumber));
            } elseif ($lineNumber == 0) {
                $this->dumpMrzLine1($lineValue);
            } elseif ($lineNumber == 1) {
                $this->dumpMrzLine2($lineValue);
            }
        }
    }

    /**
     * TD3 document integrity verificator digit verifier
     * @return object
     */
    public function verify()
    {
        return (object) [
            'documentNumber' => $this->documentNumberVerificator(),
            'birthdayDate' => $this->birthdayDateVerificator(),
            'expirationDate' => $this->expirationDateVerificator(),
            'personalNumber' => $this->personalNumberVerificator(),
            'compound' => $this->compoundVerification()
        ];
    }

    /**
     * Get the processed information available from the TD3 document
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
     * Obtain the document type extracted from line 1 of the MRZ code of the TD3 document
     * @return string
     */
    public function documentType()
    {
        return $this->toPrintData($this->documentType);
    }

    /**
     * Obtain the code of the issuer of the document extracted from line 1 of the MRZ code of the TD3 document
     * @return string
     */
    public function issueCode()
    {
        $issue = $this->toPrintData($this->issueCode);
        return ! is_null(Country::search($issue)) ? Country::search($issue) : $issue;
    }

    
    /**
     * Obtain the name of the person extracted from line 1 of the MRZ code of the TD3 document
     * @return string
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * Obtain the surname of the person extracted from line 1 of the MRZ code of the TD3 document
     * @return string
     */
    public function surname()
    {
        return $this->surname;
    }

    /**
     * Obtain the number of the document extracted from line 2 of the MRZ code of the TD3 document
     * @return string
     */
    public function documentNumber()
    {
        return $this->toPrintData($this->documentNumber);
    }

    /**
     * Obtain the person nationality of the document extracted from line 2 of the MRZ code of the TD3 document
     * @return string
     */
    public function nationality()
    {
        $nationality = $this->toPrintData($this->nationality);
        return ! is_null(Country::search($nationality)) ? Country::search($nationality) : $nationality;
    }

    /**
     * Obtain the birthday date extracted from line 2 of the MRZ code of the TD3 document
     * @return DateTime
     */
    public function birthdayDate()
    {
        return DateTime::createFromFormat('ymd', $this->birthdayDate);
    }

    /**
     * Obtain the sex extracted from line 2 of the MRZ code of the TD3 document
     * @return string
     */
    public function sex()
    {
        return $this->sex == '<' ? null : $this->sex;
    }

    /**
     * Obtain the expiration date of the document extracted from line 2 of the MRZ code of the TD3 document
     * @return DateTime
     */
    public function expirationDate()
    {
        return DateTime::createFromFormat('ymd', $this->expirationDate);
    }

    /**
     * Obtain the person ID extracted from line 2 of the MRZ code of the TD3 document
     * @return string
     */
    public function personId()
    {
        return $this->personalNumber;
    }

    /**
     * Extract and dump the data from line 1 MRZ of the TD3 document
     * @param string
     */
    protected function dumpMrzLine1(string $mrzLine1)
    {
        preg_match(self::PATTERNS[0], $mrzLine1, $mrzLineParts);

        $this->documentType = isset($mrzLineParts[1]) ? $mrzLineParts[1] : null;
        $this->issueCode = isset($mrzLineParts[2]) ? $mrzLineParts[2] : null;
        $fullName = isset($mrzLineParts[3]) ? $mrzLineParts[3] : null;
        $this->dumpName($fullName);
    }

    /**
     * Extract and dump the data from line 2 MRZ of the TD3 document
     * @param string
     */
    protected function dumpMrzLine2(string $mrzLine2)
    {
        preg_match(self::PATTERNS[1], $mrzLine2, $mrzLineParts);

        $this->documentNumber = isset($mrzLineParts[1]) ? $mrzLineParts[1] : null;
        $this->documentNumberVerificationDigit = isset($mrzLineParts[2]) ? $mrzLineParts[2] : null;
        $this->nationality = isset($mrzLineParts[3]) ? $mrzLineParts[3] : null;
        $this->birthdayDate = isset($mrzLineParts[4]) ? $mrzLineParts[4] : null;
        $this->birthdayDateVerificationDigit = isset($mrzLineParts[5]) ? $mrzLineParts[5] : null;
        $this->sex = isset($mrzLineParts[6]) ? $mrzLineParts[6] : null;
        $this->expirationDate = isset($mrzLineParts[7]) ? $mrzLineParts[7] : null;
        $this->expirationDateVerificationDigit = isset($mrzLineParts[8]) ? $mrzLineParts[8] : null;
        $this->personalNumber = isset($mrzLineParts[9]) ? $mrzLineParts[9] : null;
        $this->personalNumberVerificationDigit = isset($mrzLineParts[10]) ? $mrzLineParts[10] : null;
        $this->compoundVerificationDigit = isset($mrzLineParts[11]) ? $mrzLineParts[11] : null;
    }

    /**
     * Extract and dump the name parts from line 1 MRZ of the TD3 document
     * @param string
     */
    protected function dumpName(string $name)
    {
        $splittedParts = explode('<', $name);
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
     * Integrity verificator of the document number of the MRZ line 2 of the TD3 document
     * @return object
     */
    protected function documentNumberVerificator()
    {
        return (object) [
            'value' => $this->documentNumber,
            'verified' => $this->documentNumberVerificationDigit == $this->calcVerificationDigit($this->documentNumber)
        ];
    }

    /**
     * Integrity verificator of the birthday date of the MRZ line 2 of the TD3 document
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
     * Integrity verificator of the expiration date of the document of the MRZ line 2 of the TD3 document
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
     * Integrity verificator of the personal number of the document of the MRZ line 2 of the TD3 document
     * @return object
     */
    protected function personalNumberVerificator()
    {
        return (object) [
            'value' => $this->personalNumber,
            'verified' => $this->personalNumberVerificationDigit == $this->calcVerificationDigit($this->personalNumber)
        ];
    }

    /**
     * Integrity verificator composed of MRZ lines 1 and 2 of the TD3 document
     * @return object
     */
    protected function compoundVerification()
    {
        preg_match('/^(.{10}).{3}(.{7}).{1}(.{22}).{1}$/', $this->mrzLines[1], $mrzLine2Parts);
        unset($mrzLine2Parts[0]);
        $valuesToCheck = implode('', $mrzLine2Parts);

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
            case 'name': return $this->name();
            case 'surname': return $this->surname();
            case 'documentNumber': return $this->documentNumber();
            case 'nationality': return $this->nationality();
            case 'birthdayDate': return $this->birthdayDate();
            case 'sex': return $this->sex();
            case 'expirationDate': return $this->expirationDate();
            case 'personId': return $this->personId();
            default: throw new Exception(sprintf('The attribute %s you are trying to access does not exist', $attribute));
        }
    }
}