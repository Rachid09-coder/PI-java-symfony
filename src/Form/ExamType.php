<?php

namespace App\Form;

use App\Entity\Exam;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ExamType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title')

            ->add('description', TextareaType::class, [
                'required' => false
            ])

            ->add('duration')

            ->add('type', ChoiceType::class, [
                'choices' => [
                    'PDF (Sujet écrit)' => 'pdf',
                    'Lien Quiz' => 'link',
                    'Fichier Word' => 'word'
                ]
            ])

            ->add('externalLink', UrlType::class, [
                'required' => false
            ])

            ->add('filePath', FileType::class, [
                'label' => 'Sujet de l\'examen (PDF ou Word)',
                'mapped' => false,      // VERY IMPORTANT
                'required' => false,

                // THIS IS THE FIX
                'constraints' => [
                    new File([
                        'maxSize' => '10M',
                        'mimeTypes' => [
                            'application/pdf',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                        ],
                        'mimeTypesMessage' => 'Veuillez uploader un PDF ou un fichier Word valide',
                    ])
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Exam::class,
        ]);
    }
}
