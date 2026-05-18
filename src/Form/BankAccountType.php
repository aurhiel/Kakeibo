<?php

declare(strict_types=1);

namespace App\Form;

// Entities
use App\Entity\BankAccount;
use App\Entity\BankBrand;
use App\Entity\Currency;

// Components
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class BankAccountType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('label', TextType::class, [
                'label' => 'form_bank_account.label.label',
                'attr' => ['placeholder' => 'form_bank_account.label.placeholder'],
            ])
            ->add('bank_brand', EntityType::class, [
                'class' => BankBrand::class,
                'label' => 'form_bank_account.bank_brand.label',
                'placeholder' => 'form_bank_account.bank_brand.placeholder',
                'choice_label' => fn($bank_brand) => $bank_brand->getLabel()
            ])
            ->add('currency', EntityType::class, [
                'class' => Currency::class,
                'label' => 'form_bank_account.currency.label',
                'choice_label' => fn($currency): string => $currency->getLabel().' - '.$currency->getName()
            ])
            ->add('show_on_dashboard', CheckboxType::class, [
                'required' => false,
                'label' => 'form_bank_account.show_on_dashboard.label',
                'row_attr' => ['class' => 'form-check custom-control custom-checkbox'],
                'attr' => ['class' => 'form-check-input custom-control-input'],
                'label_attr' => ['class' => 'form-check-label custom-control-label'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BankAccount::class,
            'type_form' => 'add'
        ]);
    }
}
