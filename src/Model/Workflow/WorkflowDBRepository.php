<?php

namespace srag\Plugins\Opencast\Model\Workflow;
use srag\Plugins\Opencast\API\API;
use srag\Plugins\Opencast\LegacyHelpers\TranslatorTrait;
use srag\Plugins\Opencast\Model\Config\PluginConfig;

class WorkflowDBRepository implements WorkflowRepository
{
    use TranslatorTrait;

    public const SELECTION_TEXT_LANG_MODULE = 'workflow_selection_text';
    public const CONFIG_PANEL_LABEL_LANG_MODULE = 'workflow_config_panel_label';
    /**
     * @var API
     */
    protected $api;

    public function __construct()
    {
        global $opencastContainer;
        $this->api = $opencastContainer[API::class];
    }

    public function anyWorkflowExists(): bool
    {
        return (WorkflowAR::count() > 0);
    }

    public function anyWorkflowAvailable(): bool
    {
        return (count($this->getFilteredWorkflowsArray()) > 0);
    }

    public function getAllWorkflows(): array
    {
        return WorkflowAR::get();
    }

    public function getAllWorkflowsAsArray($key = null, $values = null): array
    {
        return WorkflowAR::getArray($key, $values);
    }

    public function store(string $workflow_id, string $title, string $description,
        string $tags, string $roles, string $config_panel, int $id = 0): void
    {
        /** @var WorkflowAR $workflow */
        $workflow = new WorkflowAR($id == 0 ? null : $id);
        $workflow->setWorkflowId($workflow_id);
        $workflow->setTitle($title);
        $workflow->setDescription($description);
        if ($id == 0) {
            $workflow->setTags($tags);
            $workflow->setRoles($roles);
            $workflow->setConfigPanel($config_panel);
        } else {
            if (!empty($tags)) {
                $workflow->setTags($tags);
            }
            if (!empty($roles)) {
                $workflow->setRoles($roles);
            }
            if (!empty($config_panel)) {
                $workflow->setConfigPanel($config_panel);
            }
        }

        $workflow->store();
    }

    public function exists(string $workflow_id): bool
    {
        return WorkflowAR::where(['workflow_id' => $workflow_id])->hasSets();
    }

    public function delete($id): void
    {
        $workflow = WorkflowAR::find($id);
        $workflow->delete();
    }

    public function getByWorkflowId(string $workflow_id)
    {
        return WorkflowAR::where(['workflow_id' => $workflow_id])->first();
    }

    public function getById(int $id)
    {
        return WorkflowAR::where(['id' => $id])->first();
    }

    public function getConfigPanelAsArrayById(string $id): array
    {
        $config_panel_array = [];
        $workflow = $this->getById($id);
        $configuration_panel_html = $workflow->getConfigPanel();
        $configuration_panel_html = trim(str_replace("\n", "", $configuration_panel_html));
        if (!empty($configuration_panel_html)) {
            $dom = new \DOMDocument();
            $dom->strictErrorChecking = false;
            $dom->loadHTML($configuration_panel_html, LIBXML_NOCDATA|LIBXML_NOWARNING|LIBXML_NOERROR);
            $inputs = $dom->getElementsByTagName('input');
            $selects = $dom->getElementsByTagName('select');

            foreach ($inputs as $input) {
                $key = '';
                $value = '';
                $type = '';
                if ($input->hasAttribute('type')) {
                    $type = $input->getAttribute('type');
                }
                if ($input->hasAttribute('name')) {
                    $key = $input->getAttribute('name');
                } else if ($input->hasAttribute('id')) {
                    $key = $input->getAttribute('id');
                }

                if ($input->hasAttribute('value')) {
                    $value = $input->getAttribute('value');
                }

                if (!empty($key)) {
                    $value = ($type == 'checkbox') ?
                        ($value == 'true' ? true : false) :
                        trim($value);
                    $config_panel_array[$key] = [
                        'value' => $value,
                        'type' => $type
                    ];
                }
            }

            foreach ($selects as $select) {
                $key = '';
                $value = '';
                if ($input->hasAttribute('name')) {
                    $key = $input->getAttribute('name');
                } else if ($input->hasAttribute('id')) {
                    $key = $input->getAttribute('id');
                }

                if ($input->hasAttribute('value')) {
                    $value = $input->getAttribute('value');
                }

                if (!empty($key)) {
                    $config_panel_array[$key] = [
                        'value' => trim($value),
                        'type' => 'select'
                    ];
                }
            }
        }
        return $config_panel_array;
    }

    public function getWorkflowsFromOpencastApi(array $filter = [], bool $with_configuration_panel = false,
        bool $with_tags = false): array
    {
        $workflows = $this->api->routes()->workflowsApi->getAllDefinitions([
            'withconfigurationpanel' => $with_configuration_panel,
            'filter' => $filter,
        ]);
        if ($with_tags) {
            return array_filter($workflows, function ($workflow) {
                return !empty($workflow->tags);
            });
        }
        return $workflows;
    }

    public function updateList(?string $tags_str = null, ?string $roles_str = null): bool
    {
        $oc_workflows_all = $this->getWorkflowsFromOpencastApi([], true, true);
        $filtered_oc_workflows = $this->getFilteredWorkflowsArray($oc_workflows_all, $tags_str, $roles_str);
        $filtered_oc_workflows_ids = array_keys($filtered_oc_workflows);
        $current_workflows = $this->getAllWorkflowsAsArray('workflow_id');
        $current_workflows_ids = array_keys($current_workflows);

        // First remove from workflowsAR list.
        foreach ($current_workflows as $cr_wf_id => $cr_wf) {
            if (!in_array($cr_wf_id, $filtered_oc_workflows_ids)) {
                $this->delete($cr_wf['id']);
            }
        }

        // Then, update the list if it is not there, without touching the current ones
        foreach ($filtered_oc_workflows as $oc_wd_id => $oc_wf) {
            // Exists, we check the diffs!
            if (in_array($oc_wd_id, $current_workflows_ids)) {
                $current_workflow = $this->getByWorkflowId($oc_wd_id);
                // Check the configuration panel changes only.
                $current_config_panel = json_encode(
                    str_replace("\r\n", "\n", trim($current_workflow->getConfigPanel()))
                );
                $new_config_panel = json_encode(
                    str_replace("\r\n", "\n", trim($oc_wf->configuration_panel))
                );
                if (!strcmp($current_config_panel, $new_config_panel)) {
                    continue;
                }
                $configuration_panel = !empty($oc_wf->configuration_panel) ? $oc_wf->configuration_panel : '';
                $current_workflow->setConfigPanel($configuration_panel);
                $current_workflow->store();
            } else {
                // Otherwise, we save it new!
                $title = isset($oc_wf->title) ? trim($oc_wf->title) : '';
                $description = isset($oc_wf->description) ? trim($oc_wf->description) : '';
                $tags = isset($oc_wf->tags) ? implode(',', $oc_wf->tags) : '';
                $roles = isset($oc_wf->roles) ? implode(',', $oc_wf->roles) : '';
                $configuration_panel = !empty($oc_wf->configuration_panel) ? $oc_wf->configuration_panel : '';
                $this->createOrUpdate($oc_wd_id, $title, $description, $tags, $roles, $configuration_panel);
            }
        }

        $success = WorkflowAR::count() === count($filtered_oc_workflows_ids);

        // Rolling back.
        if (!$success) {
            WorkflowAR::flushDB();
            foreach ($current_workflows as $workflow) {
                $new_workflow = new WorkflowAR(null);
                $new_workflow->setWorkflowId($workflow->getWorkflowId());
                $new_workflow->setTitle($workflow->getTitle());
                $new_workflow->setDescription($workflow->getDescription());
                $new_workflow->setTags($workflow->getTags());
                $new_workflow->setRoles($workflow->getRoles());
                $new_workflow->setConfigPanel($workflow->getConfigPanel());
                $new_workflow->store();
            }
        }

        return $success;
    }

    public function resetList(): bool
    {
        $oc_workflows_all = $this->getWorkflowsFromOpencastApi([], true, true);
        $filtered_oc_workflows = $this->getFilteredWorkflowsArray($oc_workflows_all);
        $filtered_oc_workflows_ids = array_keys($filtered_oc_workflows);
        $current_workflows = $this->getAllWorkflowsAsArray('workflow_id');
        $current_workflows_ids = array_keys($current_workflows);

        // Flushing everything first.
        WorkflowAR::flushDB();

        // Then, add the new ones one by one!
        foreach ($filtered_oc_workflows as $oc_wd_id => $oc_wf) {
            // Otherwise, we save it new!
            $title = isset($oc_wf->title) ? trim($oc_wf->title) : '';
            $description = isset($oc_wf->description) ? trim($oc_wf->description) : '';
            $tags = isset($oc_wf->tags) ? implode(',', $oc_wf->tags) : '';
            $roles = isset($oc_wf->roles) ? implode(',', $oc_wf->roles) : '';
            $configuration_panel = !empty($oc_wf->configuration_panel) ? $oc_wf->configuration_panel : '';
            $this->createOrUpdate($oc_wd_id, $title, $description, $tags, $roles, $configuration_panel);
        }

        $success = WorkflowAR::count() === count($filtered_oc_workflows_ids);

        // Rolling back.
        if (!$success) {
            WorkflowAR::flushDB();
            foreach ($current_workflows as $workflow) {
                $new_workflow = new WorkflowAR(null);
                $new_workflow->setWorkflowId($workflow->getWorkflowId());
                $new_workflow->setTitle($workflow->getTitle());
                $new_workflow->setDescription($workflow->getDescription());
                $new_workflow->setTags($workflow->getTags());
                $new_workflow->setRoles($workflow->getRoles());
                $new_workflow->setConfigPanel($workflow->getConfigPanel());
                $new_workflow->store();
            }
        }

        return $success;
    }

    public function createOrUpdate(string $workflow_id, string $title, string $description, string $tags = '', string $roles = '',
        string $config_panel = ''): WorkflowAR
    {
        $id = 0;
        if ($this->exists($workflow_id)) {
            $workflow = $this->getByWorkflowId($workflow_id);
            $id = $workflow->getId();
        }

        $this->store($workflow_id, $title, $description, $tags, $roles, $config_panel, $id);

        $new_workflow = $this->getByWorkflowId($workflow_id);
        return $new_workflow;
    }

    private function commaToArray($comma_separated_str): array
    {
        $converted_list = [];
        if (!empty($comma_separated_str)) {
            $converted_list = explode(',', $comma_separated_str);
            $converted_list = array_map('trim', $converted_list);
        }
        return $converted_list;
    }

    private function hasItem($base, $check): bool
    {
        foreach ($base as $item) {
            if (in_array($item, $check)) {
                return true;
            }
        }
        return false;
    }

    public function getFilteredWorkflowsArray(
        array $workflows = [],
        ?string $tags_str = null,
        ?string $roles_str = null): array
    {
        $filtered_list = [];

        $tags_to_include = PluginConfig::getConfig(PluginConfig::F_WORKFLOWS_TAGS) ?? '';
        if (!is_null($tags_str)) {
            $tags_to_include = $tags_str;
        }
        $tags_to_include_arr = $this->commaToArray($tags_to_include);
        $tags_to_include_arr = array_filter($tags_to_include_arr, function ($tag) {
            return !empty(trim($tag));
        });
        // Disable the feature if tags list is empty.
        if (empty($tags_to_include_arr)) {
            return [];
        }

        $roles_to_exclude = PluginConfig::getConfig(PluginConfig::F_WORKFLOWS_EXCLUDE_ROLES) ?? '';
        if (!is_null($roles_str)) {
            $roles_to_exclude = $roles_str;
        }
        $roles_to_exclude_arr = $this->commaToArray($roles_to_exclude);
        $roles_to_exclude_arr = array_filter($roles_to_exclude_arr, function ($role) {
            return !empty(trim($role));
        });

        // If the list is empty, then we get all currect ones from WorkflowAR
        if (empty($workflows)) {
            $workflows = $this->getAllWorkflows();
        }

        foreach ($workflows as $workflow) {
            $tags_array = [];
            $roles_array = [];
            $workflow_id = '';
            if ($workflow instanceof WorkflowAR) {
                $tags_array = $this->commaToArray($workflow->getTags());
                $roles_array = $this->commaToArray($workflow->getRoles());
                $workflow_id = $workflow->getWorkflowId();
            } else {
                $workflow_id = $workflow->identifier;
                if (isset($workflow->tags)) {
                    $tags_array = $workflow->tags;
                }
                if (isset($workflow->roles)) {
                    $roles_array = $workflow->roles;
                }
            }

            if (empty($tags_array)) {
                continue;
            }

            if ($this->hasItem($tags_array, $tags_to_include_arr) === false) {
                continue;
            }

            if ($this->hasItem($roles_array, $roles_to_exclude_arr) === true) {
                continue;
            }

            $filtered_list[$workflow_id] = $workflow;
        }

        return $filtered_list;
    }

    public function buildWorkflowSelectOptions(): string
    {
        $options = [
            '<option value="">' . $this->translate('empty_select_option', 'workflow') . '</option>'
        ];
        foreach ($this->getFilteredWorkflowsArray() as $workflow) {
            $title = $workflow->getTitle();
            $workflow_record_id = $workflow->getId();
            $translated_text = $this->translate($workflow_record_id, self::SELECTION_TEXT_LANG_MODULE);
            if (strpos($translated_text, 'MISSING') === false) {
                $title = $translated_text;
            }
            $options[] = "<option value='{$workflow_record_id}'>{$title}</option>";
        }
        return implode("\n", $options);
    }

    public function parseConfigPanels(): array
    {
        $config_panels = [];
        foreach ($this->getFilteredWorkflowsArray() as $workflow) {
            $configuration_panel_html = $workflow->getConfigPanel();
            $id = $workflow->getId();
            if (!empty(trim($configuration_panel_html))) {
                $config_panels[$id] = $this->mapConfigPanelElements($id, $configuration_panel_html);
            }
        }
        return $config_panels;
    }

    private function mapConfigPanelElements(string $workflow_id, string $configuration_panel_html): string
    {
        $dom = new \DOMDocument();
        $dom->strictErrorChecking = false;
        $configuration_panel_html = trim(str_replace("\n", "", $configuration_panel_html));
        $mapped_configuration_panel_html = $configuration_panel_html;

        if (strlen($configuration_panel_html) > 0) {
            $dom->loadHTML($configuration_panel_html, LIBXML_NOCDATA|LIBXML_NOWARNING|LIBXML_NOERROR);
            $inputs = $dom->getElementsByTagName('input');
            $selects = $dom->getElementsByTagName('select');
            $labels = $dom->getElementsByTagName('label');
            $uls = $dom->getElementsByTagName('ul');
            $legends = $dom->getElementsByTagName('legend');
            $main_div = $dom->getElementById('workflow-configuration');

            if ($main_div) {
                $main_div->setAttribute('id', "{$workflow_id}_workflow-configuration");
            }

            $ids = [];
            $names = [];
            $defaults = [];
            $required = [];

            // Legends replacements. We need to legend to be displayed in there!
            foreach ($legends as $legend) {
                $text = $legend->textContent;
                $h3 = $dom->createElement('h3');
                $h3->textContent = $text;
                $legend->setAttribute('class', 'hidden');
                $legend->parentNode->insertBefore($h3, $legend);
            }

            // Stylings and classes of ul li elements.
            foreach ($uls as $ul) {
                if ($ul->hasAttribute('style')) {
                    $ul->removeAttribute('style');
                }

                $classes = ['noStyle', 'flex-col'];
                if ($ul->hasAttribute('class')) {
                    $current_classes = $ul->getAttribute('class');
                    $current_classes = explode(' ', $current_classes);
                    foreach ($current_classes as $current_class) {
                        if (!in_array($current_class, $classes)) {
                            $classes[] = $current_class;
                        }
                    }
                }
                $ul->setAttribute('class', implode(' ', $classes));

                // Items.
                $lis = $ul->getElementsByTagName('li');
                foreach ($lis as $li) {
                    if ($li->hasAttribute('style')) {
                        // Removing all the default styles.
                        $li->removeAttribute('style');
                    }
                    $classes = ['row-flex'];
                    if ($li->hasAttribute('class')) {
                        $current_classes = $li->getAttribute('class');
                        $current_classes = explode(' ', $current_classes);
                        foreach ($current_classes as $current_class) {
                            if (!in_array($current_class, $classes)) {
                                $classes[] = $current_class;
                            }
                        }
                    }
                    $li->setAttribute('class', implode(' ', $classes));
                }
            }

            // Replace the 'id' and 'name' attributes for input elements
            foreach ($inputs as $input) {
                if ($input->hasAttribute('id')) {
                    $old_id = $input->getAttribute('id');
                    $new_id = "{$workflow_id}_{$old_id}";
                    $ids[$old_id] = $new_id;
                    $input->setAttribute('id', $new_id);
                }
                // Make sure there the name is replaced.
                if ($input->hasAttribute('name')) {
                    $old_name = $input->getAttribute('name');
                    $new_name = "{$workflow_id}[{$old_name}]";
                    $names[$old_name] = $new_name;
                    $input->setAttribute('name', $new_name);
                }
                $classes = ['wf-inputs'];
                if ($input->hasAttribute('class')) {
                    $classes[] = $input->getAttribute('class');
                }
                $input->setAttribute('class', implode(' ', $classes));

                // We need to inline styles.
                if ($input->hasAttribute('style')) {
                    $input->removeAttribute('style');
                }

                if ($input->hasAttribute('value')) {
                    $default_value = $input->getAttribute('value');
                    $default_id = "{$new_id}_default";
                    $hidden_input_default = $dom->createElement('input');
                    $hidden_input_default->setAttribute('type', 'hidden');
                    $hidden_input_default->setAttribute('id', $default_id);
                    $hidden_input_default->setAttribute('value', trim($default_value));
                    $defaults[] = $hidden_input_default;
                }

                if ($input->hasAttribute('required')) {
                    $required[] = $new_id;
                }
            }

            // Replace the 'id' and 'name' attributes for select elements
            foreach ($selects as $select) {
                if ($select->hasAttribute('id')) {
                    $old_id = $select->getAttribute('id');
                    $new_id = "{$workflow_id}_{$old_id}";
                    $ids[$old_id] = $new_id;
                    $select->setAttribute('id', $new_id);
                }
                if ($select->hasAttribute('name')) {
                    $old_name = $select->getAttribute('name');
                    $new_name = "{$workflow_id}[{$old_name}]";
                    $names[$old_name] = $new_name;
                    $select->setAttribute('name', $new_name);
                }
                $classes = ['wf-inputs'];
                if ($select->hasAttribute('class')) {
                    $classes[] = $select->getAttribute('class');
                }
                $select->setAttribute('class', implode(' ', $classes));

                // We need to inline styles.
                if ($select->hasAttribute('style')) {
                    $select->removeAttribute('style');
                }

                if ($select->hasAttribute('value')) {
                    $default_value = $select->getAttribute('value');
                    $default_id = "{$new_id}_default";
                    $hidden_input_default = $dom->createElement('input');
                    $hidden_input_default->setAttribute('type', 'hidden');
                    $hidden_input_default->setAttribute('id', $default_id);
                    $hidden_input_default->setAttribute('value', trim($default_value));
                    $defaults[] = $hidden_input_default;
                }

                if ($select->hasAttribute('required')) {
                    $required[] = $new_id;
                }
            }


            // Replace the 'for' attribute and translate the text for label elements
            foreach ($labels as $label) {
                $label_text = $label->nodeValue;
                if ($label->hasAttribute('for')) {
                    $for = $label->getAttribute('for');
                    if (isset($ids[$for])) {
                        $label->setAttribute('for', $ids[$for]);
                    }
                    $translated_label = $this->translate($for, self::CONFIG_PANEL_LABEL_LANG_MODULE);
                    if (strpos($translated_label, 'MISSING') === false) {
                        $label_text = $translated_label;
                    }
                }
                $label->nodeValue = $label_text;

                $classes = ['control-label'];
                if ($label->parentNode->tagName !== 'li') {
                    $classes[] = 'col-sm-3';
                    $classes[] = 'configLabel';
                } else {
                    $classes[] = 'configListLabel';
                }
                if ($label->hasAttribute('class')) {
                    $classes[] = $label->getAttribute('class');
                }
                $label->setAttribute('class', implode(' ', $classes));

                // We need to inline styles.
                if ($label->hasAttribute('style')) {
                    $label->removeAttribute('style');
                }

                if (isset($for) && in_array($ids[$for], $required)) {
                    $required_span = $dom->createElement('span');
                    $required_span->setAttribute('class', 'asterisk');
                    $required_span->textContent = ' * ';
                    $label->appendChild($required_span);
                }
            }

            foreach ($defaults as $hidden_input_default) {
                $dom->appendChild($hidden_input_default);
            }


            $modified = $dom->saveHTML();
            foreach ($ids as $old_id => $new_id) {
                $modified = str_replace(
                    ["#{$old_id}", 'getElementById("{$old_id}")', "getElementById('{$old_id}')"],
                    ["#{$new_id}", 'getElementById("{$new_id}")', "getElementById('{$new_id}')"],
                    $modified
                );
            }

            foreach ($names as $old_name => $new_name) {
                $modified = str_replace(
                    ["name={$old_name}", 'name="{$old_name}"', "name='{$old_name}'",
                        'getElementsByName("{$old_name}")', "getElementsByName('{$old_name}')", 'getElementsByName({$old_name})'],
                    ["name*={$old_name}", 'name*="{$old_name}"', "name*='{$old_name}'",
                        'getElementsByName("{$new_name}")', "getElementsByName('{$new_name}')", 'getElementsByName({$new_name})'],
                    $modified
                );
            }

            if (!empty($modified)) {
                $mapped_configuration_panel_html = $modified;
            }
        }

        return $mapped_configuration_panel_html;
    }
}
