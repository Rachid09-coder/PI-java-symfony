<?php

namespace App\Form;

use App\Entity\Course;
use App\Entity\Module;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CourseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $inputAttr = ['style' => 'width: 100%; padding: 14px; border: 1px solid #E2E8F0; border-radius: 12px; outline: none;'];
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre du cours',
                'attr' => array_merge($inputAttr, ['placeholder' => 'Ex: Masterclass Symfony 6']),
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description détaillée',
                'required' => false,
                'attr' => array_merge($inputAttr, ['rows' => 6, 'placeholder' => 'Décrivez le contenu du cours...']),
            ])
            ->add('price', MoneyType::class, [
                'label' => 'Prix (€)',
                'currency' => 'EUR',
                'divisor' => 1,
                'attr' => $inputAttr,
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Brouillon' => 'DRAFT',
                    'Publié' => 'PUBLISHED',
                ],
                'attr' => $inputAttr,
            ])
            ->add('modules', EntityType::class, [
                'class' => Module::class,
                'choice_label' => 'title',
                'label' => 'Modules',
                'multiple' => true,
                'expanded' => false,
                'required' => false,
                'attr' => $inputAttr,
            ])
            ->add('thumbnail', \Symfony\Component\Form\Extension\Core\Type\FileType::class, [
                'label' => 'Miniature (Image)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new \Symfony\Component\Validator\Constraints\File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPEG, PNG, WEBP)',
                    ])
                ],
                'attr' => array_merge($inputAttr, ['accept' => 'image/*']),
            ])
            ->add('pdf', \Symfony\Component\Form\Extension\Core\Type\FileType::class, [
                'label' => 'Document PDF',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new \Symfony\Component\Validator\Constraints\File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'application/pdf',
                            'application/x-pdf',
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger un fichier PDF valide',
                    ])
                ],
                'attr' => array_merge($inputAttr, ['accept' => '.pdf']),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Course::class,
        ]);
    }
}
