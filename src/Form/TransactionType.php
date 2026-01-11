<?php

declare(strict_types=1);

namespace App\Form;

// Entities
use App\Entity\Transaction;
use App\Entity\Category;
use App\Entity\User;
// Repositories
use App\Repository\CategoryRepository;

// Components
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;

class TransactionType extends AbstractType
{
    private readonly User $user;

    public function __construct(Security $security)
    {
        $this->user = $security->getUser();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $this->user;

        $builder
            ->add('label', TextType::class, [
                'label' => 'form_transaction.label.label',
                'label_attr' => [ 'class' => 'form-label' ],
                'attr' => [
                    'placeholder' => 'form_transaction.label.placeholder',
                    'autocomplete' => 'off'
                ],
            ])
            ->add('details', TextareaType::class, [
                'required' => false,
                'label' => 'form_transaction.details.label',
                'label_attr' => [ 'class' => 'form-label' ],
                'attr' => [
                    'placeholder' => 'form_transaction.details.placeholder',
                    'autocomplete' => 'off'
                ],
            ])
            ->add('amount', NumberType::class, [
                'label' => 'form_transaction.amount.label',
                'label_attr' => [ 'class' => 'form-label' ],
                'scale' => 2,
                'html5' => true,
                'attr' => [
                    'placeholder' => 'form_transaction.amount.placeholder',
                    'autocomplete' => 'off',
                    'step' => '0.01'
                ],
            ])
            ->add('date', DateType::class, [
                'label' => 'form_transaction.date.label',
                'label_attr' => [ 'class' => 'form-label' ],
                'widget' => 'single_text',
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'label' => 'form_transaction.category.label',
                'label_attr' => [ 'class' => 'form-label' ],
                'placeholder' => 'form_transaction.category.placeholder',
                'attr' => ['class' => 'custom-select'],
                'query_builder' => fn(CategoryRepository $r) => $r->createQueryBuilder('c')
                    ->where('c.is_default = true')
                    ->orWhere('c.user = :userId')
                    ->setParameter('userId', $user->getId())
                    ->addOrderBy('c.label', 'ASC'),
                'choice_label' => fn($category) => $category->getLabel()
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // 'csrf_protection' => false, // NOTE : Remove CSRF protection to get ajax submit working
            'data_class' => Transaction::class,
            'type_form' => 'add'
        ]);
    }
}
