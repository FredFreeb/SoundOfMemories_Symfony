<?php

namespace App\Form;

use App\Entity\User;
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
                    'autocomplete' => 'address-level2',
                    'placeholder' => 'Commencez par votre ville',
                    'data-address-role' => 'city',
                ],
            ])
            ->add('defaultAddress', TextType::class, [
                'label' => 'Rue et numéro',
                'required' => true,
                'constraints' => [
                    new NotBlank(),
                    new Length(max: 255),
                ],
                'attr' => [
                    'autocomplete' => 'street-address',
                    'placeholder' => 'Puis choisissez votre rue',
                    'data-address-role' => 'street',
                ],
            ])
            ->add('addressBuilding', TextType::class, [
                'label' => 'Bâtiment / escalier',
                'required' => false,
                'constraints' => [
                    new Length(max: 120),
                ],
                'attr' => [
                    'autocomplete' => 'address-line2',
                    'placeholder' => 'Bâtiment, escalier, entrée…',
                ],
            ])
            ->add('addressExtra', TextType::class, [
                'label' => 'Complément utile',
                'required' => false,
                'constraints' => [
                    new Length(max: 160),
                ],
                'attr' => [
                    'autocomplete' => 'address-line2',
                    'placeholder' => 'Appartement, étage, indication…',
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
                    'autocomplete' => 'postal-code',
                    'inputmode' => 'numeric',
                    'placeholder' => 'Code postal',
                    'data-address-role' => 'postal-code',
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
