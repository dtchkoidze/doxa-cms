<?php

return [
    'default' => [
        'admin' => [
            'channels_and_locales' => 'საიტები და ენები',
            'dashboard' => 'დაშბორდი',
            'installed_packages' => 'მოოქმედი პაკეტები',
        ],
        'index' => [
            'title' => ':Title_plural',
        ],
        'edit' => [
            'title' => ':Title-ის რედაქტირება',
        ],
        'create' => [
            'title' => ':Title-ის შექმნა',
        ],
        'create-btn' => 'ახალი :Title-ის შექმნა',
        'back-to-index-btn' => 'უკან :Title_plural-ში',
        'save-btn' => 'შენახვა',
        'save-success' => 'ჩანაწერი წარმატებით შეინახა',
        'save-fail' => 'შენახვის დროს დაფიქსირდა შეცდომა',
        'delete-success' => 'ჩანაწერი წარმატებით წაიშალა',
        'delete-fail' => 'ჩანაწერის წაშლა ვერ მოხერხდა',
        'records-deleted' => 'ჩანაწერები წარმატებებით წაიშალა',
        'data-updated' => 'მონაცემები წარმატებით შეიცვალა',
        'positions-updated' => 'პოზიცია წარმატებით შეიცვალა',
        'variation-delete-success' => 'თარგმანი წარმატებით წაიშალა',
        'no-resource' => 'ჩანაწერი ვერ მოიძებნა',
        'no-related-records' => 'დაკავშირებული ჩანაწერები ვერ მოიძებნა',
        'published_at' => 'გამოქვეყნების თარიღი',
        'categories' => 'კატეგორია',
        'category' => 'კატეგორია',
        'published' => 'გამოქვეყნებულია',
        'add' => 'დამატება',
        'tags' => 'თაგები',
        'related-already-applied' => 'ჩანაწერი უკვე მიმაგრებულია',
        'edit_form' => [
            'featured' => 'რეკომენდირებულია',
            'title' => 'დასახელება',
            'name' => 'სახელი',
            'key' => 'Key',
            'url_key' => 'Url key',
            'url_key_specific_for_variation' => 'Url key (სპეციფიკური მიმდინარე ენისათვის)',
            'active' => 'აქტიური',
            'text' => 'ტექსტი',
            'text_short' => 'მოკლე ტექსტი',
            'description' => 'ტექსტი',
            'description_short' => 'მოკლე ტექსტი',
            'main_text' => 'Main ტექსტი',
            'secondary_text' => 'მეორეული ტექსტი',
            'tag' => 'თეგი',
            'main_description' => 'Main data',
            'main_block_title' => 'Main data',

            'icon' => 'აიქონი',
            'image' => 'სურათი',
            'block_title' => [
                'main' => 'მთავარი ინფორმაცია',
                'all_info' => 'ყველა ინფორმაცია',
            ],
            'required' => [
                'if_active' => 'სავალდებულოა თუ ჩანაწერი აქტიურია*',
                'required' => '*'
            ],
            'rules' => [
                'key' => 'ლათინური ასოები, ციფრები, "-", "_", ".", "/"',
            ]
        ],
        'datagrid' => [
            'active' => 'A',
            'key' => 'Key',
            'order' => 'P',
            'name' => 'სახელი',
            'title' => 'დასახელება',
            'image' => 'სურათი',
            'icon' => 'Icon',
            'redirect_url' => 'გადამისამართების URL',
            'text' => 'ტექსტი',
            'featured' => 'რეკომენდირებული',
            'actions' => 'მოქმედება',
            'value' => 'მნიშვნელობა',
            'air_time' => 'საეთერო დრო',
            'tag' => 'თეგი',
            'empty-variation' => 'თარგმანი არ არის',
            'no-records-found' => 'ჩანაწერები ვერ მოიძებნა',
            'filter' => [
                'title' => 'ფილტრები',
            ],
            'toolbar' => [
                'search' => [
                    'title' => 'ძებნა'
                ],
                'results' => ':total ჩანაწერი მოიძებნა',
                'filters' => [
                    'title' => 'ფილტრები',
                    'custom-filters' => [
                        'clear-all' => 'გასუფთავება',
                    ],
                ],
                'per-page' => 'გვერდზე',
                'of' => ':total დან',
                'mass-actions' => [
                    'select-action' => 'აირჩიე მოქმედება',
                    'delete' => 'წაშლა',
                    'confirmation' => [
                        'delete_message' => 'მონიშნული ჩანაწერები წაიშლება აღდგენის შესაძლებლობის გარეშე.',
                        'delete_qu' => 'დაადასტურეთ ქმედება',
                    ]
                ],
            ],
        ],
        'actions' => [
            'title' => 'მოქმედება',
            'delete_when_variations_qu' => 'რა წავშალოთ?',
            'delete_when_variations_variants' => 'წავშალოთ ჩანაწერები ყველა ენისთვის და საიტისთვის თუ მარტო მიმდიანარე ენისთვის?',
            'delete_record' => 'მთელი ჩანაწერი',
            'delete' => 'ნამდვილად გსურთ ამ ჩანაწერის წაშლა?',
            'delete_variation' => 'მიმდინარე ენისთვის',
            'disagree-btn' => 'გაუქმება',
            'agree-btn' => 'დასტური',
        ],


        'auth' => [
            'authorization' => 'ავტორიზაცია',
            'back_to_sign_in' => 'ავტორიზაციაში დაბრუნება',
            'change_password' => 'პაროლის შეცვლა',
            'confirm_password' => 'დაადასტურეთ პაროლი',
            'forget_password' => 'დაგავიწყდათ პაროლი?',
            'form' => [
                'label_email' => 'Email',
                'label_password' => 'პაროლი',
                'placeholder_email' => 'example@example.com',
                'label_name' => 'სახელი',
                'placeholder_name' => 'სახელი',
            ],
            'new_password' => 'ახალი პაროლი',
            'register' => 'რეგისტრაცია',
            'registered_email' => 'დარეგისტრირებული Email',
            'registration' => 'რეგისტრაცია',
            'reset' => 'აღდგენა',
            'reset_password' => 'პაროლის აღდგენა',
            'sign_in' => 'შესვლა',
            'sign_in_w_email' => 'Email-ით ავტორიზაცია',
        ],
    ]
];
