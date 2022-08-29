<?php

namespace Rafni\MrtdReader\Tests;

use DateTime;
use Exception;
use PHPUnit\Framework\TestCase;
use Rafni\MrtdReader\Documents\IdCard;
use stdClass;

class IdCardTest extends TestCase
{
    /**
     * @covers IdCard
     */
    public function testValidDocument()
    {
        $td1 = new IdCard([
            'IDESPABC123456089000000Z<<<<<<', 
            '8010100F0010126ESP<<<<<<<<<<<2', 
            'APELLIDO<SEGUNDOAPELLIDO<<NOMB'
        ]);

        $this->assertInstanceOf(IdCard::class, $td1);
        $this->assertEquals('ID', $td1->documentType);
        $this->assertEquals('ABC123456', $td1->documentNumber);
        $this->assertEquals('89000000Z', $td1->personId);
        $this->assertInstanceOf(stdClass::class, $td1->data());
        $this->assertInstanceOf(stdClass::class, $td1->issueCode);
        $this->assertInstanceOf(stdClass::class, $td1->nationality);
        $this->assertEquals('ESP', $td1->issueCode->alpha3);
        $this->assertEquals(DateTime::createFromFormat('ymd', '801010')->format('Y-m-d'), $td1->birthdayDate->format('Y-m-d'));
        $this->assertEquals('F', $td1->sex);
        $this->assertEquals(DateTime::createFromFormat('ymd', '001012')->format('Y-m-d'), $td1->expirationDate->format('Y-m-d'));
        $this->assertEquals('ESP', $td1->nationality->alpha3);
        $this->assertEquals('NOMB', $td1->name);
        $this->assertEquals('APELLIDO SEGUNDOAPELLIDO', $td1->surname);

        return $td1;
    }

    /**
     * @covers IdCard
     * @depends testValidDocument
     */
    public function testAttributesAndMethodsAreEquals($doc)
    {
        $this->assertEquals($doc->documentType(), $doc->documentType);
        $this->assertEquals($doc->issueCode(), $doc->issueCode);
        $this->assertEquals($doc->documentNumber(), $doc->documentNumber);
        $this->assertEquals($doc->personId(), $doc->personId);
        $this->assertEquals($doc->optionalData1(), $doc->optionalData1);
        $this->assertEquals($doc->birthdayDate(), $doc->birthdayDate);
        $this->assertEquals($doc->sex(), $doc->sex);
        $this->assertEquals($doc->expirationDate(), $doc->expirationDate);
        $this->assertEquals($doc->nationality(), $doc->nationality);
        $this->assertEquals($doc->optionalData2(), $doc->optionalData2);
        $this->assertEquals($doc->name(), $doc->name);
        $this->assertEquals($doc->surname(), $doc->surname);
    }

    /**
     * @covers IdCard
     * @depends testValidDocument
     */
    public function testVerifyControlDigitsDocument($doc)
    {
        $verificationResult = $doc->verify();
        $this->assertInstanceOf(stdClass::class, $verificationResult);
        $this->assertEquals('ABC123456', $verificationResult->documentNumber->value);
        $this->assertTrue($verificationResult->documentNumber->verified);
        $this->assertEquals('801010', $verificationResult->birthdayDate->value);
        $this->assertTrue($verificationResult->birthdayDate->verified);
        $this->assertEquals('001012', $verificationResult->expirationDate->value);
        $this->assertTrue($verificationResult->expirationDate->verified);
        $this->assertEquals('ABC123456089000000Z<<<<<<80101000010126<<<<<<<<<<<', $verificationResult->compound->value);
        $this->assertTrue($verificationResult->compound->verified);
    }

    /**
     * @covers IdCard
     */
    public function testInvalidDocumentTypeMrzCodeException()
    {
        $this->expectException(Exception::class);
        new IdCard(['PDESPABC123456089000000Z<<<<<<', '8010100F0010126ESP<<<<<<<<<<<2', 'APELLIDO<SEGUNDOAPELLIDO<<NOMB']);
    }

    /**
     * @covers IdCard
     */
    public function testInvalidMrzCodeLine1LessLenghtException()
    {
        $this->expectException(Exception::class);
        new IdCard(['IDESPABC123456089000000Z<<<<<', '8010100F0010126ESP<<<<<<<<<<<2', 'APELLIDO<SEGUNDOAPELLIDO<<NOMB']);
    }

    /**
     * @covers IdCard
     */
    public function testInvalidMrzCodeLine1MoreLenghtException()
    {
        $this->expectException(Exception::class);
        new IdCard(['IDESPABC123456089000000Z<<<<<<<', '8010100F0010126ESP<<<<<<<<<<<2', 'APELLIDO<SEGUNDOAPELLIDO<<NOMB']);
    }

    /**
     * @covers IdCard
     */
    public function testInvalidMrzCodeLine2LessLenghtException()
    {
        $this->expectException(Exception::class);
        new IdCard(['IDESPABC123456089000000Z<<<<<<', '8010100F0010126ESP<<<<<<<<<<<', 'APELLIDO<SEGUNDOAPELLIDO<<NOMB']);
    }

    /**
     * @covers IdCard
     */
    public function testInvalidMrzCodeLine2MoreLenghtException()
    {
        $this->expectException(Exception::class);
        new IdCard(['IDESPABC123456089000000Z<<<<<<', '8010100F0010126ESP<<<<<<<<<<<<<', 'APELLIDO<SEGUNDOAPELLIDO<<NOMB']);
    }

    /**
     * @covers IdCard
     */
    public function testInvalidMrzCodeLine3LessLenghtException()
    {
        $this->expectException(Exception::class);
        new IdCard(['IDESPABC123456089000000Z<<<<<<', '8010100F0010126ESP<<<<<<<<<<<2', 'APELLIDO<SEGUNDOAPELLIDO<<NOM']);
    }

    /**
     * @covers IdCard
     */
    public function testInvalidMrzCodeLine3MoreLenghtException()
    {
        $this->expectException(Exception::class);
        new IdCard(['IDESPABC123456089000000Z<<<<<<', '8010100F0010126ESP<<<<<<<<<<<2', 'APELLIDO<SEGUNDOAPELLIDO<<NOMBR']);
    }
}
