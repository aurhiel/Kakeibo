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

        $fake_users = [
            [
                'username'  => 'JohnWick942',
                'email'     => 'john@wick.com',
            ],
            [
                'username'  => 'L3ila0rg4na',
                'email'     => 'leila@organa.com',
            ],
            [
                'username'  => 'Dupont&Dupond',
                'email'     => 'hello@dupont-et-dupond.com',
            ],
            [
                'username'  => 'raven99',
                'email'     => 'rachel@roth.com',
            ],
            [
                'username'  => 'w4de-w!lson',
                'email'     => 'wade@wilson.com',
            ],
            [
                'username'  => 'B4tou',
                'email'     => 'bruce@wayne.com',
            ],
            [ 'username'  => 'Natalia Romanova' ],
            [ 'username'  => 'Carole Danvers' ],
            [ 'username'  => 'Harleen Quinzel' ],
            [ 'username'  => 'David Hayter' ],
            [ 'username'  => 'Snake Plissken' ],
            [ 'username'  => 'Nami Nabigeta' ],
        ];

        $user = $fake_users[rand(0, (count($fake_users) - 1))];

        $user_username  = str_replace(' ', '', $user['username']);
        $user_email     = (isset($user['email'])) ? $user['email'] : strtolower(str_replace(' ', '.', $user['username']).'@gmail.com');

        $builder
            ->add('username',       TextType::class,  array(
                'label'     => 'form_user.username.label',
                'attr'      => [ 'placeholder' => "ex: $user_username" ],
                'disabled'  => $is_edit
            ))
            ->add('email',          EmailType::class, array(
                'label'     => 'form_user.email.label',
                'attr'      => [ 'placeholder' => "ex: $user_email" ],
                'disabled'  => $is_edit
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
