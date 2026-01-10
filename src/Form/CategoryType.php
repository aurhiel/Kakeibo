<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Category;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class CategoryType extends AbstractType
{
    public function __construct(private array $categoryIcons) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Generate repeat type <select> options
        $icons_choices = array_combine(
            array_map(static fn($slug) => "category.icons.$slug", $this->categoryIcons),
            $this->categoryIcons
        );

        $builder
            ->add('label', TextType::class, [
                'label' => 'form_category.label.label',
                'attr' => [ 'placeholder' => 'form_category.label.placeholder' ],
            ])
            ->add('color', ColorType::class, [
              'label' => 'form_category.color.label',
              'attr' => [ 'placeholder' => 'form_category.color.placeholder' ]
            ])
            ->add('icon', ChoiceType::class, [
              'label' => 'form_category.icon.label',
              'attr' => ['class' => 'custom-select'],
              'choices' => $icons_choices
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Category::class,
        ]);
    }
}
