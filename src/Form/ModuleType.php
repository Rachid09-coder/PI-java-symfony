<?php

namespace App\Form;

use App\Entity\Module;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ModuleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $inputAttr = ['style' => 'width: 100%; padding: 14px; border: 1px solid #E2E8F0; border-radius: 12px; outline: none;'];
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre du Module',
                'attr' => array_merge($inputAttr, ['placeholder' => 'Ex: Introduction aux Tableaux']),
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le titre ne peut pas être vide']),
                    new Assert\Regex([
                        'pattern' => '/\D/',
                        'message' => 'Le titre ne peut pas être composé uniquement de chiffres',
                    ]),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description du module',
                'required' => false,
                'attr' => array_merge($inputAttr, ['rows' => 5, 'placeholder' => 'Décrivez le contenu du module...']),
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
            ]);

        if ($options['include_course']) {
            $builder->add('courses', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
                'class' => \App\Entity\Course::class,
                'choice_label' => 'title',
                'multiple' => true,
                'expanded' => false,
                'label' => 'Cours associé(s)',
                'attr' => $inputAttr,
                'required' => false,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Module::class,
            'include_course' => false,
        ]);
    }
}
