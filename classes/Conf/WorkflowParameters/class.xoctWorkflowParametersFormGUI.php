<?php

use srag\CustomInputGUIs\OpenCast\PropertyFormGUI\PropertyFormGUI;
use srag\Plugins\Opencast\Model\Config\PluginConfig;
use srag\Plugins\Opencast\LegacyHelpers\TranslatorTrait;

/**
 * Class xoctWorkflowParametersFormGUI
 *
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class xoctWorkflowParametersFormGUI extends PropertyFormGUI
{
    use TranslatorTrait;
    public const PLUGIN_CLASS_NAME = ilOpenCastPlugin::class;

    public const PROPERTY_TITLE = 'setTitle';
    public const PROPERTY_INFO = 'setInfo';

    public const F_OVERWRITE_SERIES_PARAMS = 'overwrite_series_params';

    /**
     * @var xoctWorkflowParameterGUI
     */
    protected $parent;

    /**
     * @return mixed|void
     */
    protected function getValue(string $key)
    {
    }

    protected function initCommands(): void
    {
        $this->addCommandButton(xoctWorkflowParameterGUI::CMD_UPDATE_FORM, $this->lng->txt('save'));
    }

    protected function initFields(): void
    {
        $this->fields[PluginConfig::F_ALLOW_WORKFLOW_PARAMS_IN_SERIES] = [
            self::PROPERTY_TITLE => $this->translate(
                PluginConfig::F_ALLOW_WORKFLOW_PARAMS_IN_SERIES,
                'config'
            ),
            self::PROPERTY_CLASS => ilCheckboxInputGUI::class,
            self::PROPERTY_VALUE => (bool) PluginConfig::getConfig(PluginConfig::F_ALLOW_WORKFLOW_PARAMS_IN_SERIES),
            self::PROPERTY_SUBITEMS => [
                self::F_OVERWRITE_SERIES_PARAMS => [
                    self::PROPERTY_TITLE => $this->translate(self::F_OVERWRITE_SERIES_PARAMS, 'config'),
                    self::PROPERTY_INFO => $this->translate(
                        self::F_OVERWRITE_SERIES_PARAMS . '_info',
                        'config'
                    ),
                    self::PROPERTY_CLASS => ilCheckboxInputGUI::class,
                ]
            ]
        ];
    }

    /**
     *
     */
    protected function initId(): void
    {
    }

    /**
     *
     */
    protected function initTitle(): void
    {
        $this->setTitle($this->translate('settings', 'tab'));
    }

    /**
     * @param mixed $value
     */
    protected function storeValue(string $key, $value): void
    {
        switch ($key) {
            case PluginConfig::F_ALLOW_WORKFLOW_PARAMS_IN_SERIES:
                PluginConfig::set(PluginConfig::F_ALLOW_WORKFLOW_PARAMS_IN_SERIES, $value);
                break;
            case self::F_OVERWRITE_SERIES_PARAMS:
                if ($value == true) {
                    $this->parent->setOverwriteSeriesParameter();
                }
                break;
            default:
                break;
        }
    }
}
