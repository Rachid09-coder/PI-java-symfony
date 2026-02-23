<?php

namespace App\Form;

use App\Entity\Bulletin;
use App\Entity\Certification;
use App\Entity\User;
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
                'choice_label' => 'email',
                'label' => 'Étudiant',
                'placeholder' => 'Sélectionner un étudiant',
            ])
            ->add('bulletin', EntityType::class, [
                'class' => Bulletin::class,
                'choice_label' => function(Bulletin $bulletin) {
                    return $bulletin->getAcademicYear() . ' - ' . $bulletin->getSemester();
                },
                'label' => 'Bulletin',
                'placeholder' => 'Sélectionner un bulletin',
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type de certification',
                'choices' => [
                    'Certificat de scolarité' => 'SCOLARITE',
                    'Certificat de réussite' => 'REUSSITE',
                    'Attestation de notes' => 'NOTES',
                ],
            ])
            ->add('verificationCode', TextType::class, [
                'label' => 'Code de vérification',
                'required' => false,
                'attr' => ['placeholder' => 'Généré automatiquement si vide']
            ])
            ->add('pdfFile', FileType::class, [
                'label' => 'Fichier PDF',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => ['application/pdf'],
                        'mimeTypesMessage' => 'Veuillez uploader un fichier PDF valide',
                    ])
                ],
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Actif' => 'ACTIVE',
                    'Révoqué' => 'REVOKED',
                    'Expiré' => 'EXPIRED',
                ],
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
