<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\UI\PaellaConfig;

use ILIAS\UI\Component\Input\Container\Form\Standard;
use ILIAS\UI\Component\Input\Field\UploadHandler;
use ilPlugin;
use srag\Plugins\Opencast\Model\Config\PluginConfig;
use srag\Plugins\Opencast\Util\FileTransfer\PaellaConfigStorageService;
use ILIAS\UI\Factory;
use ILIAS\UI\Renderer;
use ILIAS\UI\Component\Input\Field\SwitchableGroup;

class PaellaConfigFormBuilder
{
    // Paella Player Path
    public const F_PAELLA_PLAYER_OPTION = 'paella_player_option';
    public const F_PAELLA_PLAYER_DEFAULT = 'pp_default';
    public const F_PAELLA_PLAYER_IMAGE = 'pp_image';
    public const F_PAELLA_PLAYER_FILE = 'pp_file';
    public const F_PAELLA_PLAYER_LINK = 'pp_link';
    //Paella Player Themes.
    public const F_PAELLA_PLAYER_THEME = 'paella_player_theme';
    public const F_PAELLA_PLAYER_LIVE_THEME = 'paella_player_live_theme';

    // Preview fallback.
    public const F_PAELLA_PLAYER_PREVIEW_FALLBACK = 'paella_player_preview_fallback';
    // Paella Player Caption Settings
    public const F_PAELLA_PLAYER_FALLBACK_CAPTIONS_OPTION = 'paella_player_fallback_captions_option';
    // Paella Player language Settings
    public const F_PAELLA_PLAYER_FALLBACK_LANGS_OPTION = 'paella_player_fallback_langs_option';
    /**
     * @var ilPlugin
     */
    private $plugin;
    /**
     * @var PaellaConfigStorageService
     */
    private $paellaStorageService;
    /**
     * @var Factory
     */
    private $ui_factory;

    /**
     * @var Renderer
     */
    private $ui_renderer;

    public function __construct(
        ilPlugin $plugin,
        UploadHandler $fileUploadHandler,
        PaellaConfigStorageService $paellaStorageService,
        Factory $ui_factory,
        Renderer $ui_renderer
    ) {
        $this->plugin = $plugin;
        $this->paellaStorageService = $paellaStorageService;
        $this->ui_factory = $ui_factory;
        $this->ui_renderer = $ui_renderer;
    }

    public function buildForm(string $form_action): Standard
    {
        $inputs = [];
        $inputs[self::F_PAELLA_PLAYER_OPTION] = $this->generateSwichableGroupWithUrl(
            $this->ui_renderer->render(
                $this->ui_factory->link()->standard(
                    $this->plugin->txt(self::F_PAELLA_PLAYER_DEFAULT . "_link"),
                    PluginConfig::PAELLA_DEFAULT_PATH
                )
            ),
            PluginConfig::getConfig(PluginConfig::F_PAELLA_OPTION) ?? PluginConfig::PAELLA_OPTION_DEFAULT,
            PluginConfig::getConfig(PluginConfig::F_PAELLA_URL) ?? '',
            self::F_PAELLA_PLAYER_OPTION,
            true
        );

        $inputs[self::F_PAELLA_PLAYER_THEME] = $this->generateSwichableGroupWithUrl(
            $this->ui_renderer->render(
                $this->ui_factory->link()->standard(
                    $this->plugin->txt(self::F_PAELLA_PLAYER_DEFAULT . "_link"),
                    PluginConfig::PAELLA_DEFAULT_THEME
                )
            ),
            PluginConfig::getConfig(PluginConfig::F_PAELLA_THEME) ?? PluginConfig::PAELLA_OPTION_DEFAULT,
            PluginConfig::getConfig(PluginConfig::F_PAELLA_THEME_URL) ?? '',
            self::F_PAELLA_PLAYER_THEME,
            true
        );

        $live_theme_url = PluginConfig::PAELLA_DEFAULT_THEME_LIVE;
        // Toggle the live theme path when it is buffered capable.
        if (PluginConfig::getConfig(PluginConfig::F_LIVESTREAM_BUFFERED)) {
            $live_theme_url = PluginConfig::PAELLA_DEFAULT_THEME_LIVE_BUFFERED;
        }
        $inputs[self::F_PAELLA_PLAYER_LIVE_THEME] = $this->generateSwichableGroupWithUrl(
            $this->ui_renderer->render(
                $this->ui_factory->link()->standard(
                    $this->plugin->txt(self::F_PAELLA_PLAYER_DEFAULT . "_link"),
                    $live_theme_url
                )
            ),
            PluginConfig::getConfig(PluginConfig::F_PAELLA_THEME_LIVE) ?? PluginConfig::PAELLA_OPTION_DEFAULT,
            PluginConfig::getConfig(PluginConfig::F_PAELLA_THEME_URL_LIVE) ?? '',
            self::F_PAELLA_PLAYER_LIVE_THEME,
            true
        );

        $inputs[self::F_PAELLA_PLAYER_PREVIEW_FALLBACK] = $this->generateSwichableGroupWithUrl(
            $this->ui_renderer->render(
                $this->ui_factory->link()->standard(
                    $this->plugin->txt(self::F_PAELLA_PLAYER_IMAGE . "_link"),
                    PluginConfig::PAELLA_DEFAULT_PREVIEW
                )
            ),
            PluginConfig::getConfig(PluginConfig::F_PAELLA_PREVIEW_FALLBACK) ?? PluginConfig::PAELLA_OPTION_DEFAULT,
            PluginConfig::getConfig(PluginConfig::F_PAELLA_PREVIEW_FALLBACK_URL) ?? '',
            self::F_PAELLA_PLAYER_PREVIEW_FALLBACK,
            false
        );

        $availableLanguages = $this->getAvailablePlayerLanguages();
        $defaultLanuages = PluginConfig::getConfig(PluginConfig::F_PAELLA_FALLBACK_LANGS) ?? [];
        $inputs[self::F_PAELLA_PLAYER_FALLBACK_LANGS_OPTION] = $this->ui_factory->input()->field()
                                                                                ->tag(
                                                                                    $this->txt(
                                                                                        self::F_PAELLA_PLAYER_FALLBACK_LANGS_OPTION
                                                                                    ),
                                                                                    array_keys($availableLanguages),
                                                                                    $this->txt(
                                                                                        self::F_PAELLA_PLAYER_FALLBACK_LANGS_OPTION . '_info'
                                                                                    )
                                                                                )
                                                                                ->withUserCreatedTagsAllowed(true)
                                                                                ->withValue($defaultLanuages);

        $commonCaptions = ['de', 'en'];
        $defaultCaptions = PluginConfig::getConfig(PluginConfig::F_PAELLA_FALLBACK_CAPTIONS) ?? [];
        $inputs[self::F_PAELLA_PLAYER_FALLBACK_CAPTIONS_OPTION] = $this->ui_factory->input()->field()
                                                                                   ->tag(
                                                                                       $this->txt(
                                                                                           self::F_PAELLA_PLAYER_FALLBACK_CAPTIONS_OPTION
                                                                                       ),
                                                                                       $commonCaptions,
                                                                                       $this->txt(
                                                                                           self::F_PAELLA_PLAYER_FALLBACK_CAPTIONS_OPTION . '_info'
                                                                                       )
                                                                                   )
                                                                                   ->withUserCreatedTagsAllowed(true)
                                                                                   ->withValue($defaultCaptions);

        return $this->ui_factory->input()->container()->form()->standard(
            $form_action,
            $inputs
        );
    }

    private function generateSwichableGroupWithUrl(
        string $link,
        string $option,
        string $url,
        string $text,
        bool $required
    ): SwitchableGroup {
        $f = $this->ui_factory->input()->field();
        return $f->switchableGroup([
            PluginConfig::PAELLA_OPTION_DEFAULT => $f->group([], $this->plugin->txt("pp_default_string") . " " . $link),
            PluginConfig::PAELLA_OPTION_URL => $f->group(
                [
                    'url' => $f->text($this->plugin->txt('link'))
                               ->withByline($this->plugin->txt('pp_link_info'))
                               ->withRequired(true)
                               ->withValue($url)
                ],
                $this->plugin->txt('pp_url')
            )
        ], $this->txt($text))
                 ->withByline($this->txt($text . '_info'))
                 ->withValue($option)
                 ->withRequired($required);
    }

    private function buildInlineDownload(string $file_id): string
    {
        if (!$file_id || !$this->paellaStorageService->exists($file_id)) {
            return '';
        }
        $fileAsBase64 = $this->paellaStorageService->getFileAsBase64($file_id);
        $fileInfo = $this->paellaStorageService->getFileInfo($file_id);
        return '<a href="data:text/vtt;base64,'
            . $fileAsBase64
            . '" target="blank" download="' . $fileInfo['name'] . '">Download</a>';
    }

    private function txt(string $string): string
    {
        return $this->plugin->txt('config_' . $string);
    }

    private function getAvailablePlayerLanguages(): array
    {
        // Default languages of the paella player are en (default) and es.
        $languages = [
            'en' => 'en',
            'es' => 'es'
        ];
        foreach (scandir(PluginConfig::PAELLA_LANG_PATH) as $langFile) {
            if ('.' === $langFile || '..' === $langFile) {
                continue;
            }
            $ext = pathinfo($langFile, PATHINFO_EXTENSION);
            $langName = pathinfo($langFile, PATHINFO_FILENAME);
            if ($ext === 'json') {
                $languages[$langName] = $langName;
            }
        }
        return array_reverse($languages);
    }
}
