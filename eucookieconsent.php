<?php
/**
 * Copyright (C) 2017 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.md
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <modules@thirtybees.com>
 * @copyright 2017 thirty bees
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

/**
 * Class EuCookieConsent
 *
 * @since 1.0.0
 */
class EuCookieConsent extends Module
{
    const DISPLAY_POSITION = 'EUCOOKIECONSENT_POSITION';
    const DISPLAY_LAYOUT = 'EUCOOKIECONSENT_LAYOUT';
    const DISPLAY_PALETTE_BANNER = 'EUCOOKIECONSENT_BANNER';
    const DISPLAY_PALETTE_BANNER_TEXT = 'EUCOOKIECONSENT_BANNERTXT';
    const DISPLAY_PALETTE_BUTTON = 'EUCOOKIECONSENT_BUTTON';
    const DISPLAY_PALETTE_BUTTON_TEXT = 'EUCOOKIECONSENT_BUTTONTXT';
    const DISPLAY_LEARN_MORE_LINK = 'EUCOOKIECONSENT_LEARNMORE';
    const DISPLAY_CUSTOM_TEXT = 'EUCOOKIECONSENT_CUSTOMTXT';
    const DISPLAY_MESSAGE_TEXT = 'EUCOOKIECONSENT_MESSAGETXT';
    const DISPLAY_DISMISS_TEXT = 'EUCOOKIECONSENT_DISMISSTXT';
    const DISPLAY_POLICY_LINK_TEXT = 'EUCOOKIECONSENT_POLICYTXT';
    const GEOIP = 'EUCOOKIECONSENT_GEOIP';

    /**
     * EuCookieConsent constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->name = 'eucookieconsent';
        $this->tab = 'front_office_features';
        $this->version = '1.0.1';
        $this->author = 'thirty bees';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('EU Cookie Consent');
        $this->description = $this->l('Comply with the EU Cookie Law and alerts your users about the use of cookies on your store');
    }

    /**
     * Install this module
     *
     * @return bool Indicates whether this module has been successfully installed
     */
    public function install()
    {
        if (!parent::install()) {
            return false;
        }

        $this->installDefaultSettings();

        $this->registerHook('displayHeader');

        return true;
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function getContent()
    {
        $this->postProcess();

        return $this->display(__FILE__, 'views/templates/admin/configure.tpl').$this->renderForm();
    }

    /**
     * Install default settings from a local JSON file
     *
     * @since 1.0.0
     */
    protected function installDefaultSettings()
    {
        $defaults = json_decode(file_get_contents(__DIR__.'/data/defaults.json'), true);
        $languages = Language::getLanguages(false);
        foreach ($defaults as $key => $default) {
            if (isset($default['en'])) {
                $value = [];
                foreach ($languages as $language) {
                    if (isset($default[strtolower($language['iso_code'])])) {
                        $value[$language['id_lang']] = $default[strtolower($language['iso_code'])];
                    } else {
                        $value[$language['id_lang']] = $default['en'];
                    }
                }
            } else {
                $value = $default;
            }
            Configuration::updateGlobalValue($key, $value);
        }
    }

    /**
     * @return array
     *
     * @since 1.0.0
     */
    public function getGeoIpFormOptions()
    {
        return [
            'form' => [
                'legend' => [
                    'title' => $this->l('Geoip'),
                    'icon'  => 'icon-globe',
                ],
                'input'  => [
                    [
                        'type'    => 'switch',
                        'label'   => $this->l('Enable geoip'),
                        'name'    => static::GEOIP,
                        'is_bool' => true,
                        'desc'    => $this->l('Automatically show the cookie message depending on the detected country'),
                        'values'  => [
                            [
                                'id'    => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled'),
                            ],
                            [
                                'id'    => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled'),
                            ],
                        ],
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ],
            ],
        ];
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function renderForm()
    {

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $this->fields_form = [];

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitSettings';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFieldsValues(),
            'languages'    => $this->context->controller->getLanguages(),
            'id_language'  => $this->context->language->id,
        ];

        return $helper->generateForm([$this->getDisplayOptions(), $this->getGeoIpFormOptions()]);
    }

    /**
     * @return array
     *
     * @since 1.0.0
     */
    public function getConfigFieldsValues()
    {
        return [
            static::DISPLAY_POSITION            => (int) Configuration::get(static::DISPLAY_POSITION),
            static::DISPLAY_LAYOUT              => (int) Configuration::get(static::DISPLAY_LAYOUT),
            static::DISPLAY_PALETTE_BANNER      => Configuration::get(static::DISPLAY_PALETTE_BANNER),
            static::DISPLAY_PALETTE_BANNER_TEXT => Configuration::get(static::DISPLAY_PALETTE_BANNER_TEXT),
            static::DISPLAY_PALETTE_BUTTON      => Configuration::get(static::DISPLAY_PALETTE_BUTTON),
            static::DISPLAY_PALETTE_BUTTON_TEXT => Configuration::get(static::DISPLAY_PALETTE_BUTTON_TEXT),
            static::DISPLAY_LEARN_MORE_LINK     => Configuration::getInt(static::DISPLAY_LEARN_MORE_LINK),
            static::DISPLAY_MESSAGE_TEXT        => Configuration::getInt(static::DISPLAY_MESSAGE_TEXT),
            static::DISPLAY_DISMISS_TEXT        => Configuration::getInt(static::DISPLAY_DISMISS_TEXT),
            static::DISPLAY_POLICY_LINK_TEXT    => Configuration::getInt(static::DISPLAY_POLICY_LINK_TEXT),
            static::GEOIP                       => (bool) Configuration::get(static::GEOIP),
        ];
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function hookDisplayHeader()
    {
        $widgetSettings = $this->generateWidgetSettings();

        $this->context->smarty->assign(
            [
                'widgetSettings' => json_encode($widgetSettings),
            ]
        );

        return $this->display(__FILE__, 'views/templates/hooks/cookieconsent.tpl');
    }

    /**
     * @return array
     *
     * @since 1.0.0
     */
    protected function generateWidgetSettings()
    {
        $widgetSettings = []
            + $this->getWidgetPosition()
            + $this->getWidgetLayout();

        $widgetSettings['palette'] = $this->getWidgetPalette();
        $widgetSettings['geoip'] = (bool) Configuration::get(static::GEOIP);
        $widgetSettings['content'] = $this->getWidgetContent();

        if ((int) Configuration::get(static::DISPLAY_LAYOUT) !== 1 && (int) Configuration::get(static::DISPLAY_LAYOUT) !== 4) {
            switch ((int) Configuration::get(static::DISPLAY_LAYOUT)) {
                case 2:
                    $theme = 'edgeless';
                    break;
                default:
                    $theme = 'classic';
                    break;
            }

            $widgetSettings['theme'] = $theme;
        }

        return $widgetSettings;
    }

    /**
     * Process settings page
     *
     * @since 1.0.0
     */
    protected function postProcess()
    {
        if (Tools::isSubmit('submitSettings')) {
            $languageIds = Language::getLanguages(false, null, true);
            $options = array_merge($this->getDisplayOptions()['form']['input'], $this->getGeoIpFormOptions()['form']['input']);
            foreach ($options as $option) {
                $key = $option['name'];
                if (!is_string($key) || !$key) {
                    continue;
                }

                if (isset($option['lang']) && $option['lang']) {
                    $value = [];
                    foreach ($languageIds as $idLang) {
                        $value[$idLang] = Tools::getValue($option['name'].'_'.$idLang);
                    }
                } else {
                    $value = Tools::getValue($option['name']);
                }
                Configuration::updateValue($key, $value);
            }
            $this->context->controller->confirmations[] = $this->l('Settings saved successfully');
        }
    }

    /**
     * @return array
     *
     * @since 1.0.0
     */
    protected function getWidgetPosition()
    {
        switch ((int) Configuration::get(static::DISPLAY_POSITION)) {
            case 2:
                return [
                    'position' => 'top',
                    'static'   => false,
                ];
            case 3:
                return [
                    'position' => 'top',
                    'static'   => true,
                ];
            case 4:
                return [
                    'position' => 'bottom-left',
                    'static'   => false,
                ];
            case 5:
                return [
                    'position' => 'bottom-right',
                    'static'   => false,
                ];
            default:
                return [
                    'position' => 'bottom',
                    'static'   => false,
                ];

        }
    }

    /**
     * @return array
     *
     * @since 1.0.0
     */
    protected function getWidgetLayout()
    {
        $layout = (int) Configuration::get(static::DISPLAY_LAYOUT);

        if ($layout === 1) {
            return [];
        }

        return [
            'theme' => $layout,
        ];
    }

    /**
     * @return array
     *
     * @since 1.0.0
     */
    protected function getWidgetPalette()
    {
        $palette = [];

        $palette['popup'] = [
            'background' => Configuration::get(static::DISPLAY_PALETTE_BANNER),
            'text'       => Configuration::get(static::DISPLAY_PALETTE_BANNER_TEXT),
        ];

        $palette['button'] = [
            'background' => Configuration::get(static::DISPLAY_PALETTE_BUTTON),
            'text'       => Configuration::get(static::DISPLAY_PALETTE_BUTTON_TEXT),
        ];

        if ((int) Configuration::get(static::DISPLAY_LAYOUT) === 4) {
            $palette['button']['background'] = Configuration::get(static::DISPLAY_PALETTE_BUTTON_TEXT);
            $palette['button']['text'] = Configuration::get(static::DISPLAY_PALETTE_BUTTON);
            $palette['button']['border'] = Configuration::get(static::DISPLAY_PALETTE_BUTTON);
        }

        return $palette;
    }

    /**
     * @return array
     *
     * @since 1.0.0
     */
    protected function getDisplayOptions()
    {
        return [
            'form' => [
                'legend' => [
                    'title' => $this->l('Display Settings'),
                    'icon'  => 'icon-desktop',
                ],
                'input'  => [
                    [
                        'type'   => 'radio',
                        'label'  => $this->l('Position'),
                        'name'   => static::DISPLAY_POSITION,
                        'values' => [
                            [
                                'id'    => 'banner',
                                'value' => 1,
                                'label' => $this->l('Banner bottom'),
                            ],
                            [
                                'id'    => 'banner_top',
                                'value' => 2,
                                'label' => $this->l('Banner top'),
                            ],
                            [
                                'id'    => 'banner_top_pushdown',
                                'value' => 3,
                                'label' => $this->l('Banner top (pushdown)'),
                            ],
                            [
                                'id'    => 'floating_left',
                                'value' => 4,
                                'label' => $this->l('Floating left'),
                            ],
                            [
                                'id'    => 'floating_right',
                                'value' => 5,
                                'label' => $this->l('Floating right'),
                            ],
                        ],
                    ],
                    [
                        'type'   => 'radio',
                        'label'  => $this->l('Layout'),
                        'name'   => static::DISPLAY_LAYOUT,
                        'values' => [
                            [
                                'id'    => 'block',
                                'value' => 1,
                                'label' => $this->l('Block'),
                            ],
                            [
                                'id'    => 'edgeless',
                                'value' => 2,
                                'label' => $this->l('Edgeless'),
                            ],
                            [
                                'id'    => 'classic',
                                'value' => 3,
                                'label' => $this->l('Classic'),
                            ],
                            [
                                'id'    => 'wire',
                                'value' => 4,
                                'label' => $this->l('Wire'),
                            ],
                        ],
                    ],
                    [
                        'type'  => 'color',
                        'label' => $this->l('Banner color'),
                        'name'  => static::DISPLAY_PALETTE_BANNER,
                    ],
                    [
                        'type'  => 'color',
                        'label' => $this->l('Banner text color'),
                        'name'  => static::DISPLAY_PALETTE_BANNER_TEXT,
                    ],
                    [
                        'type'  => 'color',
                        'label' => $this->l('Button color'),
                        'name'  => static::DISPLAY_PALETTE_BUTTON,
                    ],
                    [
                        'type'  => 'color',
                        'label' => $this->l('Button text color'),
                        'name'  => static::DISPLAY_PALETTE_BUTTON_TEXT,
                    ],
                    [
                        'type'  => 'text',
                        'label' => $this->l('Learn more link'),
                        'name'  => static::DISPLAY_LEARN_MORE_LINK,
                        'lang'  => true,
                    ],
                    [
                        'type'  => 'textarea',
                        'label' => $this->l('Message'),
                        'name'  => static::DISPLAY_MESSAGE_TEXT,
                        'lang'  => true,
                    ],
                    [
                        'type'  => 'text',
                        'label' => $this->l('Dismiss button text'),
                        'name'  => static::DISPLAY_DISMISS_TEXT,
                        'lang'  => true,
                    ],
                    [
                        'type'  => 'text',
                        'label' => $this->l('Policy link text'),
                        'name'  => static::DISPLAY_POLICY_LINK_TEXT,
                        'lang'  => true,
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ],
            ],
        ];
    }

    /**
     * @param null|int $idLang
     *
     * @return array
     *
     * @since 1.0.0
     */
    protected function getWidgetContent($idLang = null)
    {
        if (!$idLang) {
            $idLang = (int) Context::getContext()->language->id;
        }

        $content = [
            'message' => Configuration::get(static::DISPLAY_MESSAGE_TEXT, $idLang),
            'dismiss' => Configuration::get(static::DISPLAY_DISMISS_TEXT, $idLang),
            'link'    => Configuration::get(static::DISPLAY_POLICY_LINK_TEXT, $idLang),
        ];

        if (Configuration::get(static::DISPLAY_LEARN_MORE_LINK, $idLang)) {
            $content['href'] = Configuration::get(static::DISPLAY_LEARN_MORE_LINK, $idLang);
        }

        return $content;
    }
}
