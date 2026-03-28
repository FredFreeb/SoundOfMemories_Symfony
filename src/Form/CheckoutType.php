<?php

namespace App\Form;

use App\Entity\Order;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
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
            ->add('city', TextType::class, [
                'label' => 'Ville',
                'constraints' => [new NotBlank(), new Length(max: 120)],
                'attr' => [
                    'autocomplete' => 'address-level2',
                    'placeholder' => 'Commencez par votre ville',
                    'data-address-role' => 'city',
                ],
            ])
            ->add('shippingCountryCode', ChoiceType::class, [
                'label' => 'Pays de livraison',
                'choices' => $options['shipping_country_choices'],
                'placeholder' => 'Choisir le pays de livraison',
                'constraints' => [new NotBlank()],
            ])
            ->add('shippingAddress', TextType::class, [
                'label' => 'Rue et numéro',
                'required' => true,
                'constraints' => [new NotBlank(), new Length(max: 255)],
                'attr' => [
                    'autocomplete' => 'street-address',
                    'placeholder' => 'Puis choisissez votre rue',
                    'data-address-role' => 'street',
                ],
            ])
            ->add('postalCode', TextType::class, [
                'label' => 'Code postal',
                'required' => true,
                'constraints' => [new NotBlank(), new Length(max: 20)],
                'attr' => [
                    'autocomplete' => 'postal-code',
                    'inputmode' => 'numeric',
                    'placeholder' => 'Code postal',
                    'data-address-role' => 'postal-code',
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
                'label' => 'Instructions de livraison',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                    'placeholder' => 'Digicode, étage, précision utile pour la préparation ou la livraison…',
                ],
            ]);

        if (true === $options['allow_marketing_opt_in']) {
            $builder->add('joinSecretSociety', CheckboxType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Je rejoins la Société secrète et j’active 10 % sur cette première commande',
                'help' => 'Consentement marketing facultatif. Vous pourrez vous désinscrire ensuite depuis votre compte, même si vous conservez vos données de commande.',
                'data' => (bool) $options['marketing_opt_in_default'],
            ]);
        }
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
}
