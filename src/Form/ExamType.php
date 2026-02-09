<?php

namespace App\Form;

use App\Entity\Exam;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ExamType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le titre ne peut pas être vide']),
                    new Assert\Regex([
                        'pattern' => '/\D/',
                        'message' => 'Le titre ne peut pas être composé uniquement de chiffres',
                    ]),
                ],
            ])
            ->add('description', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class)
            ->add('type', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                'choices' => [
                    'QCM' => 'QCM',
                    'PDF' => 'pdf',
                    'Devoir' => 'Devoir',
                    'Projet' => 'Projet',
                    'Autre' => 'Autre',
                ],
            ])
            ->add('filePath', \Symfony\Component\Form\Extension\Core\Type\FileType::class, [
                'label' => 'Fichier (PDF)',
                'mapped' => false,
                'required' => false,
            ])
            ->add('externalLink')
            ->add('duration')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Exam::class,
        ]);
    }
}
