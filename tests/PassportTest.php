<?php

namespace Rafni\MrtdReader\Tests;

use DateTime;
use Exception;
use PHPUnit\Framework\TestCase;
use Rafni\MrtdReader\Documents\Passport;
use stdClass;

class PassportTest extends TestCase
{
    /**
     * @covers Passport
     */
    public function testValidDocument()
    {
        $td1 = new Passport([
            'P<UTOERIKSSON<<ANNA<MARIA<<<<<<<<<<<<<<<<<<<', 
            'L898902C36UTO7408122F1204159ZE184226B<<<<<10', 
        ]);

        $this->assertInstanceOf(Passport::class, $td1);
        $this->assertEquals('P', $td1->documentType);
        $this->assertInstanceOf(stdClass::class, $td1->issueCode);
        $this->assertEquals('UTO', $td1->issueCode->alpha3);
        $this->assertEquals('ANNA MARIA', $td1->name);
        $this->assertEquals('ERIKSSON', $td1->surname);

        $this->assertEquals('L898902C3', $td1->documentNumber);
        $this->assertInstanceOf(stdClass::class, $td1->nationality);
        $this->assertEquals('UTO', $td1->nationality->alpha3);
        $this->assertEquals(DateTime::createFromFormat('ymd', '740812')->format('Y-m-d'), $td1->birthdayDate->format('Y-m-d'));
        $this->assertEquals('F', $td1->sex);
        $this->assertEquals(DateTime::createFromFormat('ymd', '120415')->format('Y-m-d'), $td1->expirationDate->format('Y-m-d'));
        $this->assertEquals('ZE184226B', $td1->personId);
        $this->assertInstanceOf(stdClass::class, $td1->data());

        return $td1;
    }

    /**
     * @covers Passport
     * @depends testValidDocument
     */
    public function testAttributesAndMethodsAreEquals($doc)
    {
        $this->assertEquals($doc->documentType(), $doc->documentType);
        $this->assertEquals($doc->issueCode(), $doc->issueCode);
        $this->assertEquals($doc->documentNumber(), $doc->documentNumber);
        $this->assertEquals($doc->personId(), $doc->personId);
        $this->assertEquals($doc->birthdayDate(), $doc->birthdayDate);
        $this->assertEquals($doc->sex(), $doc->sex);
        $this->assertEquals($doc->expirationDate(), $doc->expirationDate);
        $this->assertEquals($doc->nationality(), $doc->nationality);
        $this->assertEquals($doc->name(), $doc->name);
        $this->assertEquals($doc->surname(), $doc->surname);
    }

    /**
     * @covers Passport
     * @depends testValidDocument
     */
    public function testVerifyControlDigitsDocument($doc)
    {
        $verificationResult = $doc->verify();
        $this->assertInstanceOf(stdClass::class, $verificationResult);
        $this->assertEquals('L898902C3', $verificationResult->documentNumber->value);
        $this->assertTrue($verificationResult->documentNumber->verified);
        $this->assertEquals('740812', $verificationResult->birthdayDate->value);
        $this->assertTrue($verificationResult->birthdayDate->verified);
        $this->assertEquals('120415', $verificationResult->expirationDate->value);
        $this->assertTrue($verificationResult->expirationDate->verified);
        $this->assertEquals('L898902C3674081221204159ZE184226B<<<<<1', $verificationResult->compound->value);
        $this->assertTrue($verificationResult->compound->verified);
    }

    /**
     * @covers Passport
     */
    public function testInvalidDocumentTypeMrzCodeException()
    {
        $this->expectException(Exception::class);
        new Passport(['I<UTOERIKSSON<<ANNA<MARIA<<<<<<<<<<<<<<<<<<<', 'L898902C36UTO7408122F1204159ZE184226B<<<<<10']);
    }

    /**
     * @covers Passport
     */
    public function testInvalidMrzCodeLine1LessLenghtException()
    {
        $this->expectException(Exception::class);
        new Passport(['P<UTOERIKSSON<<ANNA<MARIA<<<<<<<<<<<<<<<<<<', 'L898902C36UTO7408122F1204159ZE184226B<<<<<10']);
    }

    /**
     * @covers Passport
     */
    public function testInvalidMrzCodeLine1MoreLenghtException()
    {
        $this->expectException(Exception::class);
        new Passport(['P<UTOERIKSSON<<ANNA<MARIA<<<<<<<<<<<<<<<<<<<<', 'L898902C36UTO7408122F1204159ZE184226B<<<<<10']);
    }

    /**
     * @covers Passport
     */
    public function testInvalidMrzCodeLine2LessLenghtException()
    {
        $this->expectException(Exception::class);
        new Passport(['P<UTOERIKSSON<<ANNA<MARIA<<<<<<<<<<<<<<<<<<<', 'L898902C36UTO7408122F1204159ZE184226B<<<<10']);
    }

    /**
     * @covers Passport
     */
    public function testInvalidMrzCodeLine2MoreLenghtException()
    {
        $this->expectException(Exception::class);
        new Passport(['P<UTOERIKSSON<<ANNA<MARIA<<<<<<<<<<<<<<<<<<<', 'L898902C36UTO7408122F1204159ZE184226B<<<<<<10']);
    }
}
