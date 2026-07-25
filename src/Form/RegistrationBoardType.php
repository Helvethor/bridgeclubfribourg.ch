<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

use App\Entity\RegistrationBoard;

class RegistrationBoardType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date', DateType::class, [
                'label' => 'Date'
            ])
            ->add('time', TimeType::class, [
                'label' => 'Heure'
            ])
            ->add('searchOnly', ChoiceType::class, [
                'label' => 'Type',
                'choices' => [
                    'Recherche de partenaires' => false,
                    'Inscriptions' => true
                ]
            ])
            ->add('repeat', IntegerType::class, [
                'label' => 'Répéter pendant x semaines',
                'data' => 1,
                'mapped' => false
            ])
            ->add('send', SubmitType::class, [
                'label' => 'Enregistrer'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RegistrationBoard::class
        ]);
    }
}
