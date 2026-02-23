<?php

namespace App\Form;

use App\Entity\Bulletin;
use App\Entity\User;
use App\Form\ReportCardLineType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
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
                'choice_label' => function(User $user) {
                    return $user->getPrenom() . ' ' . $user->getName() . ' (' . $user->getEmail() . ')';
                },
                'label' => 'Étudiant',
                'placeholder' => 'Sélectionner un étudiant',
                'required' => false,
                'attr' => ['required' => false]
            ])
            ->add('academicYear', TextType::class, [
                'label' => 'Année académique',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex: 2025/2026',
                    'required' => false
                ]
            ])
            ->add('semester', ChoiceType::class, [
                'label' => 'Semestre',
                'required' => false,
                'choices' => [
                    'Semestre 1' => 'Semestre 1',
                    'Semestre 2' => 'Semestre 2',
                    'Annuel' => 'Annuel',
                ],
                'attr' => ['required' => false]
            ])
            ->add('average', NumberType::class, [
                'label' => 'Moyenne',
                'scale' => 2,
                'required' => false,
                'attr' => [
                    'step' => '0.01',
                    'min' => '0',
                    'max' => '20',
                    'required' => false
                ]
            ])
            ->add('mention', ChoiceType::class, [
                'label' => 'Mention',
                'required' => false,
                'choices' => [
                    'Très Bien' => 'Très Bien',
                    'Bien' => 'Bien',
                    'Assez Bien' => 'Assez Bien',
                    'Passable' => 'Passable',
                    'Insuffisant' => 'Insuffisant',
                ],
                'attr' => ['required' => false]
            ])
            ->add('classRank', IntegerType::class, [
    'label' => 'Rang / Classement',
    'required' => false, // 🔥 IMPORTANT
    'attr' => [
        'min' => '1',
        'required' => false // 🔥 désactive HTML5
            ]
    ])
            // Status removed - managed automatically via workflow buttons
            ->add('reportCardLines', CollectionType::class, [
                'entry_type' => ReportCardLineType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => 'Lignes du bulletin (matières)',
                'required' => false,
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
