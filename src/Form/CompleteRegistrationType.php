<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\OptionsResolver\OptionsResolver;

use App\Entity\Registration;

class CompleteRegistrationType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('rightPartner', TextType::class, [
                'label' => 'Nom, Prénom'
            ])
            ->add('idx', ChoiceType::class, [
                'label' => 'Partenaire',
                'choices' => $options['registrations']
            ])
            ->add('send', SubmitType::class, [
                'label' => 'S\'inscrire'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Registration::class,
            'registrations' => []
        ]);
    }
}
