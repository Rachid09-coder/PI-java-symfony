<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class ForumThreadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre du sujet',
                'attr' => ['placeholder' => 'Donnez un titre clair à votre sujet', 'maxlength' => 255],
                'constraints' => [new NotBlank(['message' => 'Le titre est obligatoire'])],
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Premier message',
                'attr' => ['placeholder' => 'Décrivez votre question ou sujet de discussion...', 'rows' => 5],
                'constraints' => [new NotBlank(['message' => 'Le message ne peut pas être vide'])],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'forum_thread',
        ]);
    }
}
