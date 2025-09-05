<?php

namespace App\Form;

use App\Entity\Site;
use App\Entity\User;
use Karser\Recaptcha3Bundle\Form\Recaptcha3Type;
use Karser\Recaptcha3Bundle\Validator\Constraints\Recaptcha3;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Regex;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('pseudo', TextType::class, [
                'required' => true,
                'attr' => ['placeholder' => 'Pseudo'],
                'constraints' => [
                    new NotBlank(['message' => 'Le pseudo est obligatoire.'])
                ],
            ])
            ->add('firstName', TextType::class, [
                'required' => true,
                'attr' => ['placeholder' => 'Prénom'],
                'constraints' => [
                    new NotBlank(['message' => 'Le prénom est obligatoire.'])
                ],
            ])
            ->add('name', TextType::class, [
                'required' => true,
                'attr' => ['placeholder' => 'Nom'],
                'constraints' => [
                    new NotBlank(['message' => 'Le nom est obligatoire.'])
                ],
            ])
            ->add('phone', TelType::class, [
                'required' => true,
                'attr' => ['placeholder' => '0102020202'],
                'constraints' => [
                    new NotBlank(['message' => 'Le téléphone est obligatoire.']),
                    new Regex([
                        'pattern' => '/^\d{10}$/',
                        'message' => 'Le numéro de téléphone doit contenir 10 chiffres.',
                    ]),
                ],
            ])
            ->add('email', TextType::class, [
                'required' => true,
                'attr' => ['placeholder' => 'email@example.com'],
                'constraints' => [
                    new NotBlank(['message' => 'L\'email est obligatoire.']),
                    new Email(['message' => 'Entrez un email valide.'])
                ],
            ])
            ->add('site', EntityType::class, [
                'class' => Site::class,
                'choice_label' => 'name',
                'placeholder' => '-- Choisissez un site --',
                'required' => false,
            ])
            ->add('poster_file', FileType::class, [
                'required' => false,
                'mapped' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '1024k',
                        'maxSizeMessage' => 'Votre fichier est trop lourd.',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/gif', 'image/bmp', 'image/jpg'],
                        'mimeTypesMessage' => 'Les formats acceptés sont jpeg, png, gif, bmp.',
                    ])
                ],
            ]);

        if ($options['include_password_and_terms'] ?? false) {
            $builder
                ->add('plainPassword', RepeatedType::class, [
                    'type' => PasswordType::class,
                    'mapped' => false,
                    'invalid_message' => 'Les mots de passe doivent correspondre.',
                    'options' => ['attr' => ['autocomplete' => 'new-password']],
                    'required' => true,
                    'first_options' => ['label' => 'Mot de passe'],
                    'second_options' => ['label' => 'Confirmez le mot de passe'],
                    'constraints' => [
                        new NotBlank(['message' => 'Entrez un mot de passe']),
                        new Length([
                            'min' => 4,
                            'minMessage' => 'Votre mot de passe doit contenir au moins {{ limit }} caractères',
                            'max' => 4096,
                        ]),
                    ],
                ])
                ->add('agreeTerms', CheckboxType::class, [
                    'label' => 'J\'accepte les termes',
                    'mapped' => false,
                    'constraints' => [
                        new IsTrue(['message' => 'Veuillez accepter les conditions.']),
                    ],
                ]);
            if ($options['use_captcha'] ?? false) {
                $builder->add('recaptcha', Recaptcha3Type::class, [
                    'constraints' => [
                        new Recaptcha3(['message' => 'Échec du contrôle anti-bot, réessayez.'])
                    ],
                    'action_name' => 'register',
                ]);
            }
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'include_password_and_terms' => true,
            'use_captcha' => false,
        ]);
    }
}
