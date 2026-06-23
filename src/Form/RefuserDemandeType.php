<?php

namespace App\Form;

use App\Entity\DemandeInscription;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class RefuserDemandeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('motifRejet', TextareaType::class, [
            'label' => 'Motif du refus',
            'attr' => ['rows' => 4, 'placeholder' => 'Expliquez pourquoi la demande est refusée...'],
            'constraints' => [new NotBlank(message: 'Veuillez indiquer un motif de refus.')],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => DemandeInscription::class]);
    }
}
