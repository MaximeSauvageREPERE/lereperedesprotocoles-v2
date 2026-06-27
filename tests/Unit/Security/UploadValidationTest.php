<?php

namespace App\Tests\Unit\Security;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Validation;

class UploadValidationTest extends TestCase
{
    public function testPhpFileDisguisedAsPdfIsRejected(): void
    {
        $fakeFile = tempnam(sys_get_temp_dir(), 'test_upload_');
        file_put_contents($fakeFile, '<?php phpinfo(); ?>');

        $uploaded = new UploadedFile($fakeFile, 'document.pdf', 'application/pdf', null, true);

        $validator = Validation::createValidator();
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
