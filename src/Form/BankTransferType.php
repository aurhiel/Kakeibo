<?php

namespace App\Form;

use App\Entity\BankAccount;
use App\Entity\Category;
use App\Entity\User;
use App\Repository\BankAccountRepository;
use App\Repository\CategoryRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;

class BankTransferType extends AbstractType
{
    private User $user;
    private CategoryRepository $categoryRepository;

    public function __construct(
        Security $security,
        CategoryRepository $categoryRepository
    ) {
        $this->user = $security->getUser();
        $this->categoryRepository = $categoryRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $this->user;
        $defaultBankAccountId = null !== $user->getDefaultBankAccount()
            ? $user->getDefaultBankAccount()->getId()
            : null
        ;
        $defaultCategory = $this->categoryRepository->findDefault();

        $builder
            ->add('label', TextType::class, [
                'label' => 'form_bank_transfer.label.label',
                'label_attr' => [ 'class' => 'form-label' ],
                'attr' => [
                    'placeholder' => 'form_bank_transfer.label.placeholder',
                    'autocomplete' => 'off'
                ],
            ])
            ->add('details', TextareaType::class, [
                'required' => false,
                'label' => 'form_bank_transfer.details.label',
                'label_attr' => [ 'class' => 'form-label' ],
                'attr' => [
                    'placeholder' => 'form_bank_transfer.details.placeholder',
                    'autocomplete' => 'off'
                ],
            ])
            ->add('amount', NumberType::class, [
                'label' => 'form_bank_transfer.amount.label',
                'label_attr' => [ 'class' => 'form-label' ],
                'scale' => 2,
                'html5' => true,
                'attr' => [
                    'placeholder' => 'form_bank_transfer.amount.placeholder',
                    'autocomplete' => 'off',
                    'min' => 0.01,
                    'step' => 0.01
                ],
            ])
            ->add('date', DateType::class, [
                'label' => 'form_bank_transfer.date.label',
                'label_attr' => [ 'class' => 'form-label' ],
                'widget' => 'single_text',
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'label' => 'form_bank_transfer.category.label',
                'label_attr' => [ 'class' => 'form-label' ],
                'placeholder' => 'form_bank_transfer.category.placeholder',
                'attr' => [
                    'class' => 'custom-select',
                    'data-form-default-value' => null !== $defaultCategory ? $defaultCategory->getId() : ''
                ],
                'query_builder' => function (CategoryRepository $r) use ($user) {
                    return $r->createQueryBuilder('c')
                        ->where('c.is_default = true')
                        ->orWhere('c.user = :userId')
                        ->setParameter('userId', $user->getId())
                        ->addOrderBy('c.label', 'ASC')
                    ;
                },
                'choice_label' => function ($category) {
                    return $category->getLabel();
                }
            ])
            ->add('bank_account_to', EntityType::class, [
                'mapped' => false,
                'class' => BankAccount::class,
                'label' => 'form_bank_transfer.bank_account_to.label',
                'label_attr' => [ 'class' => 'form-label' ],
                'placeholder' => 'form_bank_transfer.bank_account_to.placeholder',
                'attr' => ['class' => 'custom-select'],
                'query_builder' => function (BankAccountRepository $r) use ($user, $defaultBankAccountId) {
                    $qb = $r->createQueryBuilder('ba')
                        ->where('ba.user = :userId')
                        ->andWhere('ba.is_archived = false')
                        ->setParameter('userId', $user->getId())
                    ;

                    if (null !== $defaultBankAccountId) {
                        $qb->andWhere('ba.id != :defaultBankAccountId')
                            ->setParameter('defaultBankAccountId', $defaultBankAccountId)
                        ;
                    }

                    return $qb;
                },
                'choice_label' => function (BankAccount $bankAccount) {
                    return sprintf('%s (%s)', $bankAccount->getLabel(), $bankAccount->getBankBrand()->getLabel());
                }
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'type_form' => 'add',
        ]);
    }
}
