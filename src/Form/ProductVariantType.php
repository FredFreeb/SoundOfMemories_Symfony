<?php

namespace App\Form;

use App\Entity\ProductVariant;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductVariantType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('label', TextType::class, [
                'label' => 'Libelle',
            ])
            ->add('sku', TextType::class, [
                'label' => 'SKU',
                'required' => false,
            ])
            ->add('priceCents', MoneyType::class, [
                'label' => 'Prix',
                'currency' => 'EUR',
                'divisor' => 100,
            ])
            ->add('compareAtPriceCents', MoneyType::class, [
                'label' => 'Prix barre',
                'currency' => 'EUR',
                'divisor' => 100,
                'required' => false,
                'empty_data' => '',
            ])
            ->add('stock', IntegerType::class, [
                'label' => 'Stock',
            ])
            ->add('position', IntegerType::class, [
                'label' => 'Ordre',
                'required' => false,
                'empty_data' => '0',
            ])
            ->add('isDefault', CheckboxType::class, [
                'label' => 'Variante par defaut',
                'required' => false,
            ])
            ->add('isPublished', CheckboxType::class, [
                'label' => 'Publiee',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProductVariant::class,
        ]);
    }
}
