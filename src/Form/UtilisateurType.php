<?php

namespace App\Form;

use App\Entity\Profession;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class UtilisateurType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('prenom', TextType::class, [
                'constraints' => [new NotBlank(), new Length(min: 2, max: 100)],
            ])
            ->add('nom', TextType::class, [
                'constraints' => [new NotBlank(), new Length(min: 2, max: 100)],
            ])
            ->add('email', EmailType::class, [
                'constraints' => [new NotBlank(), new Email()],
            ])
            ->add('profession', EntityType::class, [
                'class' => Profession::class,
                'choice_label' => 'nom',
                'placeholder' => '-- Choisir --',
                'constraints' => [new NotBlank()],
            ])
            ->add('niveau', ChoiceType::class, [
                // mapped: false → champ virtuel non lié à User::$roles
                // Le contrôleur lit $form->get('niveau')->getData() et appelle setRoles([$niveau])
                // ROLE_USER n'est pas stocké en BDD (ajouté dynamiquement dans getRoles())
                'mapped' => false,
                'label' => 'Rôle',
                'choices' => [
                    'Utilisateur' => 'ROLE_USER',
                    'Modérateur' => 'ROLE_MODERATEUR',
                    'Administrateur' => 'ROLE_ADMIN',
                ],
                'constraints' => [new NotBlank()],
            ])
            ->add('isVerified', CheckboxType::class, [
                // required: false indispensable pour les checkboxes — sinon Symfony considère
                // qu'une case non cochée est une erreur de validation
                'required' => false,
                'label' => 'Compte activé',
            ])
            ->add('plainPassword', PasswordType::class, [
                // mapped: false et required: false → le champ est optionnel en édition
                // Si vide, le contrôleur ne met pas à jour le mot de passe
                'mapped' => false,
                'required' => false,
                'label' => 'Nouveau mot de passe',
                'attr' => ['placeholder' => 'Laisser vide pour ne pas modifier'],
                'constraints' => [new Length(min: 6, max: 4096)],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
