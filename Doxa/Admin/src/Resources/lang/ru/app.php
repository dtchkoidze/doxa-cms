<?php

return [
    'default' => [
        'index' => [
            'title' => 'Страница :Title_plural',
        ],
        'edit' => [
            'title' => 'Редактирование :Title',
        ],
        'create' => [
            'title' => 'Создание :Title',
        ],
        'create-btn' => 'Создать новый :Title',
        'back-to-index-btn' => 'Назад к :Title_plural',
        'save-btn' => 'Сохранить',
        'save-success' => ':Title успешно сохранено',
        'save-fail' => ':Title не удалось сохранить',
        'delete-success' => ':Title Успешно удалено',
        'delete-fail' => 'Не получилось удалить :Title',
        'records-deleted' => 'Данные успешно удалены',
        'category' => 'Категория',
        'edit_form' => [
            'title' => 'Название',
        ],
        'datagrid' => [
            'active' => 'A',
            'key' => 'Ключ',
            'url_key' => 'URL Ключ',
            'order' => 'P',
            'title' => 'Название',
            'name' => 'Название',
            'image' => 'Изображение',
            'text' => 'Текст',
            'featured' => 'Рекомендуемое',
            'empty-variation' => 'Перевод отсутствует',
            'no-records-found' => 'Записей не найдено',
            'filters' => [
                'title' => 'Фильтры',
                'custom-filters' => [
                    'clear-all' => 'Очистить все',
                ],
            ],
            'toolbar' => [
                'search' => [
                    'title' => 'Поиск',
                ],
                'results' => 'Записей найдено: :total',
                'filters' => [
                    'title' => 'Фильтры',
                    'custom-filters' => [
                        'clear-all' => 'Очистить все',
                    ],
                ],
                'per-page' => 'на странице',
                'of' => 'из :total',
                'mass-actions' => [
                    'select-action' => 'Выбери действие',
                    'delete' => 'Удалить',
                    'confirmation' => [
                        'delete_message' => 'Выбранные записи будут удалены без возможности восстановления.',
                        'delete_qu' => 'Подтвердите действие',
                    ]
                ],
            ],
        ],
        'actions' => [
            'title' => 'Действия',
            'delete_when_variations_qu' => 'Что будум удалять?',
            'delete_when_variations_variants' => 'Удалить запись для текущего перевода или всю запись?',
            'delete_record' => 'Всю запись',
            'delete' => 'Удалить',
            'delete_variation' => 'Удалить перевод',
            'disagree-btn' => 'Отмена',
            'agree-btn' => 'ОК',
        ],

        'auth' => [
            "authorization" => "Авторизация",
            "back_to_sign_in" => "Назад к входу в систему",
            "change_password" => "Изменить пароль",
            "confirm_password" => "Подтвердите пароль",
            "forget_password" => "Забыли пароль?",
            'form' => [
                "label_email" => "Email",
                "label_password" => "Пароль",
                "placeholder_email" => 'example@example.com',
                "label_name" => "Имя",
                'placeholder_name' => 'Имя',
            ],
            "new_password" => "Новый пароль",
            "register" => "Регистрация",
            "registered_email" => "Зарегистрированный Email",
            "registration" => "Регистрация",
            "reset" => "Сброс",
            "reset_password" => "Восстановить пароль",
            "sign_in" => "Войти",
            "sign_in_w_email" => "Войти по Email",
        ]
    ],
];
