<?php

namespace App\Form;

use App\Entity\Bulletin;
use App\Entity\Certification;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CertificationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type')
            ->add('issuedAt', null, [
                'widget' => 'single_text',
            ])
            ->add('verificationCode')
            ->add('pdfPath')
            ->add('status')
            ->add('student', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'id',
            ])
            ->add('bulletin', EntityType::class, [
                'class' => Bulletin::class,
                'choice_label' => 'id',
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
