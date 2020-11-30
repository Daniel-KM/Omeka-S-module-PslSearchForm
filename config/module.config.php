<?php declare(strict_types=1);
namespace PslSearchForm;

return [
    'view_manager' => [
        'template_path_stack' => [
            dirname(__DIR__) . '/view',
        ],
    ],
    'form_elements' => [
        'factories' => [
            Form\PslForm::class => Service\Form\PslFormFactory::class,
            Form\FilterFieldset::class => Service\Form\FilterFieldsetFactory::class,
            Form\Admin\PslFormConfigFieldset::class => Service\Form\PslFormConfigFieldsetFactory::class,
        ],
    ],
    'search_form_adapters' => [
        'invokables' => [
            'psl' => FormAdapter\PslFormAdapter::class,
        ],
    ],
    'translator' => [
        'translation_file_patterns' => [
            [
                'type' => 'gettext',
                'base_dir' => dirname(__DIR__) . '/language',
                'pattern' => '%s.mo',
                'text_domain' => null,
            ],
        ],
    ],
];
