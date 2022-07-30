<?php
namespace App\Form;

// Entities
use App\Entity\Transaction;
use App\Entity\Category;

// Repositories
use App\Repository\CategoryRepository;

// Components
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Security\Core\Security;

class TransactionType extends AbstractType
{
    public function __construct(Security $security)
    {
        /**
         * @var User $user
         */
        $this->user = $security->getUser();
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $is_edit = ($options['type_form'] == 'edit');
        $user = $this->user;

        $builder
            ->add('label', TextType::class, [
                'label' => 'form_transaction.label.label',
                'attr' => [
                    'placeholder' => 'form_transaction.label.placeholder',
                    'autocomplete' => 'off'
                ],
            ])
            ->add('amount', NumberType::class, [
                'label' => 'form_transaction.amount.label',
                'scale' => 2,
                'html5' => true,
                'attr' => [
                    'placeholder' => 'form_transaction.amount.placeholder',
                    'class' => 'text-right',
                    'autocomplete' => 'off',
                    'step' => '0.01'
                ],
            ])
            ->add('date', DateType::class, [
                'label' => 'form_transaction.date.label',
                'widget' => 'single_text',
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'label' => 'form_transaction.category.label',
                'placeholder' => 'form_transaction.category.placeholder',
                'attr' => ['class' => 'custom-select'],
                'query_builder' => function (CategoryRepository $r) use ($user) {
                    return $r->createQueryBuilder('c')
                        ->where('c.is_default = true')
                        ->orWhere('c.user = :userId')
                        ->setParameter('userId', $user->getId())
                        // Order on theme name
                        ->addOrderBy('c.label', 'ASC');
                },
                'choice_label' => function ($category) {
                    return $category->getLabel();
                }
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            // 'csrf_protection' => false,             // NOTE : Remove CSRF protection to get ajax submit working
            'data_class'  => Transaction::class,
            'type_form'   => 'add'
        ));
    }
}
