<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'mapped' => false,
                'label' => 'Prénom',
                'constraints' => [new NotBlank(), new Length(max: 120)],
                'attr' => [
                    'autocomplete' => 'given-name',
                ],
            ])
            ->add('lastName', TextType::class, [
                'mapped' => false,
                'label' => 'Nom',
                'constraints' => [new NotBlank(), new Length(max: 120)],
                'attr' => [
                    'autocomplete' => 'family-name',
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'constraints' => [new NotBlank(), new Email(), new Length(max: 180)],
                'attr' => [
                    'autocomplete' => 'email',
                    'inputmode' => 'email',
                    'spellcheck' => 'false',
                ],
            ])
            ->add('phone', TextType::class, [
                'label' => 'Telephone',
                'required' => false,
                'attr' => [
                    'autocomplete' => 'tel',
                    'inputmode' => 'tel',
                    'placeholder' => 'Ex. +33 6 12 34 56 78',
                ],
            ])
            ->add('city', TextType::class, [
                'label' => 'Ville',
                'required' => false,
                'attr' => [
                    'autocomplete' => 'address-level2',
                    'placeholder' => 'Commencez par votre ville',
                    'data-address-role' => 'city',
                ],
            ])
            ->add('defaultAddress', TextType::class, [
                'label' => 'Rue et numéro',
                'required' => false,
                'attr' => [
                    'autocomplete' => 'street-address',
                    'placeholder' => 'Puis choisissez votre rue',
                    'data-address-role' => 'street',
                ],
            ])
            ->add('postalCode', TextType::class, [
                'label' => 'Code postal',
                'required' => false,
                'attr' => [
                    'autocomplete' => 'postal-code',
                    'inputmode' => 'numeric',
                    'placeholder' => 'Code postal',
                    'data-address-role' => 'postal-code',
                ],
            ])
            ->add('marketingOptIn', CheckboxType::class, [
                'label' => 'Je rejoins la Société secrète et j’active 10 % sur ma première commande',
                'required' => false,
                'help' => 'Vous pourrez vous désinscrire à tout moment depuis votre compte. Le consentement marketing reste séparé des données de commande.',
            ])
            ->add('website', TextType::class, [
                'mapped' => false,
                'required' => false,
                'label' => false,
                'attr' => [
                    'autocomplete' => 'off',
                    'tabindex' => '-1',
                    'class' => 'field-honeypot',
                    'aria-hidden' => 'true',
                ],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => true,
                'first_options' => [
                    'label' => 'Mot de passe',
                    'attr' => [
                        'autocomplete' => 'new-password',
                        'minlength' => '12',
                    ],
                ],
                'second_options' => [
                    'label' => 'Confirmation',
                    'attr' => [
                        'autocomplete' => 'new-password',
                        'minlength' => '12',
                    ],
                ],
                'invalid_message' => 'Les deux mots de passe doivent être identiques.',
                'constraints' => [new NotBlank(), new Length(min: 12, max: 4096)],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
