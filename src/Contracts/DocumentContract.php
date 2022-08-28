<?php

namespace Rafni\MrtdReader\Contracts;

/**
 * ICAO 9303-1 Machine Readable Travel Documents MRTDs
 * https://www.icao.int/publications/pages/publication.aspx?docnum=9303
 */
interface DocumentContract
{
	/**
     * TD1 document integrity verificator digit verifier
     * @return object
     */
    public function verify();

    /**
     * Get the processed information available from the TD1 document
     * @return object
     */
    public function data();

    /**
     * Obtain the document type extracted from line 1 of the MRZ code of the TD1 document
     * @return string
     */
    public function documentType();

    /**
     * Obtain the code of the issuer of the document extracted from line 1 of the MRZ code of the TD1 document
     * @return string
     */
    public function issueCode();

    /**
     * Obtain the number of the document extracted from line 1 of the MRZ code of the TD1 document
     * @return string
     */
    public function documentNumber();

    /**
     * Obtain the person ID extracted from line 1 of the MRZ code of the TD1 document
     * @return string
     */
    public function personId();

    /**
     * Obtain the birthday date extracted from line 2 of the MRZ code of the TD1 document
     * @return DateTime
     */
    public function birthdayDate();

    /**
     * Obtain the sex extracted from line 2 of the MRZ code of the TD1 document
     * @return string
     */
    public function sex();

    /**
     * Obtain the expiration date of the document extracted from line 2 of the MRZ code of the TD1 document
     * @return DateTime
     */
    public function expirationDate();

    /**
     * Obtain the person nationality of the document extracted from line 2 of the MRZ code of the TD1 document
     * @return string
     */
    public function nationality();

    /**
     * Obtain the name of the person extracted from line 3 of the MRZ code of the TD1 document
     * @return string
     */
    public function name();

    /**
     * Obtain the surname of the person extracted from line 3 of the MRZ code of the TD1 document
     * @return string
     */
    public function surname();
}