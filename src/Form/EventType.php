<?php

namespace App\Form;

use App\Entity\Event;
use App\Entity\User;
use App\Entity\Place;
use App\Entity\Site;
use App\Entity\State;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Nom'])
            ->add('startDateTime', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'Date et heure de la sortie'
            ])
            ->add('endDateTime', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'Date et heure de fin de la sortie'
            ])
            ->add('registrationDeadline', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'Date limite d’inscription'
            ])
            ->add('maxParticipants', IntegerType::class,['label'=>'Nombre maximum de participant.es'])
            ->add('eventInfo', TextAreaType::class, [
                'label' => 'Informations de l\'évènement',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 5,
                    'placeholder' => 'Ajoutez une description ou des infos utiles sur la sortie'
                ]])

            ->add('site', EntityType::class, [
                'class' => Site::class,
                'choice_label' => 'name',
                'label' => 'Site de l\'évènement',
            ])
            ->add('place', EntityType::class, [
                'class' => Place::class,
                'choice_label' => 'name',
                'label' => 'Lieu de l\'évènement',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
        ]);
    }
}
