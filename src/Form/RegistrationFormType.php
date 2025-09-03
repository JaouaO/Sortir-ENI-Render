<?php

namespace App\Form;

use App\Entity\Site;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            ->add('pseudo',TextType::class, [
                'required' => false,
                'attr' => [
                    'placeholder' => 'Le pseudo'
                ]
            ])
            ->add('name', TextType::class, [
                'required' => false,
                'attr' => [
                    'placeholder' => 'Doe'
                ]
            ])
            ->add('firstName',TextType::class, [
                'required' => false,
                'attr' => [
                    'placeholder' => 'John'
                ]
            ])
            ->add('phone', TelType::class, [
                'required' => false,
                'attr' => [
                    'placeholder' => '0102020202'
                ]
            ])
            ->add('email',TextType::class, [
                'required' => false,
                'attr' => [
                    'placeholder' => 'johndoe@gmail.com'
                ]
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
                        'maxSizeMessage' => 'votre fichier est trop lourd',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                            'image/bmp',
                            'image/jpg'
                        ],
                        'mimeTypesMessage' => 'Les formats accéptés sont jpeg,png,gif.',
                    ])
                ]
            ])
        ;
        if ($options['include_password_and_terms'] ?? false) {
            $builder
                ->add('plainPassword', PasswordType::class, [
                    'label' => 'Mot de passe',
                    'required' => false,
                    'mapped' => false,
                    'attr' => ['autocomplete' => 'new-password'],
                    'constraints' => [
                        new NotBlank([
                            'message' => 'Entrez un mot de passe ',
                        ]),
                        new Length([
                            'min' => 4,
                            'minMessage' => 'Your password should be at least {{ limit }} characters',
                            // max length allowed by Symfony for sécurité
                            'max' => 4096,
                        ]),
                    ]
                ])
                ->add('agreeTerms', CheckboxType::class, [
                    'label' => 'J\'accepte les termes et conditions',
                    'mapped' => false,
                    'constraints' => [
                        new IsTrue([
                            'message' => 'Veuillez accepter les conditions.',
                        ]),
                    ],
                ]);
        }



    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'include_password_and_terms' => true,
        ]);
    }
}