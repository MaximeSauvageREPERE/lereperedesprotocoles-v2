<?php

namespace App\Tests\Unit\Security;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Validation;

/**
 * Tests unitaires standalone de la validation d'upload.
 * Utilisent Validation::createValidator() sans kernel Symfony — aucune BDD, aucun service requis.
 * Vérifient que la contrainte File rejette les fichiers malveillants déguisés.
 */
class UploadValidationTest extends TestCase
{
    public function testPhpFileDisguisedAsPdfIsRejected(): void
    {
        // Crée un vrai fichier PHP sur le disque (magic bytes du texte, pas d'un PDF)
        $fakeFile = tempnam(sys_get_temp_dir(), 'test_upload_');
        file_put_contents($fakeFile, '<?php phpinfo(); ?>');

        // UploadedFile($path, $originalName, $mimeType, $error, $test)
        // $test=true : ignore les restrictions PHP sur les fichiers uploadés (mode test)
        // Le navigateur peut mentir sur $mimeType ('application/pdf') — d'où la validation côté serveur
        $uploaded = new UploadedFile($fakeFile, 'document.pdf', 'application/pdf', null, true);

        $validator = Validation::createValidator();
        // mimeTypes utilise finfo/magic bytes pour détecter le vrai type, pas le $mimeType déclaré
        $violations = $validator->validate($uploaded, [
            new File(
                mimeTypes: ['application/pdf'],
                mimeTypesMessage: 'Veuillez uploader un fichier PDF valide.',
            ),
        ]);

        $this->assertGreaterThan(0, $violations->count(), 'Un fichier PHP renommé en .pdf doit être rejeté.');

        unlink($fakeFile);
    }

    public function testPhpFileDisguisedAsImageIsRejected(): void
    {
        $fakeFile = tempnam(sys_get_temp_dir(), 'test_upload_');
        file_put_contents($fakeFile, '<?php phpinfo(); ?>');

        $uploaded = new UploadedFile($fakeFile, 'photo.jpg', 'image/jpeg', null, true);

        $validator = Validation::createValidator();
        $violations = $validator->validate($uploaded, [
            new File(
                mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
                mimeTypesMessage: 'Formats acceptés : JPG, PNG, WebP.',
            ),
        ]);

        $this->assertGreaterThan(0, $violations->count(), 'Un fichier PHP renommé en .jpg doit être rejeté.');

        unlink($fakeFile);
    }

    public function testFileWithWrongExtensionIsRejected(): void
    {
        // Seconde ligne de défense : extensions vérifie le nom du fichier original
        // même si le magic bytes correspondait au bon type MIME
        $fakeFile = tempnam(sys_get_temp_dir(), 'test_upload_');
        file_put_contents($fakeFile, '<?php phpinfo(); ?>');

        $uploaded = new UploadedFile($fakeFile, 'document.exe', 'application/pdf', null, true);

        $validator = Validation::createValidator();
        $violations = $validator->validate($uploaded, [
            new File(
                extensions: ['pdf'],
                extensionsMessage: 'Seul le format PDF est accepté.',
            ),
        ]);

        $this->assertGreaterThan(0, $violations->count(), 'Une extension .exe doit être rejetée.');

        unlink($fakeFile);
    }
}
