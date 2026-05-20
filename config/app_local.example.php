<?php

use Cake\Mailer\Transport\SmtpTransport;
use function Cake\Core\env;

/*
 * Local configuration file to provide any overrides to your app.php configuration.
 * Copy and save this file as app_local.php and make changes as required.
 * Note: It is not recommended to commit files with credentials such as app_local.php
 * into source code version control.
 */
return [
    /*
     * Debug Level:
     *
     * Production Mode:
     * false: No error messages, errors, or warnings shown.
     *
     * Development Mode:
     * true: Errors and warnings shown.
     */
    'debug' => filter_var(env('DEBUG', true), FILTER_VALIDATE_BOOLEAN),

    /*
     * Security and encryption configuration
     *
     * - salt - A random string used in security hashing methods.
     *   The salt value is also used as the encryption key.
     *   You should treat it as extremely sensitive data.
     */
    'Security' => [
        'salt' => env('SECURITY_SALT', '__SALT__'),
    ],

    /*
     * Connection information used by the ORM to connect
     * to your application's datastores.
     *
     * See app.php for more configuration options.
     */
    'Datasources' => [
        'default' => [
            'host' => 'localhost',
            /*
             * CakePHP will use the default DB port based on the driver selected
             * MySQL on MAMP uses port 8889, MAMP users will want to uncomment
             * the following line and set the port accordingly
             */
            //'port' => 'non_standard_port_number',

            'username' => 'root',
            'password' => 'root',

            'database' => 'academy_management_db',
            /*
             * If not using the default 'public' schema with the PostgreSQL driver
             * set it here.
             */
            //'schema' => 'myapp',

            /*
             * You can use a DSN string to set the entire configuration
             */
            'url' => env('DATABASE_URL', null),
        ],

        /*
         * The test connection is used during the test suite.
         */
        'test' => [
            'host' => 'localhost',
            //'port' => 'non_standard_port_number',
            'username' => 'my_app',
            'password' => 'secret',
            'database' => 'test_myapp',
            //'schema' => 'myapp',
            'url' => env('DATABASE_TEST_URL', 'sqlite://127.0.0.1/tmp/tests.sqlite'),
        ],
    ],

    /*
     * Email configuration.
     *
     * Host and credential configuration in case you are using SmtpTransport
     *
     * See app.php for more configuration options.
     */
    'EmailTransport' => [
        'default' => [
            'className' => SmtpTransport::class,
            'host' => env('EMAIL_SMTP_HOST', 'ssl://u26s1185.iedev.org'),
            'port' => (int)env('EMAIL_SMTP_PORT', 465),
            'timeout' => 30,
            'username' => env('EMAIL_SMTP_USERNAME', 'u26s1185@u26s1185.iedev.org'),
            'password' => env('EMAIL_SMTP_PASSWORD', null),
            'client' => null,
            'tls' => filter_var(env('EMAIL_SMTP_TLS', false), FILTER_VALIDATE_BOOLEAN),
            'url' => env('EMAIL_TRANSPORT_DEFAULT_URL', null),
        ],
    ],

    'Email' => [
        'default' => [
            'transport' => 'default',
            'from' => [
                env('EMAIL_FROM_ADDRESS', 'u26s1185@u26s1185.iedev.org') => env('EMAIL_FROM_NAME', 'CandleCraft Academy'),
            ],
        ],
    ],

    'Stripe' => [
        'environment' => env('STRIPE_ENVIRONMENT', null),
        'api_version' => env('STRIPE_API_VERSION', '2026-02-25.clover'),
        'secret_key' => env('STRIPE_SECRET_KEY', null),
        'publishable_key' => env('STRIPE_PUBLISHABLE_KEY', null),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET', null),
    ],

    'Payments' => [
        'demo_mode' => filter_var(env('PAYMENTS_DEMO_MODE', false), FILTER_VALIDATE_BOOLEAN),
        'admin_alerts' => [
            'email_enabled' => filter_var(env('PAYMENT_ALERT_EMAILS', true), FILTER_VALIDATE_BOOLEAN),
        ],
    ],

    'Recaptcha' => [
        'site_key' => env('RECAPTCHA_SITE_KEY', '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI'),
        'secret_key' => env('RECAPTCHA_SECRET_KEY', '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe'),
    ],

    'Uploads' => [
        'resources_root' => env('UPLOAD_RESOURCES_ROOT', ROOT . DS . 'storage' . DS . 'resources'),
        'resources_url_prefix' => env('UPLOAD_RESOURCES_URL_PREFIX', '/resources'),
    ],
];
