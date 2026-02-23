<?php

namespace App\Form;

use App\Entity\Certification;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class CertificationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('student', EntityType::class, [
                'class' => User::class,
                'choice_label' => function(User $user) {
                    return $user->getPrenom() . ' ' . $user->getName() . ' (' . $user->getEmail() . ')';
                },
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                        ->where('u.role = :role')
                        ->setParameter('role', 'etudiant')
                        ->orderBy('u.name', 'ASC');
                },
                'label' => 'Étudiant',
                'placeholder' => 'Sélectionner un étudiant',
                'attr' => ['class' => 'form-select-premium']
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type de certification',
                'choices' => [
                    'Attestation de scolarité' => 'SCOLARITE',
                    'Certificat de réussite' => 'REUSSITE',
                    'Relevé de notes' => 'NOTES',
                    'Diplôme interne' => 'DIPLOME',
                    'Attestation de stage' => 'STAGE',
                    'Attestation de présence' => 'PRESENCE',
                ],
                'attr' => ['class' => 'form-select-premium']
            ])
            ->add('semesterChoice', TextType::class, [
                'label' => 'Période concernée',
                'mapped' => false,
                'required' => false,
                'attr' => ['id' => 'certification_semesterChoice', 'class' => 'form-select-premium']
            ])
            // Status removed - managed automatically (defaults to ACTIVE, can be revoked via button)
            ->add('verificationCode', TextType::class, [
                'label' => 'Code de vérification',
                'required' => false,
                'attr' => ['placeholder' => 'Généré automatiquement si vide', 'class' => 'form-control-premium'],
            ])
            ->add('validUntil', TextType::class, [
                'label' => 'Valide jusqu\'au',
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'class' => 'form-control-premium flatpickr-date',
                    'placeholder' => 'Sélectionner une date',
                    'autocomplete' => 'off'
                ]
            ])
            ->add('pdfFile', FileType::class, [
                'label' => 'Fichier PDF (optionnel)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => ['application/pdf'],
                        'mimeTypesMessage' => 'Veuillez uploader un fichier PDF valide',
                    ])
                ],
                'attr' => ['class' => 'form-control-premium']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Certification::class,
        ]);
    }
}
