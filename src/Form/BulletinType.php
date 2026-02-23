<?php

namespace App\Form;

use App\Entity\Bulletin;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BulletinType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('student', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'email',
                'label' => 'Étudiant',
                'placeholder' => 'Sélectionner un étudiant',
            ])
            ->add('academicYear', TextType::class, [
                'label' => 'Année académique',
                'attr' => ['placeholder' => 'Ex: 2025-2026']
            ])
            ->add('semester', ChoiceType::class, [
                'label' => 'Semestre',
                'choices' => [
                    'Semestre 1' => 'Semestre 1',
                    'Semestre 2' => 'Semestre 2',
                ],
            ])
            ->add('average', NumberType::class, [
                'label' => 'Moyenne',
                'scale' => 2,
                'attr' => ['step' => '0.01', 'min' => '0', 'max' => '20']
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Brouillon' => 'DRAFT',
                    'Validé' => 'VALIDATED',
                    'Publié' => 'PUBLISHED',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Bulletin::class,
        ]);
    }
}
