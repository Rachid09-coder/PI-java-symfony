<?php

namespace App\Form;

use App\Entity\ReportCardLine;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReportCardLineType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('moduleName', TextType::class, [
                'label' => 'Matière',
                'attr' => ['placeholder' => 'Nom de la matière', 'class' => 'form-control'],
            ])
            ->add('noteCC', NumberType::class, [
                'label' => 'CC (10%)',
                'required' => false,
                'scale' => 2,
                'attr' => ['step' => '0.25', 'min' => '0', 'max' => '20', 'class' => 'form-control note-input', 'placeholder' => 'Note CC'],
            ])
            ->add('noteDS', NumberType::class, [
                'label' => 'DS (20%)',
                'required' => false,
                'scale' => 2,
                'attr' => ['step' => '0.25', 'min' => '0', 'max' => '20', 'class' => 'form-control note-input', 'placeholder' => 'Note DS'],
            ])
            ->add('noteExam', NumberType::class, [
                'label' => 'Exam (70%)',
                'required' => false,
                'scale' => 2,
                'attr' => ['step' => '0.25', 'min' => '0', 'max' => '20', 'class' => 'form-control note-input', 'placeholder' => 'Note Exam'],
            ])
            ->add('note', NumberType::class, [
                'label' => 'Note Finale',
                'scale' => 2,
                'attr' => ['step' => '0.01', 'min' => '0', 'max' => '20', 'class' => 'form-control note-finale', 'readonly' => true],
            ])
            ->add('coefficient', NumberType::class, [
                'label' => 'Coefficient',
                'scale' => 1,
                'attr' => ['step' => '0.5', 'min' => '0.5', 'class' => 'form-control'],
            ])
            ->add('teacherComment', TextareaType::class, [
                'label' => 'Commentaire',
                'required' => false,
                'attr' => ['rows' => 2, 'placeholder' => 'Commentaire du professeur (optionnel)', 'class' => 'form-control'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ReportCardLine::class,
        ]);
    }
}
