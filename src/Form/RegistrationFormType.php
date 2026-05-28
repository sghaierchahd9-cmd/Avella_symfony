<?php
// src/Form/RegistrationFormType.php
namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'attr' => ['placeholder' => 'votre nom complet'],
            ])
            ->add('prenom', TextType::class, [
                'attr' => ['placeholder' => 'votre prénom'],
            ])
            ->add('telephone', TelType::class, [
                'attr' => ['placeholder' => 'votre numéro de téléphone'],
            ])
            ->add('email', TextType::class, [
                'attr' => ['placeholder' => 'votre adresse email'],
            ])
            ->add('roles', ChoiceType::class, [
                'choices' => [
                    'Client'  => 'ROLE_CLIENT',
                    'Vendeur' => 'ROLE_VENDEUR',
                ],
                'expanded' => true,   // radio buttons
                'multiple' => false,
                'mapped'   => false,  // on gère manuellement dans le contrôleur
                'label'    => 'Tu es un ?',
            ])
            ->add('plainPassword', PasswordType::class, [
                'mapped' => false,
                'attr'   => ['autocomplete' => 'new-password', 'placeholder' => 'votre mot de passe'],
                'constraints' => [
                    new NotBlank(message: 'Please enter a password'),
                    new Length(min: 6, minMessage: 'Minimum {{ limit }} caractères', max: 4096),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => User::class]);
    }
}