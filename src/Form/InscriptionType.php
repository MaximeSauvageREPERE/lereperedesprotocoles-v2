<?php

namespace App\Form;

use App\Entity\DemandeInscription;
use App\Entity\Profession;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class InscriptionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'constraints' => [new NotBlank(), new Length(max: 100)],
            ])
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'constraints' => [new NotBlank(), new Length(max: 100)],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Adresse email professionnelle',
                'constraints' => [new NotBlank(), new Email()],
            ])
            ->add('profession', EntityType::class, [
                'class' => Profession::class,
                // choice_label utilise __toString() de Profession pour afficher le nom dans le <select>
                'choice_label' => 'nom',
                'label' => 'Profession',
                'placeholder' => 'Sélectionnez votre profession',
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                // mapped: false → le champ n'est pas lié à DemandeInscription (pas de setter plainPassword)
                // Le contrôleur lit $form->get('plainPassword')->getData() pour hacher manuellement
                'mapped' => false,
                'first_options' => ['label' => 'Mot de passe'],
                'second_options' => ['label' => 'Confirmez le mot de passe'],
                'invalid_message' => 'Les mots de passe ne correspondent pas.',
                'constraints' => [
                    new NotBlank(),
                    new Length(min: 8, max: 4096, minMessage: 'Le mot de passe doit faire au moins {{ limit }} caractères.'),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        // Lie le formulaire à DemandeInscription : les champs mappés alimentent directement l'entité
        $resolver->setDefaults([
            'data_class' => DemandeInscription::class,
        ]);
    }
}
