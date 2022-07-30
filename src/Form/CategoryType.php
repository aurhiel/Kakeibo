<?php

namespace App\Form;

use App\Entity\Category;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

use Symfony\Component\DependencyInjection\ContainerInterface;

class CategoryType extends AbstractType
{
    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }
    
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $icons = $this->container->getParameter('app.category_icons');

        $icons_choices = [];
        // Generate repeat type <select> options
        foreach ($icons as $icon_slug) {
            $icons_choices['category.icons.' . $icon_slug] = $icon_slug;
        }

        $builder
            ->add('label', TextType::class, [
                'label' => 'form_category.label.label',
                'required' => true,
                'attr' => [ 'placeholder' => 'form_category.label.placeholder' ],
            ])
            ->add('color', ColorType::class, [
              'label' => 'form_category.color.label',
              'required' => true,
              'attr' => [ 'placeholder' => 'form_category.color.placeholder' ]
          ])
            ->add('icon', ChoiceType::class, [
              'label'       => 'form_category.icon.label',
              'attr'        => ['class' => 'custom-select'],
              'multiple'    => false,
              'choices'     => $icons_choices
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
