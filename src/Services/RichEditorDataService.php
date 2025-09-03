<?php

namespace Backstage\Fields\Services;

class RichEditorDataService
{
    /**
     * Convert RichEditor string values to array format for Filament v4 compatibility
     * This method is kept for backward compatibility during the transition period
     * After running the migration, this should no longer be needed
     */
    public static function convertRichEditorStringsToArrays(array $data, string $valueColumn): array
    {
        if (!isset($data[$valueColumn])) {
            return $data;
        }

        $richEditorFields = \Backstage\Fields\Models\Field::where('field_type', 'rich-editor')
            ->pluck('ulid')
            ->toArray();

        // Use recursive conversion to handle all nested structures
        $data[$valueColumn] = self::convertRichEditorStringsRecursively(
            $data[$valueColumn], 
            $richEditorFields
        );

        return $data;
    }

    /**
     * Recursively convert RichEditor strings to arrays in any nested structure
     * This method is kept for backward compatibility during the transition period
     */
    public static function convertRichEditorStringsRecursively(array $data, array $richEditorFields): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = self::convertRichEditorStringsRecursively($value, $richEditorFields);
            } elseif (is_string($value) && in_array($key, $richEditorFields)) {
                $data[$key] = self::convertStringToRichEditorArray($value);
            }
        }

        return $data;
    }

    /**
     * Process form data to ensure RichEditor fields are in the correct format
     * This method is kept for backward compatibility during the transition period
     */
    public static function processFormDataForRichEditor(array $data): array
    {
        $richEditorFields = \Backstage\Fields\Models\Field::where('field_type', 'rich-editor')
            ->pluck('ulid')
            ->toArray();

        // Process the entire data array recursively
        return self::processRichEditorInData($data, $richEditorFields);
    }

    /**
     * Recursively process RichEditor fields in any data structure
     * This method is kept for backward compatibility during the transition period
     */
    public static function processRichEditorInData($data, array $richEditorFields)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (is_string($value) && in_array($key, $richEditorFields)) {
                    $data[$key] = self::convertStringToRichEditorArray($value);
                } elseif (is_array($value)) {
                    $data[$key] = self::processRichEditorInData($value, $richEditorFields);
                }
            }
        }

        return $data;
    }

    /**
     * Convert HTML string to Filament v4 RichEditor array format
     * This method is kept for backward compatibility during the transition period
     */
    public static function convertStringToRichEditorArray(?string $html): array
    {
        if (empty($html) || $html === null) {
            return [
                'type' => 'doc',
                'content' => []
            ];
        }

        // For now, create a simple paragraph structure
        // In a more sophisticated implementation, you'd parse the HTML properly
        $text = strip_tags($html);
        
        if (empty($text)) {
            return [
                'type' => 'doc',
                'content' => []
            ];
        }
        
        return [
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'paragraph',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => $text
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Check if a field is a RichEditor field
     */
    public static function isRichEditorField(string $fieldUlid): bool
    {
        return \Backstage\Fields\Models\Field::where('ulid', $fieldUlid)
            ->where('field_type', 'rich-editor')
            ->exists();
    }

    /**
     * Get all RichEditor field ULIDs
     */
    public static function getRichEditorFieldUlids(): array
    {
        return \Backstage\Fields\Models\Field::where('field_type', 'rich-editor')
            ->pluck('ulid')
            ->toArray();
    }

    /**
     * Get empty RichEditor array structure for new content
     */
    public static function getEmptyRichEditorArray(): array
    {
        return [
            'type' => 'doc',
            'content' => []
        ];
    }
}
