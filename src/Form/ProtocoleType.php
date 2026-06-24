<?php

namespace App\Form;

use App\Entity\Protocole;
use App\Entity\Theme;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Vich\UploaderBundle\Form\Type\VichFileType;
use Vich\UploaderBundle\Form\Type\VichImageType;

class ProtocoleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'constraints' => [
                    new NotBlank(),
                    new Length(min: 2, max: 255),
                ],
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'attr' => ['rows' => 4],
            ])
            ->add('theme', EntityType::class, [
                'class' => Theme::class,
                'choice_label' => 'nom',
                'placeholder' => '-- Choisir un thème --',
                'constraints' => [new NotBlank()],
            ])
            ->add('pdfFile', VichFileType::class, [
                'label' => 'Fichier PDF',
                'required' => false,
                'allow_delete' => true,
                'download_uri' => false,
                'constraints' => [
                    new File(
                        mimeTypes: ['application/pdf'],
                        mimeTypesMessage: 'Veuillez uploader un fichier PDF valide.',
                        maxSize: '10M',
                    ),
                ],
            ])
            ->add('imageFile', VichImageType::class, [
                'label' => 'Image de couverture',
                'required' => false,
                'allow_delete' => true,
                'download_uri' => false,
                'image_uri' => false,
                'constraints' => [
                    new File(
                        mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
                        mimeTypesMessage: 'Formats acceptés : JPG, PNG, WebP.',
                        maxSize: '20M',
                    ),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Protocole::class,
        ]);
    }
}
