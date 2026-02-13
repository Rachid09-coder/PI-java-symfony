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
            ])
            ->add('academicYear', TextType::class, [
                'label' => 'Année académique',
                'attr' => ['placeholder' => 'Ex: 2025/2026']
            ])
            ->add('semester', ChoiceType::class, [
                'label' => 'Semestre',
                'choices' => [
                    'Semestre 1' => 'Semestre 1',
                    'Semestre 2' => 'Semestre 2',
                    'Annuel' => 'Annuel',
                ],
            ])
            ->add('average', NumberType::class, [
                'label' => 'Moyenne',
                'scale' => 2,
                'required' => false,
                'attr' => ['step' => '0.01', 'min' => '0', 'max' => '20']
            ])
            ->add('mention', ChoiceType::class, [
                'label' => 'Mention',
                'required' => true,
                'choices' => [
                    'Très Bien' => 'Très Bien',
                    'Bien' => 'Bien',
                    'Assez Bien' => 'Assez Bien',
                    'Passable' => 'Passable',
                    'Insuffisant' => 'Insuffisant',
                ],
            ])
            ->add('classRank', IntegerType::class, [
                'label' => 'Rang / Classement',
                'required' => true,
                'attr' => ['min' => '1']
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Brouillon' => 'Brouillon',
                    'Vérifié' => 'Vérifié',
                    'Validé' => 'Validé',
                    'Publié' => 'Publié',
                ],
            ])
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
