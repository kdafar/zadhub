<?php

namespace App\Services;

class FlowRenderer
{
    public function renderScreen(array $screenConfig, array $sessionData = [], ?string $errorMessage = null): array
    {
        $dataBindings = [];
        $children = [];

        // This powerful function will find any placeholder like {variable_name}
        // and replace it with the corresponding value from the session data.
        $replaceVariables = function ($string) use ($sessionData) {
            if (! is_string($string)) {
                return $string;
            }

            return preg_replace_callback('/\{(\w+)\}/', function ($matches) use ($sessionData) {
                return $sessionData[$matches[1]] ?? $matches[0]; // If variable not found, leave it as is
            }, $string);
        };

        // Add error message display if one exists
        if ($errorMessage) {
            $children[] = [
                'type' => 'TextBody',
                'text' => '⚠️ '.$errorMessage,
                'markdown' => true,
            ];
        }

        foreach ($screenConfig['children'] ?? [] as $componentConfig) {
            $componentType = $componentConfig['type'];
            // Apply variable replacement to all fields within the component's data
            $componentData = array_map($replaceVariables, $componentConfig['data']);

            switch ($componentType) {
                case 'text_body':
                    $children[] = [
                        'type' => 'TextBody',
                        'text' => $componentData['text'],
                        'markdown' => $componentData['markdown'] ?? false,
                    ];
                    break;

                case 'dropdown':
                    $optionsKey = $componentData['name'].'_options';
                    $dataBindings[$optionsKey] = array_map(
                        fn ($id, $title) => ['id' => $id, 'title' => $title],
                        array_keys($componentData['options']),
                        array_values($componentData['options'])
                    );
                    $children[] = [
                        'type' => 'Dropdown',
                        'name' => $componentData['name'],
                        'label' => $componentData['label'],
                        'required' => $componentData['is_required'] ?? false,
                        'data-source' => '${data.'.$optionsKey.'}',
                    ];
                    break;

                case 'text_input':
                    $children[] = [
                        'type' => 'TextInput',
                        'name' => $componentData['name'],
                        'label' => $componentData['label'],
                        'required' => $componentData['is_required'] ?? false,
                        'input-type' => $componentData['input_type'] ?? 'text',
                    ];
                    break;

                case 'image':
                    $children[] = [
                        'type' => 'Image',
                        'src' => $componentData['src'],
                        'height' => $componentData['height'] ?? 'medium',
                    ];
                    break;

                case 'date_picker':
                    $children[] = [
                        'type' => 'DatePicker',
                        'name' => $componentData['name'],
                        'label' => $componentData['label'],
                        'required' => $componentData['is_required'] ?? false,
                    ];
                    break;
            }
        }

        return [
            'id' => $screenConfig['id'],
            'title' => $replaceVariables($screenConfig['title']),
            'body' => 'Please complete the form below.', // Generic body for now
            'footer' => $replaceVariables($screenConfig['footer_label']),
            'data_bindings' => $dataBindings,
            // We still need to build the final layout JSON that uses the children.
            // For now, this structure is sufficient for the data exchange.
        ];
    }
}
