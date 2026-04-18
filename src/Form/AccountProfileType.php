<?php

namespace App\Form;

use App\Entity\User;
use App\Service\BoxtalShippingService;
use App\Service\PhoneNumberService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class AccountProfileType extends AbstractType
{
    public function __construct(
        private readonly PhoneNumberService $phoneNumberService,
        private readonly BoxtalShippingService $boxtalShipping,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fullName', TextType::class, [
                'label' => 'Nom complet',
                'constraints' => [
                    new NotBlank(),
                    new Length(max: 120),
                ],
                'attr' => [
                    'autocomplete' => 'name',
                ],
            ])
            ->add('phoneCountryCode', ChoiceType::class, [
                'mapped' => false,
                'label' => 'Indicatif',
                'required' => false,
                'choices' => $this->phoneNumberService->getCountryChoices(),
                'placeholder' => 'Choisir',
                'choice_attr' => fn (mixed $choice, string $label, mixed $value): array => [
                    'data-dial-code' => (string) $this->phoneNumberService->getDialCode((string) $value),
                    'data-flag' => $this->phoneNumberService->getFlagEmoji((string) $value),
                ],
                'attr' => [
                    'data-phone-country' => 'true',
                ],
            ])
            ->add('phone', TextType::class, [
                'label' => 'Numéro de téléphone',
                'required' => true,
                'constraints' => [
                    new NotBlank(),
                    new Length(max: 40),
                ],
                'attr' => [
                    'autocomplete' => 'tel',
                    'inputmode' => 'tel',
                    'placeholder' => 'Ex. 6 12 34 56 78',
                    'data-phone-local' => 'true',
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
            ->add('countryCode', ChoiceType::class, [
                'label' => 'Pays',
                'required' => false,
                'choices' => $this->boxtalShipping->getDestinationCountryChoices(),
                'placeholder' => 'Choisir le pays',
                'attr' => [
                    'data-address-role' => 'country',
                ],
            ])
            ->add('defaultAddress', TextType::class, [
                'label' => 'Rue',
                'required' => true,
                'constraints' => [
                    new NotBlank(),
                    new Length(max: 255),
                ],
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
                'constraints' => [
                    new Length(max: 120),
                ],
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
                'constraints' => [
                    new Length(max: 160),
                ],
                'attr' => [
                    'autocomplete' => 'new-password',
                    'placeholder' => 'Bâtiment, étage, digicode…',
                    'spellcheck' => 'false',
                    'autocapitalize' => 'off',
                ],
            ])
            ->add('postalCode', TextType::class, [
                'label' => 'Code postal',
                'required' => true,
                'constraints' => [
                    new NotBlank(),
                    new Length(max: 20),
                ],
                'attr' => [
                    'autocomplete' => 'new-password',
                    'inputmode' => 'text',
                    'placeholder' => 'Code postal ou ZIP',
                    'data-address-role' => 'postal-code',
                    'spellcheck' => 'false',
                    'autocapitalize' => 'characters',
                ],
            ])
            ->add('marketingOptIn', CheckboxType::class, [
                'label' => 'Je souhaite recevoir les nouveautés et garder l’avantage bienvenue si ma première commande n’a pas encore été passée',
                'required' => false,
                'help' => 'Vous pouvez retirer ce consentement à tout moment. Les données marketing sont séparées des données nécessaires aux commandes.',
            ])
            ->add('avatarFile', FileType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Avatar',
                'help' => 'JPEG, PNG ou WebP uniquement, 2 Mo maximum. Le dernier fichier remplace l’ancien.',
                'constraints' => [
                    new Image(
                        maxSize: '2M',
                        maxWidth: 3000,
                        maxHeight: 3000,
                        mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
                        mimeTypesMessage: 'Merci d’utiliser une image JPEG, PNG ou WebP.',
                    ),
                ],
                'attr' => [
                    'accept' => 'image/jpeg,image/png,image/webp',
                    'data-avatar-input' => 'true',
                ],
            ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            $user = $event->getData();
            $form = $event->getForm();

            if (!$user instanceof User) {
                return;
            }

            $region = $this->phoneNumberService->detectRegion($user->getPhone(), 'FR');
            $form->get('phoneCountryCode')->setData($region);
            $form->get('phone')->setData($this->phoneNumberService->getLocalNumber($user->getPhone(), $region));
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event): void {
            $data = $event->getData();
            $form = $event->getForm();

            if (!\is_array($data)) {
                return;
            }

            $region = isset($data['phoneCountryCode']) ? (string) $data['phoneCountryCode'] : 'FR';
            $phone = isset($data['phone']) ? (string) $data['phone'] : '';

            if ('' === trim($phone)) {
                $data['phone'] = null;
                $event->setData($data);

                return;
            }

            try {
                $data['phone'] = $this->phoneNumberService->normalize($region, $phone);
            } catch (\InvalidArgumentException $exception) {
                $form->get('phone')->addError(new FormError($exception->getMessage()));
            }

            $event->setData($data);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
