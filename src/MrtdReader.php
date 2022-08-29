<?php

namespace Rafni\MrtdReader;

use Rafni\MrtdReader\Contracts\DocumentContract;
use Rafni\MrtdReader\Documents\DocumentId;
use Rafni\MrtdReader\Documents\Passport;
use Exception;
use Rafni\MrtdReader\Documents\IdCard;

class MrtdReader
{
	/**
	 * MRTD document reader factory
	 * ICAO 9303-1 Machine Readable Travel Documents MRTDs
 	 * https://www.icao.int/publications/pages/publication.aspx?docnum=9303
	 
	 * @param string $documentType
	 * @param array $mrzLines
	 * @throws Exception
	 * @return DocumentContract
	 */
	public static function make(string $documentType, array $mrzLines) : DocumentContract
	{
		switch ($documentType) {
			case 'ID_CARD': return new IdCard($mrzLines);
			case 'PASSPORT': return new Passport($mrzLines);
			default: throw new Exception('Document not available');
		}
	}
}