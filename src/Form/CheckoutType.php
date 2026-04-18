<?php

namespace App\Form;

use App\Entity\Order;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class CheckoutType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('customerName', TextType::class, [
                'label' => 'Nom complet',
                'constraints' => [new NotBlank(), new Length(max: 180)],
                'attr' => [
                    'autocomplete' => 'name',
                    'placeholder' => 'Nom et prénom du destinataire',
                ],
            ])
            ->add('customerEmail', EmailType::class, [
                'label' => 'Email',
                'constraints' => [new NotBlank(), new Length(max: 180)],
                'attr' => [
                    'autocomplete' => 'email',
                    'inputmode' => 'email',
                    'placeholder' => 'Adresse utilisée pour le suivi',
                ],
            ])
            ->add('customerPhone', TextType::class, [
                'label' => 'Téléphone',
                'constraints' => [new NotBlank(), new Length(max: 40)],
                'attr' => [
                    'autocomplete' => 'tel',
                    'inputmode' => 'tel',
                    'placeholder' => 'Numéro joignable pour la livraison',
                ],
            ])
            ->add('shippingCountryCode', ChoiceType::class, [
                'label' => 'Pays',
                'choices' => $options['shipping_country_choices'],
                'placeholder' => 'Choisir le pays de livraison',
                'constraints' => [new NotBlank()],
                'attr' => [
                    'data-address-role' => 'country',
                ],
            ])
            ->add('city', TextType::class, [
                'label' => 'Ville',
                'constraints' => [new NotBlank(), new Length(max: 120)],
                'attr' => [
                    'autocomplete' => 'new-password',
                    'placeholder' => 'Ville',
                    'data-address-role' => 'city',
                    'spellcheck' => 'false',
                    'autocapitalize' => 'off',
                ],
            ])
            ->add('shippingAddress', TextType::class, [
                'label' => 'Rue',
                'required' => true,
                'constraints' => [new NotBlank(), new Length(max: 255)],
                'attr' => [
                    'autocomplete' => 'new-password',
                    'placeholder' => 'Nom de rue',
                    'data-address-role' => 'street',
                    'spellcheck' => 'false',
                    'autocapitalize' => 'off',
                ],
            ])
            ->add('shippingStreetNumber', TextType::class, [
                'mapped' => false,
                'label' => 'Numéro',
                'required' => false,
                'constraints' => [new Length(max: 40)],
                'attr' => [
                    'autocomplete' => 'new-password',
                    'placeholder' => '12 bis, 4A…',
                    'spellcheck' => 'false',
                    'autocapitalize' => 'off',
                ],
            ])
            ->add('postalCode', TextType::class, [
                'label' => 'Code postal',
                'required' => true,
                'constraints' => [new NotBlank(), new Length(max: 20)],
                'attr' => [
                    'autocomplete' => 'new-password',
                    'inputmode' => 'text',
                    'placeholder' => 'Code postal ou ZIP',
                    'data-address-role' => 'postal-code',
                    'spellcheck' => 'false',
                    'autocapitalize' => 'characters',
                ],
            ])
            ->add('shippingMethodCode', ChoiceType::class, [
                'label' => 'Mode de livraison',
                'required' => (bool) $options['shipping_method_required'],
                'placeholder' => [] === $options['shipping_choices']
                    ? 'Renseignez d abord votre pays et votre adresse'
                    : 'Choisir un mode de livraison',
                'choices' => $this->buildShippingChoices($options['shipping_choices']),
                'help' => $options['shipping_help'],
                'attr' => [
                    'data-shipping-choice' => 'true',
                ],
            ])
            ->add('note', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, [
                'label' => 'Complément',
                'required' => false,
                'attr' => [
                    'autocomplete' => 'new-password',
                    'rows' => 3,
                    'placeholder' => 'Digicode, étage, bâtiment ou précision utile…',
                    'autocapitalize' => 'off',
                ],
            ])
            ->add('acceptCgv', \Symfony\Component\Form\Extension\Core\Type\CheckboxType::class, [
                'mapped' => false,
                'required' => true,
                'label' => 'J’accepte les conditions générales de vente',
                'help' => 'En continuant, vous acceptez les <a href="/conditions-generales-de-vente" target="_blank" rel="noopener">CGV</a>.',
                'help_html' => true,
                'constraints' => [
                    new IsTrue(message: 'Vous devez accepter les conditions générales de vente.'),
                ],
            ])
            ->add('acceptPrivacy', \Symfony\Component\Form\Extension\Core\Type\CheckboxType::class, [
                'mapped' => false,
                'required' => true,
                'label' => 'J’ai lu la politique de confidentialité',
                'help' => 'Vos données sont traitées selon la <a href="/politique-de-confidentialite" target="_blank" rel="noopener">politique de confidentialité</a>.',
                'help_html' => true,
                'constraints' => [
                    new IsTrue(message: 'Vous devez confirmer avoir pris connaissance de la politique de confidentialité.'),
                ],
            ]);

        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event): void {
            $order = $event->getData();
            $form = $event->getForm();

            if (!$order instanceof Order) {
                return;
            }

            [$street, $number] = $this->splitShippingAddress($order->getShippingAddress());

            $form->get('shippingAddress')->setData($street);
            $form->get('shippingStreetNumber')->setData($number);
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event): void {
            $data = $event->getData();

            if (!\is_array($data)) {
                return;
            }

            $street = isset($data['shippingAddress']) ? (string) $data['shippingAddress'] : '';
            $number = isset($data['shippingStreetNumber']) ? (string) $data['shippingStreetNumber'] : '';
            $data['shippingAddress'] = $this->combineShippingAddress($street, $number);

            $event->setData($data);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
            'allow_marketing_opt_in' => false,
            'marketing_opt_in_default' => false,
            'shipping_country_choices' => [],
            'shipping_choices' => [],
            'shipping_help' => null,
            'shipping_method_required' => false,
        ]);
        $resolver->setAllowedTypes('allow_marketing_opt_in', 'bool');
        $resolver->setAllowedTypes('marketing_opt_in_default', 'bool');
        $resolver->setAllowedTypes('shipping_country_choices', 'array');
        $resolver->setAllowedTypes('shipping_choices', 'array');
        $resolver->setAllowedTypes('shipping_help', ['null', 'string']);
        $resolver->setAllowedTypes('shipping_method_required', 'bool');
    }

    /**
     * @param array<int, array{label:string,priceCents:int,description:string,carrier:string}> $shippingChoices
     *
     * @return array<string, string>
     */
    private function buildShippingChoices(array $shippingChoices): array
    {
        $choices = [];

        foreach ($shippingChoices as $option) {
            if (!isset($option['code'], $option['label'], $option['priceCents'])) {
                continue;
            }

            $choices[sprintf(
                '%s · %s EUR',
                (string) $option['label'],
                number_format(((int) $option['priceCents']) / 100, 2, ',', ' ')
            )] = (string) $option['code'];
        }

        return $choices;
    }

    /**
     * @return array{0:string,1:string}
     */
    private function splitShippingAddress(?string $shippingAddress): array
    {
        $value = trim((string) $shippingAddress);

        if ('' === $value) {
            return ['', ''];
        }

        $primaryLine = trim((string) preg_split('/\s*,\s*/', $value, 2)[0]);

        if (preg_match('/^(\d+[^\s,]*)\s+(.*)$/u', $primaryLine, $matches)) {
            return [trim($matches[2]), trim($matches[1])];
        }

        if (preg_match('/^(.*?)(?:\s+)(\d+[^\s,]*)$/u', $primaryLine, $matches)) {
            return [trim($matches[1]), trim($matches[2])];
        }

        return [$primaryLine, ''];
    }

    private function combineShippingAddress(string $street, string $number): string
    {
        return trim(implode(' ', array_filter([
            trim($street),
            trim($number),
        ], static fn (string $value): bool => '' !== $value)));
    }
}
