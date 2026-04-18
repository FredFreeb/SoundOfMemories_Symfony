<?php

namespace App\Form;

use App\Entity\User;
use App\Service\BoxtalShippingService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationType extends AbstractType
{
    public function __construct(
        private readonly BoxtalShippingService $boxtalShipping,
    ) {
    }

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
            ->add('countryCode', ChoiceType::class, [
                'label' => 'Pays',
                'required' => false,
                'choices' => $this->boxtalShipping->getDestinationCountryChoices(),
                'placeholder' => 'Choisir le pays',
                'attr' => [
                    'data-address-role' => 'country',
                ],
            ])
            ->add('postalCode', TextType::class, [
                'label' => 'Code postal',
                'required' => false,
                'attr' => [
                    'autocomplete' => 'new-password',
                    'inputmode' => 'text',
                    'placeholder' => 'Code postal ou ZIP',
                    'data-address-role' => 'postal-code',
                    'spellcheck' => 'false',
                    'autocapitalize' => 'characters',
                ],
            ])
            ->add('city', TextType::class, [
                'label' => 'Ville',
                'required' => false,
                'attr' => [
                    'autocomplete' => 'new-password',
                    'placeholder' => 'Ville',
                    'data-address-role' => 'city',
                    'spellcheck' => 'false',
                    'autocapitalize' => 'off',
                ],
            ])
            ->add('defaultAddress', TextType::class, [
                'label' => 'Rue',
                'required' => false,
                'attr' => [
                    'autocomplete' => 'new-password',
                    'placeholder' => 'Nom de rue',
                    'data-address-role' => 'street',
                    'spellcheck' => 'false',
                    'autocapitalize' => 'off',
                ],
            ])
            ->add('addressBuilding', TextType::class, [
                'label' => 'Numéro',
                'required' => false,
                'attr' => [
                    'autocomplete' => 'new-password',
                    'placeholder' => '12 bis, 4A…',
                    'spellcheck' => 'false',
                    'autocapitalize' => 'off',
                ],
            ])
            ->add('addressExtra', TextType::class, [
                'label' => 'Complément',
                'required' => false,
                'attr' => [
                    'autocomplete' => 'new-password',
                    'placeholder' => 'Bâtiment, étage, digicode…',
                    'spellcheck' => 'false',
                    'autocapitalize' => 'off',
                ],
            ])
            ->add('marketingOptIn', CheckboxType::class, [
                'label' => 'Je souhaite recevoir les nouveautés et offres du site',
                'required' => false,
                'help' => 'Option facultative. Vous pourrez retirer ce consentement à tout moment depuis votre compte.',
            ])
            ->add('acceptCgv', CheckboxType::class, [
                'mapped' => false,
                'required' => true,
                'label' => 'J’accepte les conditions générales de vente',
                'help' => 'En continuant, vous acceptez les <a href="/conditions-generales-de-vente" target="_blank" rel="noopener">CGV</a>.',
                'help_html' => true,
                'constraints' => [
                    new IsTrue(message: 'Vous devez accepter les conditions générales de vente.'),
                ],
            ])
            ->add('acceptPrivacy', CheckboxType::class, [
                'mapped' => false,
                'required' => true,
                'label' => 'J’ai lu la politique de confidentialité',
                'help' => 'Vos données sont traitées selon la <a href="/politique-de-confidentialite" target="_blank" rel="noopener">politique de confidentialité</a>.',
                'help_html' => true,
                'constraints' => [
                    new IsTrue(message: 'Vous devez confirmer avoir pris connaissance de la politique de confidentialité.'),
                ],
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
