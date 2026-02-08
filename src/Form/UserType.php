<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('prenom')
            ->add('name')
            ->add('email', EmailType::class, [
                'attr' => ['autocomplete' => 'email'],
            ])
            ->add('numtel')
            ->add('role', ChoiceType::class, [
                'choices' => [
                    'Élève' => 'etudiant',
                    'Enseignant / Admin' => 'professeur',
                ],
                'placeholder' => 'Choisir un rôle',
                'required' => true,
            ])
            ->add('password', PasswordType::class, [
                'attr' => ['autocomplete' => 'new-password'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
