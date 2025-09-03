<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\File;

class UserImportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('csvFile', FileType::class, [
            'label' => 'Fichier CSV',
            'mapped' => false,
            'required' => true,
            'constraints' => [
                new File([
                    'mimeTypes' => [
                        "text/csv",
                        'text/plain',
                        'application/vnd.ms-excel',
                    ],
                    'mimeTypesMessage' => 'Merci de télécharger un fichier CSV valide',
                ]),
            ],
        ]);
    }

}