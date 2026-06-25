<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Protocole;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\File;

class ProtocoleTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $protocole = new Protocole();

        $this->assertNull($protocole->getId());
        $this->assertSame('', $protocole->getTitre());
        $this->assertSame('', $protocole->getSlug());
        $this->assertNull($protocole->getDescription());
        $this->assertNull($protocole->getPdfFile());
        $this->assertNull($protocole->getPdfFilename());
        $this->assertNull($protocole->getImageFile());
        $this->assertNull($protocole->getImageFilename());
        $this->assertNull($protocole->getTheme());
        $this->assertInstanceOf(\DateTimeImmutable::class, $protocole->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $protocole->getUpdatedAt());
    }

    public function testSetPdfFileRefreshesUpdatedAt(): void
    {
        $protocole = new Protocole();
        $past = new \DateTimeImmutable('-1 day');
        $protocole->setUpdatedAt($past);

        $protocole->setPdfFile(new File('dummy.pdf', false));

        $this->assertGreaterThan($past, $protocole->getUpdatedAt());
    }

    public function testSetPdfFileNullDoesNotRefreshUpdatedAt(): void
    {
        $protocole = new Protocole();
        $past = new \DateTimeImmutable('-1 day');
        $protocole->setUpdatedAt($past);

        $protocole->setPdfFile(null);

        $this->assertEquals($past, $protocole->getUpdatedAt());
    }

    public function testSetImageFileRefreshesUpdatedAt(): void
    {
        $protocole = new Protocole();
        $past = new \DateTimeImmutable('-1 day');
        $protocole->setUpdatedAt($past);

        $protocole->setImageFile(new File('dummy.jpg', false));

        $this->assertGreaterThan($past, $protocole->getUpdatedAt());
    }

    public function testSetImageFileNullDoesNotRefreshUpdatedAt(): void
    {
        $protocole = new Protocole();
        $past = new \DateTimeImmutable('-1 day');
        $protocole->setUpdatedAt($past);

        $protocole->setImageFile(null);

        $this->assertEquals($past, $protocole->getUpdatedAt());
    }

    public function testOnPreUpdateRefreshesUpdatedAt(): void
    {
        $protocole = new Protocole();
        $past = new \DateTimeImmutable('-1 day');
        $protocole->setUpdatedAt($past);

        $protocole->onPreUpdate();

        $this->assertGreaterThan($past, $protocole->getUpdatedAt());
    }

    public function testToStringReturnsTitre(): void
    {
        $protocole = new Protocole();
        $protocole->setTitre('Protocole Hypertension');

        $this->assertSame('Protocole Hypertension', (string) $protocole);
    }
}
