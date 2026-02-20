<?php

namespace App\Form;

use App\Entity\Exam;
use App\Entity\Course;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ExamType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le titre ne peut pas être vide']),
                    new Assert\Regex([
                        'pattern' => '/\D/',
                        'message' => 'Le titre ne peut pas être composé uniquement de chiffres',
                    ]),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type d\'examen',
                'choices' => [
                    'QCM' => 'QCM',
                    'PDF' => 'pdf',
                    'Devoir' => 'Devoir',
                    'Projet' => 'Projet',
                    'Autre' => 'Autre',
                ],
            ])
            ->add('moduleName', TextType::class, [
                'label' => 'Nom du module/matière',
                'required' => false,
                'attr' => ['placeholder' => 'Ex: Mathématiques, Physique, Informatique...'],
            ])
            ->add('gradeCategory', ChoiceType::class, [
                'label' => 'Catégorie de note',
                'required' => false,
                'choices' => [
                    '-- Sélectionner --' => null,
                    'CC (Quiz/QCM) - 10%' => 'cc',
                    'DS - 20%' => 'ds',
                    'Examen Final - 70%' => 'exam',
                ],
                'help' => 'CC: Contrôle Continu (Quiz/QCM), DS: Devoir Surveillé, Exam: Examen Final',
            ])
            ->add('academicYear', TextType::class, [
                'label' => 'Année académique',
                'required' => false,
                'attr' => ['placeholder' => 'Ex: 2024-2025'],
            ])
            ->add('semester', ChoiceType::class, [
                'label' => 'Semestre',
                'required' => false,
                'choices' => [
                    '-- Sélectionner --' => null,
                    'Semestre 1' => 1,
                    'Semestre 2' => 2,
                ],
            ])
            ->add('coefficient', NumberType::class, [
                'label' => 'Coefficient du module',
                'required' => false,
                'attr' => ['placeholder' => 'Ex: 1, 1.5, 2...', 'step' => '0.5', 'min' => '0.5'],
                'help' => 'Si un cours est sélectionné, le coefficient du cours sera utilisé',
            ])
            ->add('course', EntityType::class, [
                'class' => Course::class,
                'choice_label' => function (Course $course) {
                    $coef = $course->getCoefficient() ? ' (Coef: ' . $course->getCoefficient() . ')' : '';
                    return $course->getTitle() . $coef;
                },
                'label' => 'Cours associé',
                'required' => false,
                'placeholder' => '-- Sélectionner un cours --',
                'help' => 'Le coefficient et le nom du module seront automatiquement récupérés du cours',
            ])
            ->add('filePath', FileType::class, [
                'label' => 'Fichier (PDF)',
                'mapped' => false,
                'required' => false,
            ])
            ->add('externalLink', TextType::class, [
                'label' => 'Lien externe',
                'required' => false,
            ])
            ->add('duration', IntegerType::class, [
                'label' => 'Durée (minutes)',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Exam::class,
        ]);
    }
}
