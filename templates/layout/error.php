<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         0.10.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 * @var \App\View\AppView $this
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>
        CandleCraft Academy - <?= $this->fetch('title') ?: 'Page Not Found' ?>
    </title>
    <?= $this->Html->meta('icon') ?>

    <?= $this->Html->css(['admin-bootstrap', 'bootstrap-icons', 'admin']) ?>
    <style>
        body {
            min-height: 100vh;
            margin: 0;
            background: #f8f4ed;
            color: #2f2219;
            font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        .error-shell {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 32px 16px;
        }
        .site-error {
            width: min(680px, 100%);
            padding: 40px;
            border: 1px solid rgba(117, 84, 55, 0.18);
            border-radius: 8px;
            background: #fffaf3;
            box-shadow: 0 18px 45px rgba(47, 34, 25, 0.08);
            text-align: center;
        }
        .site-error__eyebrow {
            margin: 0 0 12px;
            color: #94632f;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.18em;
            text-transform: uppercase;
        }
        .site-error h1 {
            margin: 0 0 14px;
            font-size: clamp(32px, 7vw, 56px);
            line-height: 1;
            letter-spacing: 0;
        }
        .site-error p {
            margin: 0 auto;
            max-width: 520px;
            color: #6b5b4d;
            font-size: 16px;
            line-height: 1.6;
        }
        .site-error__actions {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 14px;
            flex-wrap: wrap;
            margin-top: 28px;
        }
        .site-error__button,
        .site-error__link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 44px;
            padding: 10px 18px;
            border-radius: 6px;
            font-weight: 700;
            text-decoration: none;
        }
        .site-error__button {
            background: #6f4a25;
            color: #fffaf3;
        }
        .site-error__button:hover,
        .site-error__button:focus {
            color: #fffaf3;
            background: #563719;
        }
        .site-error__link {
            color: #6f4a25;
        }
        @media (max-width: 520px) {
            .site-error {
                padding: 32px 22px;
            }
            .site-error__button,
            .site-error__link {
                width: 100%;
            }
        }
    </style>

    <?= $this->fetch('meta') ?>
    <?= $this->fetch('css') ?>
    <?= $this->fetch('script') ?>
</head>
<body>
    <main class="error-shell">
        <?= $this->Flash->render() ?>
        <?= $this->fetch('content') ?>
    </main>
</body>
</html>
