<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class AddUserForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName',TextType::class,['constraints' => [
                new NotBlank(['message' => 'Veuillez entrer votre speciality']),
            ],])
            ->add('lastName',textType::class,['constraints' => [
                new NotBlank(['message' => 'Veuillez entrer votre speciality']),
            ],])
            ->add('email',EmailType::class)
            ->add('password',PasswordType::class,['mapped' => false,'constraints' => [
                new Length([
                    'min' => 6,
                    'minMessage' => 'Votre mot de passe doit comporter au moins {{ limit }} caractères',
                    'max' => 4096,
                ])
            ]])
            ->add('specialty',TextType::class,['constraints' => [
                new NotBlank(['message' => 'Veuillez entrer votre speciality']),
            ],])

        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
