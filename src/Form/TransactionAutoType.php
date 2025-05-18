<?php
namespace App\Form;

// Entities
use App\Entity\TransactionAuto;
use App\Entity\Category;
use App\Entity\User;
// Repositories
use App\Repository\CategoryRepository;

// Components
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Security\Core\Security;

class TransactionAutoType extends AbstractType
{
    private User $user;

    public function __construct(Security $security)
    {
        $this->user = $security->getUser();
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $is_edit = ($options['type_form'] == 'edit');
        $user = $this->user;

        $repeat_type_choices = [];
        // Generate repeat type <select> options
        foreach (TransactionAuto::RT_LIST as $repeat_type) {
            $repeat_type_choices['global.repeat_types.' . $repeat_type] = $repeat_type;
        }

        $min_datetime = new \DateTime('now + 1 day');
        $date_start_attrs = ['min' => $min_datetime->format('Y-m-d'), 'value' => $min_datetime->format('Y-m-d')];

        if ($is_edit == true) {
            // Set date start field as readonly if already launched
            if (!empty($options['data']->getDateLast())) {
                $date_start_attrs['readonly'] = true;
                $date_start_attrs['min'] = $options['data']->getDateStart()->format('Y-m-d');
            }

            // Force date start value
            $date_start_attrs['value'] = $options['data']->getDateStart()->format('Y-m-d');
        }

        $builder
            ->add('label',      TextType::class, [
                'label'         => 'form_trans_auto.label.label',
                'attr'          => [
                    'placeholder'   => 'form_trans_auto.label.placeholder',
                    'autocomplete'  => 'off'
                ],
            ])
            ->add('amount',     NumberType::class, [
                'label'         => 'form_trans_auto.amount.label',
                'scale'         => 2,
                'html5'         => true,
                'attr'          => [
                    'placeholder'   => 'form_trans_auto.amount.placeholder',
                    'class'         => 'text-right',
                    'autocomplete'  => 'off',
                    'step'          => '0.01'
                ],
            ])
            ->add('date_start', DateType::class, [
                'label'         => 'form_trans_auto.date_start.label',
                'widget'        => 'single_text',
                'attr'          => $date_start_attrs
            ])
            ->add('repeat_type',  ChoiceType::class, [
                'label'       => 'form_trans_auto.repeat_type.label',
                'attr'        => ['class' => 'custom-select'],
                'multiple'    => false,
                'choices'     => $repeat_type_choices
            ])
            ->add('category',   EntityType::class, [
                'class'         => Category::class,
                'label'         => 'form_trans_auto.category.label',
                'placeholder'   => 'form_trans_auto.category.placeholder',
                'attr'          => ['class' => 'custom-select'],
                'query_builder' => function (CategoryRepository $r) use ($user) {
                    return $r->createQueryBuilder('c')
                        ->where('c.is_default = true')
                        ->orWhere('c.user = :userId')
                        ->setParameter('userId', $user->getId())
                        // Order on theme name
                        ->addOrderBy('c.label', 'ASC');
                },
                'choice_label'  => function ($category) {
                    return $category->getLabel();
                }
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            // 'csrf_protection' => false, // NOTE : Remove CSRF protection to get ajax submit working
            'data_class' => TransactionAuto::class,
            'type_form' => 'add'
        ));
    }
}
