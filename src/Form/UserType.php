<?php
namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // $listener = function (FormEvent $event) use ($options)
        // {
        //     $data = $event->getData();
        //     // TODO find a better way to get users connected email
        //     // Retrieve email from session (= edit settings)
        //     if(!empty($options['data']->getEmail()) && !empty($options['data']->getUsername()))
        //     {
        //         // $data['user_email'] = $options['data']->getEmail();
        //         // $data['username'] = $options['data']->getUsername();
        //         $event->setData($data);
        //     }
        // };

        $is_edit = ($options['type_form'] == 'edit');

        $builder
            ->add('username',       TextType::class,  array(
                'label' => 'form_user.username.label',
                'disabled' => $is_edit
            ))
            ->add('email',          EmailType::class, array(
                'label' => 'form_user.email.label',
                'disabled' => $is_edit
            ))
            ->add('plainPassword',  RepeatedType::class, array(
                'type' => PasswordType::class,
                'first_options'  => array(
                    'label' => 'form_user.first_password.label',
                    'attr' => array('value' => ($is_edit ? '0ld-pa$$wo|2d' : ''))
                ),
                'second_options' => array(
                    'label' => 'form_user.second_password.label',
                    'attr' => array('value' => ($is_edit ? '0ld-pa$$wo|2d' : ''))
                ),
            ))
        ;

        // Listener (eg. to fill email field on edit information)
        // $builder->addEventListener(FormEvents::PRE_SUBMIT, $listener);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class'  => User::class,
            'type_form'   => 'add'
        ));
    }
}
