<?php

namespace Rafni\MrtdReader\Contracts;

use DateTime;

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
    public function verify() : object;

    /**
     * Get the processed information available from the MRTD document
     * @return object
     */
    public function data() : object;

    /**
     * Obtain the document type extracted from MRZ code of the MRTD document
     * @return string
     */
    public function documentType();

    /**
     * Obtain the code of the issuer of the document extracted from MRZ code of the MRTD document
     * @return object|string
     */
    public function issueCode();

    /**
     * Obtain the number of the document extracted from MRZ code of the MRTD document
     * @return string
     */
    public function documentNumber();

    /**
     * Obtain the person ID extracted from MRZ code of the MRTD document
     * @return string
     */
    public function personId();

    /**
     * Obtain the birthday date extracted from MRZ code of the MRTD document
     * @return DateTime
     */
    public function birthdayDate() : DateTime;

    /**
     * Obtain the sex extracted from MRZ code of the MRTD document
     * @return string
     */
    public function sex();

    /**
     * Obtain the expiration date of the document extracted from MRZ code of the MRTD document
     * @return DateTime
     */
    public function expirationDate() : DateTime;

    /**
     * Obtain the person nationality of the document extracted from MRZ code of the MRTD document
     * @return object|string
     */
    public function nationality();

    /**
     * Obtain the name of the person extracted from MRZ code of the MRTD document
     * @return string
     */
    public function name();

    /**
     * Obtain the surname of the person extracted from MRZ code of the MRTD document
     * @return string
     */
    public function surname();
}