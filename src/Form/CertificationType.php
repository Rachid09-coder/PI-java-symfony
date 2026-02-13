<?php

namespace App\Form;

use App\Entity\Bulletin;
use App\Entity\Certification;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
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
                    'Attestation de scolarité' => 'SCOLARITE',
                    'Certificat de réussite' => 'REUSSITE',
                    'Relevé de notes' => 'NOTES',
                    'Diplôme interne' => 'DIPLOME',
                    'Attestation de stage' => 'STAGE',
                    'Attestation de présence' => 'PRESENCE',
                ],
            ])
            ->add('verificationCode', TextType::class, [
                'label' => 'Code de vérification',
                'required' => false,
                'attr' => ['placeholder' => 'Généré automatiquement si vide'],
            ])
            ->add('validUntil', DateType::class, [
                'label' => 'Valide jusqu\'au',
                'required' => false,
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
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
